<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Scheduler;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ScheduledBatchEvent;
use Mautic\CampaignBundle\Event\ScheduledEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventScheduler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var Interval
     */
    private $intervalScheduler;

    /**
     * @var DateTime
     */
    private $dateTimeScheduler;

    /**
     * @var EventCollector
     */
    private $collector;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * EventScheduler constructor.
     *
     * @param LoggerInterface          $logger
     * @param EventLogger              $eventLogger
     * @param Interval                 $intervalScheduler
     * @param DateTime                 $dateTimeScheduler
     * @param EventCollector           $collector
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        LoggerInterface $logger,
        EventLogger $eventLogger,
        Interval $intervalScheduler,
        DateTime $dateTimeScheduler,
        EventCollector $collector,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->logger               = $logger;
        $this->dispatcher           = $dispatcher;
        $this->eventLogger          = $eventLogger;
        $this->intervalScheduler    = $intervalScheduler;
        $this->dateTimeScheduler    = $dateTimeScheduler;
        $this->collector            = $collector;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param Event     $event
     * @param \DateTime $executionDate
     * @param Lead      $contact
     */
    public function scheduleForContact(Event $event, \DateTime $executionDate, Lead $contact)
    {
        $contacts = new ArrayCollection([$contact]);

        $this->schedule($event, $executionDate, $contacts);
    }

    /**
     * @param Event           $event
     * @param \DateTime       $executionDate
     * @param ArrayCollection $contacts
     * @param bool            $isInactiveEvent
     */
    public function schedule(Event $event, \DateTime $executionDate, ArrayCollection $contacts, $isInactiveEvent = false)
    {
        $config = $this->collector->getEventConfig($event);

        // Load the rotations for creating new log entries
        $this->eventLogger->hydrateContactRotationsForNewLogs($contacts->getKeys(), $event->getCampaign()->getId());

        // If this is relative to a specific hour, process the contacts in batches by contacts' timezone
        if ($this->intervalScheduler->isContactSpecificExecutionDateRequired($event)) {
            $groupedExecutionDates = $this->intervalScheduler->groupContactsByDate($event, $contacts, $executionDate);

            foreach ($groupedExecutionDates as $groupExecutionDateDAO) {
                $this->scheduleEventForContacts(
                    $event,
                    $config,
                    $groupExecutionDateDAO->getExecutionDate(),
                    $groupExecutionDateDAO->getContacts(),
                    $isInactiveEvent
                );
            }

            return;
        }

        // Otherwise just schedule as the default
        $this->scheduleEventForContacts($event, $config, $executionDate, $contacts, $isInactiveEvent);
    }

    /**
     * @param LeadEventLog $log
     * @param \DateTime    $toBeExecutedOn
     */
    public function reschedule(LeadEventLog $log, \DateTime $toBeExecutedOn)
    {
        $log->setTriggerDate($toBeExecutedOn);
        $this->eventLogger->persistLog($log);

        $event  = $log->getEvent();
        $config = $this->collector->getEventConfig($event);

        $this->dispatchScheduledEvent($config, $log, true);
    }

    /**
     * @param ArrayCollection|LeadEventLog[] $logs
     * @param \DateTime                      $toBeExecutedOn
     */
    public function rescheduleLogs(ArrayCollection $logs, \DateTime $toBeExecutedOn)
    {
        foreach ($logs as $log) {
            $log->setTriggerDate($toBeExecutedOn);
        }

        $this->eventLogger->persistCollection($logs);

        $event  = $logs->first()->getEvent();
        $config = $this->collector->getEventConfig($event);

        $this->dispatchBatchScheduledEvent($config, $event, $logs, true);
    }

    /**
     * @param LeadEventLog $log
     */
    public function rescheduleFailure(LeadEventLog $log)
    {
        if (!$interval = $this->coreParametersHelper->getParameter('campaign_time_wait_on_event_false')) {
            return;
        }

        try {
            $date = new \DateTime();
            $date->add(new \DateInterval($interval));
        } catch (\Exception $exception) {
            // Bad interval
            return;
        }

        $this->reschedule($log, $date);
    }

    /**
     * @param ArrayCollection $logs
     */
    public function rescheduleFailures(ArrayCollection $logs)
    {
        if (!$interval = $this->coreParametersHelper->getParameter('campaign_time_wait_on_event_false')) {
            return;
        }

        if (!$logs->count()) {
            return;
        }

        try {
            $date = new \DateTime();
            $date->add(new \DateInterval($interval));
        } catch (\Exception $exception) {
            // Bad interval
            return;
        }

        foreach ($logs as $log) {
            $this->reschedule($log, $date);
        }

        // Send out a batch event
        $event  = $logs->first()->getEvent();
        $config = $this->collector->getEventConfig($event);

        $this->dispatchBatchScheduledEvent($config, $event, $logs, true);
    }

    /**
     * @param Event          $event
     * @param \DateTime|null $compareFromDateTime
     * @param \DateTime|null $comparedToDateTime
     *
     * @return \DateTime
     *
     * @throws NotSchedulableException
     */
    public function getExecutionDateTime(Event $event, \DateTime $compareFromDateTime = null, \DateTime $comparedToDateTime = null)
    {
        if (null === $compareFromDateTime) {
            $compareFromDateTime = new \DateTime();
        } else {
            // Prevent comparisons from modifying original object
            $compareFromDateTime = clone $compareFromDateTime;
        }

        if (null === $comparedToDateTime) {
            $comparedToDateTime = clone $compareFromDateTime;
        } else {
            // Prevent comparisons from modifying original object
            $comparedToDateTime = clone $comparedToDateTime;
        }

        switch ($event->getTriggerMode()) {
            case Event::TRIGGER_MODE_IMMEDIATE:
            case null: // decision
                $this->logger->debug('CAMPAIGN: ('.$event->getId().') Executing immediately');

                return $compareFromDateTime;
            case Event::TRIGGER_MODE_INTERVAL:
                return $this->intervalScheduler->getExecutionDateTime($event, $compareFromDateTime, $comparedToDateTime);
            case Event::TRIGGER_MODE_DATE:
                return $this->dateTimeScheduler->getExecutionDateTime($event, $compareFromDateTime, $comparedToDateTime);
        }

        throw new NotSchedulableException();
    }

    /**
     * @param LeadEventLog $log
     * @param \DateTime    $currentDateTime
     *
     * @return \DateTime
     *
     * @throws NotSchedulableException
     */
    public function validateExecutionDateTime(LeadEventLog $log, \DateTime $currentDateTime)
    {
        if (!$scheduledDateTime = $log->getTriggerDate()) {
            throw new NotSchedulableException();
        }

        $event = $log->getEvent();

        switch ($event->getTriggerMode()) {
            case Event::TRIGGER_MODE_IMMEDIATE:
            case null: // decision
                $this->logger->debug('CAMPAIGN: ('.$event->getId().') Executing immediately');

                return $currentDateTime;
            case Event::TRIGGER_MODE_INTERVAL:
                return $this->intervalScheduler->validateExecutionDateTime($log, $currentDateTime);
            case Event::TRIGGER_MODE_DATE:
                return $this->dateTimeScheduler->getExecutionDateTime($event, $currentDateTime, $scheduledDateTime);
        }

        throw new NotSchedulableException();
    }

    /**
     * @param ArrayCollection|Event[] $events
     * @param \DateTime               $lastActiveDate
     *
     * @return array
     *
     * @throws NotSchedulableException
     */
    public function getSortedExecutionDates(ArrayCollection $events, \DateTime $lastActiveDate)
    {
        $eventExecutionDates = [];

        /** @var Event $child */
        foreach ($events as $child) {
            $eventExecutionDates[$child->getId()] = $this->getExecutionDateTime($child, $lastActiveDate);
        }

        uasort(
            $eventExecutionDates,
            function (\DateTime $a, \DateTime $b) {
                if ($a === $b) {
                    return 0;
                }

                return $a < $b ? -1 : 1;
            }
        );

        return $eventExecutionDates;
    }

    /**
     * @param \DateTime $eventExecutionDate
     * @param \DateTime $earliestExecutionDate
     * @param \DateTime $now
     *
     * @return \DateTime
     */
    public function getExecutionDateForInactivity(\DateTime $eventExecutionDate, \DateTime $earliestExecutionDate, \DateTime $now)
    {
        if ($earliestExecutionDate->getTimestamp() === $eventExecutionDate->getTimestamp()) {
            // Inactivity is based on the "wait" period so execute now
            return clone $now;
        }

        return $eventExecutionDate;
    }

    /**
     * @param \DateTime $executionDate
     * @param \DateTime $now
     *
     * @return bool
     */
    public function shouldSchedule(\DateTime $executionDate, \DateTime $now)
    {
        // Mainly for functional tests so we don't have to wait minutes but technically can be used in an environment as well if this behavior
        // is desired by system admin
        if (false === (bool) getenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS')) {
            // Purposively ignore seconds to prevent rescheduling based on a variance of a few seconds
            $executionDate = new \DateTime($executionDate->format('Y-m-d H:i'), $executionDate->getTimezone());
            $now           = new \DateTime($now->format('Y-m-d H:i'), $now->getTimezone());
        }

        return $executionDate > $now;
    }

    /**
     * @param Event           $event
     * @param \DateTime       $executionDateTime
     * @param ArrayCollection $contacts
     * @param \DateTime       $comparedFromDateTime
     *
     * @throws NotSchedulableException
     */
    public function validateAndScheduleEventForContacts(Event $event, \DateTime $executionDateTime, ArrayCollection $contacts, \DateTime $comparedFromDateTime)
    {
        if ($this->intervalScheduler->isContactSpecificExecutionDateRequired($event)) {
            $this->logger->debug(
                'CAMPAIGN: Event ID# '.$event->getId().
                ' has to be scheduled based on contact specific parameters '.
                ' compared to '.$executionDateTime->format('Y-m-d H:i:s')
            );

            $groupedExecutionDates = $this->intervalScheduler->groupContactsByDate($event, $contacts, $executionDateTime);
            $config                = $this->collector->getEventConfig($event);

            foreach ($groupedExecutionDates as $groupExecutionDateDAO) {
                $this->scheduleEventForContacts(
                    $event,
                    $config,
                    $groupExecutionDateDAO->getExecutionDate(),
                    $groupExecutionDateDAO->getContacts()
                );
            }

            return;
        }

        if ($this->shouldSchedule($executionDateTime, $comparedFromDateTime)) {
            $this->schedule($event, $executionDateTime, $contacts);

            return;
        }

        throw new NotSchedulableException();
    }

    /**
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     * @param bool                  $isReschedule
     */
    private function dispatchScheduledEvent(AbstractEventAccessor $config, LeadEventLog $log, $isReschedule = false)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_SCHEDULED,
            new ScheduledEvent($config, $log, $isReschedule)
        );
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     * @param bool                  $isReschedule
     */
    private function dispatchBatchScheduledEvent(AbstractEventAccessor $config, Event $event, ArrayCollection $logs, $isReschedule = false)
    {
        if (!$logs->count()) {
            return;
        }

        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_SCHEDULED_BATCH,
            new ScheduledBatchEvent($config, $event, $logs, $isReschedule)
        );
    }

    /**
     * @param Event                 $event
     * @param AbstractEventAccessor $config
     * @param \DateTime             $executionDate
     * @param ArrayCollection       $contacts
     * @param bool                  $isInactiveEvent
     */
    private function scheduleEventForContacts(Event $event, AbstractEventAccessor $config, \DateTime $executionDate, ArrayCollection $contacts, $isInactiveEvent = false)
    {
        foreach ($contacts as $contact) {
            // Create the entry
            $log = $this->eventLogger->buildLogEntry($event, $contact, $isInactiveEvent);

            // Schedule it
            $log->setTriggerDate($executionDate);

            // Add it to the queue to persist to the DB
            $this->eventLogger->queueToPersist($log);

            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($event->getEventType()).' ID# '.$event->getId().' for contact ID# '.$contact->getId()
                .' has timing that is not appropriate and thus scheduled for '.$executionDate->format('Y-m-d H:m:i T')
            );

            $this->dispatchScheduledEvent($config, $log);
        }

        // Persist any pending in the queue
        $logs = $this->eventLogger->persistQueuedLogs();

        // Send out a batch event
        $this->dispatchBatchScheduledEvent($config, $event, $logs);

        // Update log entries and clear from memory
        $this->eventLogger->persistCollection($logs)
            ->clearCollection($logs);
    }
}

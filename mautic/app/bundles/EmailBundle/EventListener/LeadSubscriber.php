<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Entity\EmailReplyRepositoryInterface;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /** @var EmailReplyRepositoryInterface */
    private $emailReplyRepository;

    /**
     * LeadSubscriber constructor.
     *
     * @param EmailReplyRepositoryInterface $emailReplyRepository
     */
    public function __construct(EmailReplyRepositoryInterface $emailReplyRepository)
    {
        $this->emailReplyRepository = $emailReplyRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEmailEvents($event, 'read');
        $this->addEmailEvents($event, 'sent');
        $this->addEmailEvents($event, 'failed');
        $this->addEmailReplies($event);
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticEmailBundle:Stat')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $state
     */
    protected function addEmailEvents(LeadTimelineEvent $event, $state)
    {
        // Set available event types
        $eventTypeKey  = 'email.'.$state;
        $eventTypeName = $this->translator->trans('mautic.email.'.$state);
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('emailList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepository */
        $statRepository        = $this->em->getRepository('MauticEmailBundle:Stat');
        $queryOptions          = $event->getQueryOptions();
        $queryOptions['state'] = $state;
        $stats                 = $statRepository->getLeadStats($event->getLeadId(), $queryOptions);

        // Add total to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if (!empty($stat['email_name'])) {
                    $label = $stat['email_name'];
                } elseif (!empty($stat['storedSubject'])) {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email').': '.$stat['storedSubject'];
                } else {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email');
                }

                if (!empty($stat['idHash'])) {
                    $eventName = [
                        'label'      => $label,
                        'href'       => $this->router->generate('mautic_email_webview', ['idHash' => $stat['idHash']]),
                        'isExternal' => true,
                    ];
                } else {
                    $eventName = $label;
                }
                if ('failed' == $state or 'sent' == $state) { //this is to get the correct column for date dateSent
                    $dateSent = 'sent';
                } else {
                    $dateSent = 'read';
                }

                $contactId = $stat['lead_id'];
                unset($stat['lead_id']);
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$stat['id'],
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $stat['date'.ucfirst($dateSent)],
                        'extra'      => [
                            'stat' => $stat,
                            'type' => $state,
                        ],
                        'contentTemplate' => 'MauticEmailBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => ($state == 'read') ? 'fa-envelope-o' : 'fa-envelope',
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }

    /**
     * @param LeadTimelineEvent $event
     */
    protected function addEmailReplies(LeadTimelineEvent $event)
    {
        $eventTypeKey  = 'email.replied';
        $eventTypeName = $this->translator->trans('mautic.email.replied');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('emailList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $options          = $event->getQueryOptions();
        $replies          = $this->emailReplyRepository->getByLeadIdForTimeline($event->getLeadId(), $options);
        if (!$event->isEngagementCount()) {
            foreach ($replies['results'] as $reply) {
                $label = $this->translator->trans('mautic.email.timeline.event.email_reply');
                if (!empty($reply['email_name'])) {
                    $label .= ': '.$reply['email_name'];
                } elseif (!empty($reply['storedSubject'])) {
                    $label .= ': '.$reply['storedSubject'];
                }

                $contactId = $reply['lead_id'];
                unset($reply['lead_id']);

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$reply['id'],
                        'eventLabel' => $label,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $reply['date_replied'],
                        'icon'       => 'fa-envelope',
                        'contactId'  => $contactId,
                    ]
                );
            }
        }
    }
}

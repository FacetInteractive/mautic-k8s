<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EventLogger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventLogger;

    /**
     * @var Interval|
     */
    private $intervalScheduler;

    /**
     * @var DateTime|
     */
    private $dateTimeScheduler;

    /**
     * @var EventCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventCollector;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParamtersHelper;

    protected function setUp()
    {
        $this->logger              = new NullLogger();
        $this->coreParamtersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParamtersHelper->method('getParameter')
            ->willReturnCallback(
                function ($param, $default) {
                    return 'America/New_York';
                }
            );
        $this->eventLogger       = $this->createMock(EventLogger::class);
        $this->intervalScheduler = new Interval($this->logger, $this->coreParamtersHelper);
        $this->dateTimeScheduler = new DateTime($this->logger);
        $this->eventCollector    = $this->createMock(EventCollector::class);
        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
    }

    public function testShouldScheduleIgnoresSeconds()
    {
        $this->assertFalse(
            $this->getScheduler()->shouldSchedule(
                new \DateTime('2018-07-03 09:20:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    public function testShouldSchedule()
    {
        $this->assertTrue(
            $this->getScheduler()->shouldSchedule(
                new \DateTime('2018-07-03 09:21:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    public function testEventDoesNotGetRescheduledForRelativeTimeWhenValidated()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(1);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00', new \DateTimeZone('America/New_York'));

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00', new \DateTimeZone('America/New_York'));

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15', new \DateTimeZone('America/New_York'));

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $scheduler = $this->getScheduler();

        $executionDate = $scheduler->validateExecutionDateTime($log, $simulatedNow);
        $this->assertFalse($scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 09:00:00', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    public function testEventIsRescheduledForRelativeTimeIfAppropriate()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(1);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 11:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00');

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00');

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15');

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $scheduler = $this->getScheduler();

        $executionDate = $scheduler->validateExecutionDateTime($log, $simulatedNow);
        $this->assertTrue($scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 11:00:00', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    public function testEventDoesNotGetRescheduledForRelativeTimeWithDowWhenValidated()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00', new \DateTimeZone('America/New_York'));

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00', new \DateTimeZone('America/New_York'));

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15', new \DateTimeZone('America/New_York'));

        $dow = $simulatedNow->format('w');

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dow]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $scheduler     = $this->getScheduler();
        $executionDate = $scheduler->validateExecutionDateTime($log, $simulatedNow);

        $this->assertFalse($scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 13:00:15', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    private function getScheduler()
    {
        return new EventScheduler(
            $this->logger,
            $this->eventLogger,
            $this->intervalScheduler,
            $this->dateTimeScheduler,
            $this->eventCollector,
            $this->dispatcher,
            $this->coreParamtersHelper
        );
    }
}

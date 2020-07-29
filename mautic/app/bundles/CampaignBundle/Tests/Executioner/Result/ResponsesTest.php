<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Result\Responses;

class ResponsesTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractingResponsesFromLog()
    {
        $actionEvent = $this->createMock(Event::class);
        $actionEvent->method('getEventType')
            ->willReturn(Event::TYPE_ACTION);
        $actionEvent->method('getType')
            ->willReturn('actionEvent');
        $actionEvent->method('getId')
            ->willReturn(1);

        // BC should set response as just test
        $actionLog = $this->createMock(LeadEventLog::class);
        $actionLog->method('getEvent')
            ->willReturn($actionEvent);
        $actionLog->method('getMetadata')
            ->willReturn(['timeline' => 'test']);

        $action2Event = $this->createMock(Event::class);
        $action2Event->method('getEventType')
            ->willReturn(Event::TYPE_ACTION);
        $action2Event->method('getType')
            ->willReturn('action2Event');
        $action2Event->method('getId')
            ->willReturn(2);

        // Response should be full array
        $action2Log = $this->createMock(LeadEventLog::class);
        $action2Log->method('getEvent')
            ->willReturn($action2Event);
        $action2Log->method('getMetadata')
            ->willReturn(['timeline' => 'test', 'something' => 'else']);

        // Response should be full array
        $conditionEvent = $this->createMock(Event::class);
        $conditionEvent->method('getEventType')
            ->willReturn(Event::TYPE_CONDITION);
        $conditionEvent->method('getType')
            ->willReturn('conditionEvent');
        $conditionEvent->method('getId')
            ->willReturn(3);

        $conditionLog = $this->createMock(LeadEventLog::class);
        $conditionLog->method('getEvent')
            ->willReturn($conditionEvent);
        $conditionLog->method('getMetadata')
            ->willReturn(['something' => 'else']);

        $logs = new ArrayCollection([$actionLog, $action2Log, $conditionLog]);

        $responses = new Responses();
        $responses->setFromLogs($logs);

        $actions = [
            'actionEvent'  => [
                1 => 'test',
            ],
            'action2Event' => [
                2 => [
                    'timeline'  => 'test',
                    'something' => 'else',
                ],
            ],
        ];

        $conditions = [
            'conditionEvent' => [
                3 => [
                    'something' => 'else',
                ],
            ],
        ];

        $this->assertEquals(
            [
                'action'    => $actions,
                'condition' => $conditions,
            ],
            $responses->getResponseArray()
        );

        $this->assertEquals($actions, $responses->getActionResponses());
        $this->assertEquals($conditions, $responses->getConditionResponses());
    }
}

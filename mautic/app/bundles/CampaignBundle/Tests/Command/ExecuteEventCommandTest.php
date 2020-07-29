<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

class ExecuteEventCommandTest extends AbstractCampaignCommand
{
    public function testEventsAreExecutedForInactiveEventWithSingleContact()
    {
        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=1');

        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // There should be two events scheduled
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        $logIds = [];
        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Event is not scheduled for lead ID '.$log['lead_id']);
            }

            $logIds[] = $log['id'];
        }

        $this->runCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => implode(',', $logIds)]);

        // There should still be trhee events scheduled
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Event is not scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Pop off the last so we can test that only the two given are executed
        $lastId = array_pop($logIds);

        // Wait 20 seconds to go past scheduled time
        sleep(20);

        $this->runCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => implode(',', $logIds)]);

        // The events should have executed
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        foreach ($byEvent[2] as $log) {
            // Lasta
            if ($log['id'] === $lastId) {
                if (0 === (int) $log['is_scheduled']) {
                    $this->fail('Event is not scheduled when it should be for lead ID '.$log['lead_id']);
                }

                continue;
            }

            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Event is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=0');
    }
}

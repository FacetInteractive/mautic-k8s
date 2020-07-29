<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Membership;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\EventDispatcher;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testLeadChangeEventDispatched()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $this->isInstanceOf(CampaignLeadChangeEvent::class));

        $this->getDispatcher()->dispatchMembershipChange(new Lead(), new Campaign(), Adder::NAME);
    }

    public function testBatchChangeEventDispatched()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE, $this->isInstanceOf(CampaignLeadChangeEvent::class));

        $this->getDispatcher()->dispatchBatchMembershipChange([new Lead()], new Campaign(), Adder::NAME);
    }

    private function getDispatcher()
    {
        return new EventDispatcher($this->eventDispatcher);
    }
}

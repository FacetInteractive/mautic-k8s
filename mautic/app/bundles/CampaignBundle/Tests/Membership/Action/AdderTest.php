<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Membership\Action;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;

class AdderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LeadRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $leadRepository;

    /**
     * @var LeadEventLogRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $leadEventLogRepository;

    public function setUp()
    {
        $this->leadRepository         = $this->createMock(LeadRepository::class);
        $this->leadEventLogRepository = $this->createMock(LeadEventLogRepository::class);
    }

    public function testNewMemberAdded()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);
        $campaign->method('allowRestart')
            ->willReturn(true);

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->WillReturn(2);

        $this->leadEventLogRepository->method('hasBeenInCampaignRotation')
            ->with(2, 1, 1)
            ->willReturn(true);

        $this->leadRepository->expects($this->once())
            ->method('saveEntity');

        $campaignMember = $this->getAdder()->createNewMembership($contact, $campaign, true);

        $this->assertEquals($contact, $campaignMember->getLead());
        $this->assertEquals($campaign, $campaignMember->getCampaign());
        $this->assertEquals(true, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testManuallyRemovedAddedBackWhenManualActionAddsTheMember()
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(true);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, true);

        $this->assertEquals(true, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testFilterRemovedAddedBackWhenManualActionAddsTheMember()
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaignMember->setDateLastExited(new \DateTime());
        $campaign = new Campaign();
        $campaign->setAllowRestart(true);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, false);

        $this->assertEquals(false, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testManuallyRemovedIsNotAddedBackWhenFilterActionAddsTheMember()
    {
        $this->expectException(ContactCannotBeAddedToCampaignException::class);

        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(false);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, false);
    }

    /**
     * @return Adder
     */
    private function getAdder()
    {
        return new Adder($this->leadRepository, $this->leadEventLogRepository);
    }
}

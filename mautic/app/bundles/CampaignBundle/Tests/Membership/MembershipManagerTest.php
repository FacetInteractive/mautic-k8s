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

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Action\Remover;
use Mautic\CampaignBundle\Membership\EventDispatcher;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;

class MembershipManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Adder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $adder;

    /**
     * @var Remover|\PHPUnit_Framework_MockObject_MockObject
     */
    private $remover;

    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var LeadRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $leadRepository;

    /**
     * @var NullLogger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    protected function setUp()
    {
        $this->adder           = $this->createMock(Adder::class);
        $this->remover         = $this->createMock(Remover::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->leadRepository  = $this->createMock(LeadRepository::class);
        $this->logger          = new NullLogger();
    }

    public function testMembershipCreatedIfNotFound()
    {
        $contact  = new Lead();
        $campaign = new Campaign();

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->adder->expects($this->once())
            ->method('createNewMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->addContact($contact, $campaign);
    }

    public function testMembershipUpdatedIfFound()
    {
        $contact        = new Lead();
        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($campaignMember);

        $this->adder->expects($this->once())
            ->method('updateExistingMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->addContact($contact, $campaign);
    }

    public function testMembershipIsUpdatedWhenRemoved()
    {
        $contact        = new Lead();
        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($campaignMember);

        $this->remover->expects($this->once())
            ->method('updateExistingMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->removeContact($contact, $campaign);
    }

    public function testContactsAreAddedOrUpdated()
    {
        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn(1);
        $contact2 = $this->createMock(Lead::class);
        $contact2->method('getId')
            ->willReturn(2);

        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact2);
        $campaignMember->setCampaign($campaign);

        // One is found and one is not
        $this->leadRepository->expects($this->once())
            ->method('getCampaignMembers')
            ->willReturn([$contact2->getId() => $campaignMember]);

        $this->adder->expects($this->once())
            ->method('updateExistingMembership')
            ->with($campaignMember, true);

        $this->adder->expects($this->once())
            ->method('createNewMembership')
            ->with($contact, $campaign, true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchBatchMembershipChange')
            ->with([$contact->getId() => $contact, $contact2->getId() => $contact2], $campaign, Adder::NAME);

        $this->getManager()->addContacts(new ArrayCollection([1 => $contact, 2 => $contact2]), $campaign);
    }

    public function testContactsAreRemoved()
    {
        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn(1);
        $contact2 = $this->createMock(Lead::class);
        $contact2->method('getId')
            ->willReturn(2);

        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact2);
        $campaignMember->setCampaign($campaign);

        // One is found and one is not
        $this->leadRepository->expects($this->once())
            ->method('getCampaignMembers')
            ->willReturn([$contact2->getId() => $campaignMember]);

        $this->remover->expects($this->once())
            ->method('updateExistingMembership')
            ->with($campaignMember, false);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchBatchMembershipChange')
            ->with([$contact2->getId() => $contact2], $campaign, Remover::NAME);

        $this->getManager()->removeContacts(new ArrayCollection([1 => $contact, 2 => $contact2]), $campaign);
    }

    private function getManager()
    {
        return new MembershipManager($this->adder, $this->remover, $this->eventDispatcher, $this->leadRepository, $this->logger);
    }
}

<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\NullLogger;

class KickoffContactFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadRepository
     */
    private $leadRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CampaignRepository
     */
    private $campaignRepository;

    protected function setUp()
    {
        $this->leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->campaignRepository = $this->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoContactsFoundExceptionIsThrown()
    {
        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, $limiter);
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound()
    {
        $contactIds = [1, 2];

        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn($contactIds);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, $limiter);
    }

    public function testArrayCollectionIsReturnedForFoundContacts()
    {
        $contactIds = [1, 2];

        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn($contactIds);

        $foundContacts = new ArrayCollection([new Lead(), new Lead()]);
        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($foundContacts);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->assertEquals($foundContacts, $this->getContactFinder()->getContacts(1, $limiter));
    }

    /**
     * @return KickoffContactFinder
     */
    private function getContactFinder()
    {
        return new KickoffContactFinder(
            $this->leadRepository,
            $this->campaignRepository,
            new NullLogger()
        );
    }
}

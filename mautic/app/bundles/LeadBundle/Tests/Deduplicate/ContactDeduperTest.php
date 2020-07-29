<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Mautic\LeadBundle\Deduplicate\ContactDeduper;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\FieldModel;

class ContactDeduperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FieldModel
     */
    private $fieldModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContactMerger
     */
    private $contactMerger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadRepository
     */
    private $leadRepository;

    protected function setUp()
    {
        $this->fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactMerger = $this->getMockBuilder(ContactMerger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDuplicatesAreMergedWithMergeOlderIntoNewer()
    {
        $this->leadRepository->expects($this->once())
            ->method('getIdentifiedContactCount')
            ->willReturn(4);

        $lead1 = $this->getLead(1, 'lead1@test.com');
        $lead2 = $this->getLead(2, 'lead2@test.com');
        $lead3 = $this->getLead(3, 'lead3@test.com');
        // Duplicate
        $lead4 = $this->getLead(4, 'lead1@test.com');

        $this->leadRepository->expects($this->exactly(4))
            ->method('getNextIdentifiedContact')
            ->withConsecutive([0], [1], [2], [3])
            ->willReturnOnConsecutiveCalls($lead1, $lead2, $lead3, null);

        $this->fieldModel->expects($this->exactly(3))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'email']);
        $this->fieldModel->expects($this->once())
            ->method('getFieldList')
            ->willReturn(['email' => 'email']);

        $this->leadRepository->expects($this->exactly(3))
            ->method('getLeadsByUniqueFields')
            // $lead4 has a older dateAdded
            ->willReturnOnConsecutiveCalls([$lead4, $lead1], [], []);

        // $lead4 is winner as the older contact
        $this->contactMerger->expects($this->once())
            ->method('merge')
            ->with($lead4, $lead1);

        $this->getDeduper()->deduplicate();
    }

    public function testDuplicatesAreMergedWithMergeNewerIntoOlder()
    {
        $this->leadRepository->expects($this->once())
            ->method('getIdentifiedContactCount')
            ->willReturn(4);

        $lead1 = $this->getLead(1, 'lead1@test.com');
        $lead2 = $this->getLead(2, 'lead2@test.com');
        $lead3 = $this->getLead(3, 'lead3@test.com');
        // Duplicate
        $lead4 = $this->getLead(4, 'lead1@test.com');

        $this->leadRepository->expects($this->exactly(4))
            ->method('getNextIdentifiedContact')
            ->withConsecutive([0], [1], [2], [3])
            ->willReturnOnConsecutiveCalls($lead1, $lead2, $lead3, null);

        $this->fieldModel->expects($this->exactly(3))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'email']);
        $this->fieldModel->expects($this->once())
            ->method('getFieldList')
            ->willReturn(['email' => 'email']);

        $this->leadRepository->expects($this->exactly(3))
            ->method('getLeadsByUniqueFields')
            // $lead1 has a older dateAdded
            ->willReturnOnConsecutiveCalls([$lead1, $lead4], [], []);

        // $lead1 is the winner as the newer contact
        $this->contactMerger->expects($this->once())
            ->method('merge')
            ->with($lead1, $lead4);

        $this->getDeduper()->deduplicate();
    }

    /**
     * @param   $id
     * @param   $email
     *
     * @return Lead|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getLead($id, $email)
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $lead->expects($this->any())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => $id,
                    'points' => 10,
                    'email'  => $email,
                ]
            );
        $lead->expects($this->any())
            ->method('getDateModified')
            ->willReturn(new \DateTime());

        return $lead;
    }

    /**
     * @return ContactDeduper
     */
    private function getDeduper()
    {
        return new ContactDeduper(
            $this->fieldModel,
            $this->contactMerger,
            $this->leadRepository
        );
    }
}

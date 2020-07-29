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

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ContactMergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MergeRecordRepository
     */
    private $leadRepo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MergeRecordRepository
     */
    private $mergeRecordRepo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher
     */
    private $dispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Logger
     */
    private $logger;

    protected function setUp()
    {
        $this->leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->leadRepo = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mergeRecordRepo = $this->getMockBuilder(MergeRecordRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->leadModel->method('getRepository')->willReturn($this->leadRepo);
    }

    public function testMergeTimestamps()
    {
        $oldestDateTime = new \DateTime('-60 minutes');
        $latestDateTime = new \DateTime('-30 minutes');

        $winner = new Lead();
        $winner->setLastActive($oldestDateTime);
        $winner->setDateIdentified($latestDateTime);

        $loser  = new Lead();
        $loser->setLastActive($latestDateTime);
        $loser->setDateIdentified($oldestDateTime);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getLastActive());
        $this->assertEquals($oldestDateTime, $winner->getDateIdentified());

        // Test with null date identified loser
        $winner->setDateIdentified($latestDateTime);
        $loser->setDateIdentified(null);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getDateIdentified());

        // Test with null date identified winner
        $winner->setDateIdentified(null);
        $loser->setDateIdentified($latestDateTime);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getDateIdentified());
    }

    public function testMergeIpAddresses()
    {
        $winner = new Lead();
        $winner->addIpAddress((new IpAddress('1.2.3.4'))->setIpDetails('from winner'));
        $winner->addIpAddress((new IpAddress('4.3.2.1'))->setIpDetails('from winner'));
        $winner->addIpAddress((new IpAddress('5.6.7.8'))->setIpDetails('from winner'));

        $loser = new Lead();
        $loser->addIpAddress((new IpAddress('5.6.7.8'))->setIpDetails('from loser'));
        $loser->addIpAddress((new IpAddress('8.7.6.5'))->setIpDetails('from loser'));

        $this->getMerger()->mergeIpAddressHistory($winner, $loser);

        $ipAddresses = $winner->getIpAddresses();
        $this->assertCount(4, $ipAddresses);

        $ipAddressArray = $ipAddresses->toArray();

        $expectedIpAddressArray = [
            '1.2.3.4' => 'from winner',
            '4.3.2.1' => 'from winner',
            '5.6.7.8' => 'from winner',
            '8.7.6.5' => 'from loser',
        ];

        foreach ($expectedIpAddressArray as $ipAddress => $ipId) {
            $this->assertSame($ipAddress, $ipAddressArray[$ipAddress]->getIpAddress());
            $this->assertSame($ipId, $ipAddressArray[$ipAddress]->getIpDetails());
        }
    }

    public function testMergeFieldDataWithLoserAsNewlyUpdated()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime('-30 minutes');
        $loserDateModified  = new \DateTime();
        $winner->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);
        $loser->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($loserDateModified);
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $winner->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        // Loser values are newest so should be kept
        // id and points should not be set addUpdatedField should only be called once for email
        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithWinnerAsNewlyUpdated()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime();
        $loserDateModified  = new \DateTime('-30 minutes');
        $winner->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->once())
            ->method('getId');

        // Winner values are newest so should be kept
        // addUpdatedField should never be called as they aren't different values
        $winner->expects($this->never())
            ->method('addUpdatedField');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithLoserAsNewlyCreated()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime('-30 minutes');
        $loserDateModified  = new \DateTime();
        $winner->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(null);
        $loser->expects($this->once())
            ->method('getDateAdded')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        // Loser values are newest so should be kept
        // id and points should not be set addUpdatedField should only be called once for email
        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithWinnerAsNewlyCreated()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime();
        $loserDateModified  = new \DateTime('-30 minutes');
        $winner->expects($this->once())
            ->method('getDateModified')
            ->willReturn(null);
        $winner->expects($this->once())
            ->method('getDateAdded')
            ->willReturn($winnerDateModified);
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->once())
            ->method('getId');

        // Winner values are newest so should be kept
        // addUpdatedField should never be called as they aren't different values
        $winner->expects($this->never())
            ->method('addUpdatedField');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeOwners()
    {
        $winner = new Lead();
        $loser  = new Lead();

        $winnerOwner = new User();
        $winnerOwner->setUsername('bob');
        $winner->setOwner($winnerOwner);

        $loserOwner = new User();
        $loserOwner->setUsername('susan');
        $loser->setOwner($loserOwner);

        // Should not have been merged due to winner already having one
        $this->getMerger()->mergeOwners($winner, $loser);
        $this->assertEquals($winnerOwner->getUsername(), $winner->getOwner()->getUsername());

        $winner->setOwner(null);
        $this->getMerger()->mergeOwners($winner, $loser);

        // Should be set to loser owner since winner owner was null
        $this->assertEquals($loserOwner->getUsername(), $winner->getOwner()->getUsername());
    }

    public function testMergePoints()
    {
        $winner = new Lead();
        $loser  = new Lead();

        $winner->setPoints(100);
        $loser->setPoints(50);

        $this->getMerger()->mergePoints($winner, $loser);

        $this->assertEquals(150, $winner->getPoints());
    }

    public function testMergeTags()
    {
        $winner = new Lead();
        $loser  = new Lead();
        $loser->addTag(new Tag('loser'));
        $loser->addTag(new Tag('loser2'));

        $this->leadModel->expects($this->once())
            ->method('modifyTags')
            ->with($winner, ['loser', 'loser2'], null, false);

        $this->getMerger()->mergeTags($winner, $loser);
    }

    public function testFullMergeThrowsSameContactException()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->expectException(SameContactException::class);

        $this->getMerger()->merge($winner, $loser);
    }

    public function testFullMerge()
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );
        $winner->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn(new \DateTime('-30 minutes'));

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );
        $loser->expects($this->exactly(2))
            ->method('getDateModified')
            ->willReturn(new \DateTime());

        // updateMergeRecords
        $this->mergeRecordRepo->expects($this->once())
            ->method('moveMergeRecord')
            ->with(2, 1);

        // mergeIpAddresses
        $ip = new IpAddress('1.2.3..4');
        $loser->expects($this->once())
            ->method('getIpAddresses')
            ->willReturn(new ArrayCollection([$ip]));
        $winner->expects($this->once())
            ->method('addIpAddress')
            ->with($ip);

        // mergeFieldData
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');
        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        // mergeOwners
        $winner->expects($this->never())
            ->method('setOwner');

        // mergePoints
        $loser->expects($this->once())
            ->method('getPoints')
            ->willReturn(100);
        $winner->expects($this->once())
            ->method('adjustPoints')
            ->with(100);

        // mergeTags
        $loser->expects($this->once())
            ->method('getTags')
            ->willReturn(new ArrayCollection());
        $this->leadModel->expects($this->once())
            ->method('modifyTags')
            ->with($winner, [], null, false);

        $this->getMerger()->merge($winner, $loser);
    }

    /**
     * @return ContactMerger
     */
    private function getMerger()
    {
        return new ContactMerger(
            $this->leadModel,
            $this->mergeRecordRepo,
            $this->dispatcher,
            $this->logger
        );
    }
}

<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\EventListener\MaintenanceSubscriber;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MaintenanceSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MaintenanceSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $connection          = $this->createMock(Connection::class);
        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $this->subscriber    = new MaintenanceSubscriber($connection, $userTokenRepository);
        $translator          = $this->createMock(TranslatorInterface::class);
        $this->subscriber->setTranslator($translator);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', -50]],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testOnDataCleanup()
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', 'mautic');
        }

        $dateTime         = new \DateTimeImmutable();
        $format           = 'Y-m-d H:i:s';
        $rowCount         = 2;
        $translatedString = 'nonsense';

        $dateTimeMock = $this->createMock(\DateTime::class);
        $dateTimeMock
            ->expects($this->exactly(2))
            ->method('format')
            ->with($format)
            ->willReturn($dateTime->format($format));

        $event = $this->createMock(MaintenanceEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getDate')
            ->willReturn($dateTimeMock);
        $event
            ->expects($this->exactly(3))
            ->method('isDryRun')
            ->willReturn(false);
        $event
            ->expects($this->exactly(3))
            ->method('setStat');

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder
            ->expects($this->exactly(2))
            ->method('lte')
            ->with('date_added', ':date');

        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($expressionBuilder);
        $qb
            ->expects($this->exactly(2))
            ->method('where')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn($rowCount);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $subscriber          = new MaintenanceSubscriber($connection, $userTokenRepository);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->willReturn($translatedString);
        $subscriber->setTranslator($translator);

        $this->assertNull($subscriber->onDataCleanup($event));
    }
}

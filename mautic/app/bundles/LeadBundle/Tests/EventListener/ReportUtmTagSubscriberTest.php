<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\EventListener\ReportUtmTagSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Symfony\Component\Translation\TranslatorInterface;

class ReportUtmTagSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testNotRelevantContextBuilder()
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);
        $reportBuilderEventMock = $this->createMock(ReportBuilderEvent::class);

        $reportBuilderEventMock->expects($this->once())
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(false);

        $reportBuilderEventMock->expects($this->never())
            ->method('addTable');

        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportUtmTagSubscriber->onReportBuilder($reportBuilderEventMock);
    }

    public function testNotRelevantContextGenerate()
    {
        $fieldsBuilderMock        = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->once())
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(false);

        $reportGeneratorEventMock->expects($this->never())
            ->method('getQueryBuilder');

        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    public function testReportBuilder()
    {
        $translatorMock        = $this->createMock(TranslatorInterface::class);
        $channelListHelperMock = $this->createMock(ChannelListHelper::class);
        $reportHelperMock      = $this->createMock(ReportHelper::class);
        $fieldsBuilderMock     = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock = $this->createMock(CompanyReportData::class);

        $leadColumns = [
            'lead.name' => [
                'label' => 'lead name',
                'type'  => 'bool',
            ],
        ];
        $companyColumns = [
            'comp.name' => [
                'label' => 'company name',
                'type'  => 'bool',
            ],
        ];

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->with('l.')
            ->willReturn($leadColumns);

        $companyReportDataMock->expects($this->once())
            ->method('getCompanyData')
            ->with()
            ->willReturn($companyColumns);

        $reportBuilderEvent = new ReportBuilderEvent($translatorMock, $channelListHelperMock, 'lead.utmTag', [], $reportHelperMock);

        $segmentReportSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $segmentReportSubscriber->onReportBuilder($reportBuilderEvent);

        $expected = [
            'lead.utmTag' => [
                'display_name' => 'mautic.lead.report.utm.utm_tag',
                'columns'      => [
                    'lead.name' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'name',
                    ],
                    'comp.name' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'name',
                    ],
                    'utm.utm_campaign' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'utm_campaign',
                    ],
                    'utm.utm_content' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'utm_content',
                    ],
                    'utm.utm_medium' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'utm_medium',
                    ],
                    'utm.utm_source' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'utm_source',
                    ],
                    'utm.utm_term' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'utm_term',
                    ],
                ],
                'group' => 'contacts',
            ],
        ];

        $this->assertEquals($expected, $reportBuilderEvent->getTables()); //Different order of keys on PHP 5.6.
    }

    public function testReportGenerateNoJoinedTables()
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }

        $reportGeneratorEventMock = $this->getReportGeneratorEventMock();
        $reportUtmTagSubscriber   = $this->getReportUtmTagSubscriber();
        $queryBuilderMock         = $this->getQueryBuilderMock();

        $reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    public function testReportGenerateWithUsers()
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }

        $reportGeneratorEventMock = $this->getReportGeneratorEventMock();
        $reportUtmTagSubscriber   = $this->getReportUtmTagSubscriber();
        $queryBuilderMock         = $this->getQueryBuilderMock();

        $reportGeneratorEventMock->expects($this->at(1))
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $reportGeneratorEventMock->expects($this->at(2))
            ->method('hasColumn')
            ->with(['u.first_name', 'u.last_name'])
            ->willReturn(true);

        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    /**
     * @return ReportUtmTagSubscriber
     */
    private function getReportUtmTagSubscriber()
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);
        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);

        return $reportUtmTagSubscriber;
    }

    /**
     * @return ReportGeneratorEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getReportGeneratorEventMock()
    {
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->at(0))
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(true);

        return $reportGeneratorEventMock;
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryBuilderMock()
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $queryBuilderMock->expects($this->at(0))
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_utmtags', 'utm')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->at(1))
            ->method('leftJoin')
            ->with('utm', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = utm.lead_id')
            ->willReturn($queryBuilderMock);

        return $queryBuilderMock;
    }
}

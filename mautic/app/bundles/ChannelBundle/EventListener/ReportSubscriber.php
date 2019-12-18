<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber.
 */
class ReportSubscriber extends CommonSubscriber
{
    const CONTEXT_MESSAGE_CHANNEL = 'message.channel';

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    public function __construct(CompanyReportData $companyReportData)
    {
        $this->companyReportData = $companyReportData;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY  => ['onReportDisplay', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$event->checkContext([self::CONTEXT_MESSAGE_CHANNEL])) {
            return;
        }

        // message queue
        $prefix  = 'mq.';
        $columns = [
            $prefix.'channel' => [
                'label' => 'mautic.message.queue.report.channel',
                'type'  => 'html',
            ],
            $prefix.'channel_id' => [
                'label' => 'mautic.message.queue.report.channel_id',
                'type'  => 'int',
            ],
            $prefix.'priority' => [
                'label' => 'mautic.message.queue.report.priority',
                'type'  => 'string',
            ],
            $prefix.'max_attempts' => [
                'label' => 'mautic.message.queue.report.max_attempts',
                'type'  => 'int',
            ],
            $prefix.'attempts' => [
                'label' => 'mautic.message.queue.report.attempts',
                'type'  => 'int',
            ],
            $prefix.'success' => [
                'label' => 'mautic.message.queue.report.success',
                'type'  => 'boolean',
            ],
            $prefix.'status' => [
                'label' => 'mautic.message.queue.report.status',
                'type'  => 'string',
            ],
            $prefix.'last_attempt' => [
                'label' => 'mautic.message.queue.report.last_attempt',
                'type'  => 'datetime',
            ],
            $prefix.'date_sent' => [
                'label' => 'mautic.message.queue.report.date_sent',
                'type'  => 'datetime',
            ],
            $prefix.'scheduled_date' => [
                'label' => 'mautic.message.queue.report.scheduled_date',
                'type'  => 'datetime',
            ],
            $prefix.'date_published' => [
                'label' => 'mautic.message.queue.report.date_published',
                'type'  => 'datetime',
            ],
        ];

        $companyColumns = $this->companyReportData->getCompanyData();

        $columns = array_merge(
            $columns,
            $event->getLeadColumns(),
            $companyColumns
        );

        $event->addTable(
            self::CONTEXT_MESSAGE_CHANNEL,
            [
                'display_name' => 'mautic.message.queue',
                'columns'      => $columns,
            ]
        );
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGeneratorEvent $event
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext([self::CONTEXT_MESSAGE_CHANNEL])) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->leftJoin('mq', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = mq.lead_id');

        if ($this->companyReportData->eventHasCompanyColumns($event)) {
            $event->addCompanyLeftJoin($queryBuilder);
        }

        $event->setQueryBuilder($queryBuilder);
    }

    /**
     * @param ReportDataEvent $event
     */
    public function onReportDisplay(ReportDataEvent $event)
    {
        $data = $event->getData();
        if ($event->checkContext([self::CONTEXT_MESSAGE_CHANNEL])) {
            if (isset($data[0]['channel']) && isset($data[0]['channel_id'])) {
                foreach ($data as $key => &$row) {
                    $href = $this->router->generate('mautic_'.$row['channel'].'_action', ['objectAction' => 'view', 'objectId' => $row['channel_id']]);
                    if (isset($row['channel'])) {
                        $row['channel'] = '<a href="'.$href.'">'.$row['channel'].'</a>';
                    }
                    unset($row);
                }
            }
        }

        $event->setData($data);
        unset($data);
    }
}

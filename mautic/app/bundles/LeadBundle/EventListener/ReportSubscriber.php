<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\StageBundle\Model\StageModel;

/**
 * Class ReportSubscriber.
 */
class ReportSubscriber extends CommonSubscriber
{
    const CONTEXT_LEADS                     = 'leads';
    const CONTEXT_LEAD_POINT_LOG            = 'lead.pointlog';
    const CONTEXT_CONTACT_ATTRIBUTION_MULTI = 'contact.attribution.multi';
    const CONTEXT_CONTACT_ATTRIBUTION_FIRST = 'contact.attribution.first';
    const CONTEXT_CONTACT_ATTRIBUTION_LAST  = 'contact.attribution.last';
    const CONTEXT_CONTACT_FREQUENCYRULES    = 'contact.frequencyrules';
    const CONTEXT_CONTACT_MESSAGE_FREQUENCY = 'contact.message.frequency';
    const CONTEXT_COMPANIES                 = 'companies';

    const GROUP_CONTACTS = 'contacts';

    private $leadContexts = [
        self::CONTEXT_LEADS,
        self::CONTEXT_LEAD_POINT_LOG,
        self::CONTEXT_CONTACT_ATTRIBUTION_MULTI,
        self::CONTEXT_CONTACT_ATTRIBUTION_FIRST,
        self::CONTEXT_CONTACT_ATTRIBUTION_LAST,
        self::CONTEXT_CONTACT_FREQUENCYRULES,
    ];
    private $companyContexts = [self::CONTEXT_COMPANIES];

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var StageModel
     */
    protected $stageModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var array
     */
    protected $channels;

    /**
     * @var array
     */
    protected $channelActions;

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    /**
     * @param LeadModel         $leadModel
     * @param StageModel        $stageModel
     * @param CampaignModel     $campaignModel
     * @param CompanyModel      $companyModel
     * @param CompanyReportData $companyReportData
     * @param FieldsBuilder     $fieldsBuilder
     */
    public function __construct(
        LeadModel $leadModel,
        StageModel $stageModel,
        CampaignModel $campaignModel,
        CompanyModel $companyModel,
        CompanyReportData $companyReportData,
        FieldsBuilder $fieldsBuilder
    ) {
        $this->leadModel         = $leadModel;
        $this->stageModel        = $stageModel;
        $this->campaignModel     = $campaignModel;
        $this->companyModel      = $companyModel;
        $this->companyReportData = $companyReportData;
        $this->fieldsBuilder     = $fieldsBuilder;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY        => ['onReportDisplay', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$event->checkContext($this->leadContexts) && !$event->checkContext($this->companyContexts)) {
            return;
        }

        if ($event->checkContext($this->leadContexts)) {
            $companyColumns = $this->companyReportData->getCompanyData();

            $columns = array_merge(
                $this->fieldsBuilder->getLeadFieldsColumns('l.'),
                $companyColumns
            );

            $filters = array_merge(
                $this->fieldsBuilder->getLeadFilter('l.', 's.'),
                $companyColumns
            );

            $data = [
                'display_name' => 'mautic.lead.leads',
                'columns'      => $columns,
                'filters'      => $filters,
            ];

            $event->addTable(self::CONTEXT_LEADS, $data, self::GROUP_CONTACTS);

            $attributionTypes = [
                self::CONTEXT_CONTACT_ATTRIBUTION_MULTI,
                self::CONTEXT_CONTACT_ATTRIBUTION_FIRST,
                self::CONTEXT_CONTACT_ATTRIBUTION_LAST,
            ];

            if ($event->checkContext($attributionTypes)) {
                $context = $event->getContext();
                foreach ($attributionTypes as $attributionType) {
                    if (empty($context) || $event->checkContext($attributionType)) {
                        $type = str_replace('contact.attribution.', '', $attributionType);
                        $this->injectAttributionReportData($event, $columns, $filters, $type);
                    }
                }
            }

            if ($event->checkContext([self::CONTEXT_LEADS, self::CONTEXT_LEAD_POINT_LOG])) {
                // Add shared graphs
                $event->addGraph(self::CONTEXT_LEADS, 'line', 'mautic.lead.graph.line.leads');
                $event->addGraph(self::CONTEXT_LEAD_POINT_LOG, 'line', 'mautic.lead.graph.line.leads');

                if ($event->checkContext(self::CONTEXT_LEAD_POINT_LOG)) {
                    $this->injectPointsReportData($event, $columns, $filters);
                }
            }

            if ($event->checkContext([self::CONTEXT_CONTACT_FREQUENCYRULES])) {
                $this->injectFrequencyReportData($event, $columns, $filters);
            }
        }

        if ($event->checkContext($this->companyContexts)) {
            $companyColumns = $this->fieldsBuilder->getCompanyFieldsColumns('comp.');

            $companyFilters = $companyColumns;

            $data = [
                'display_name' => 'mautic.lead.lead.companies',
                'columns'      => $companyColumns,
                'filters'      => $companyFilters,
            ];

            foreach ($this->companyContexts as $context) {
                $event->addTable($context, $data, self::CONTEXT_COMPANIES);
                $event->addGraph($context, 'line', 'mautic.lead.graph.line.companies');
                $event->addGraph($context, 'pie', 'mautic.lead.graph.pie.companies.industry');
                $event->addGraph($context, 'pie', 'mautic.lead.table.pie.company.country');
                $event->addGraph($context, 'table', 'mautic.lead.company.table.top.cities');
            }
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGeneratorEvent $event
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext($this->leadContexts) && !$event->checkContext($this->companyContexts)) {
            return;
        }

        $context = $event->getContext();
        $qb      = $event->getQueryBuilder();

        switch ($context) {
            case self::CONTEXT_LEADS:
                $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $event->addLeadIpAddressLeftJoin($qb);
                }

                if ($event->hasFilter('s.leadlist_id')) {
                    $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
                    $event->applyDateFilters($qb, 'date_added', 's');
                } else {
                    $event->applyDateFilters($qb, 'date_added', 'l');
                }
                break;

            case self::CONTEXT_LEAD_POINT_LOG:
                $event->applyDateFilters($qb, 'date_added', 'lp');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_points_change_log', 'lp')
                    ->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $event->addLeadIpAddressLeftJoin($qb);
                }

                break;
            case self::CONTEXT_CONTACT_FREQUENCYRULES:
                $event->applyDateFilters($qb, 'date_added', 'lf');
                $qb->from(MAUTIC_TABLE_PREFIX.'lead_frequencyrules', 'lf')
                    ->leftJoin('lf', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lf.lead_id');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $event->addLeadIpAddressLeftJoin($qb);
                }

                break;

            case self::CONTEXT_CONTACT_ATTRIBUTION_MULTI:
            case self::CONTEXT_CONTACT_ATTRIBUTION_FIRST:
            case self::CONTEXT_CONTACT_ATTRIBUTION_LAST:
                $localDateTriggered = 'CONVERT_TZ(log.date_triggered,\'UTC\',\''.date_default_timezone_get().'\')';
                $event->applyDateFilters($qb, 'attribution_date', 'l', true);
                $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                    ->join('l', MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log', 'l.id = log.lead_id')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'stages', 's', 'l.stage_id = s.id')
                    ->join('log', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'log.event_id = e.id')
                    ->join('log', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'log.campaign_id = c.id')
                    ->andWhere(
                        $qb->expr()->andX(
                            $qb->expr()->eq('e.event_type', $qb->expr()->literal('decision')),
                            $qb->expr()->eq('log.is_scheduled', 0),
                            $qb->expr()->isNotNull('l.attribution'),
                            $qb->expr()->neq('l.attribution', 0),
                            $qb->expr()->lte("DATE($localDateTriggered)", 'DATE(l.attribution_date)')
                        )
                    );

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
                }

                if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
                    $event->addIpAddressLeftJoin($qb, 'log');
                }

                if ($event->hasColumn(['cat.id', 'cat.title']) || $event->hasColumn(['cat.id', 'cat.title'])) {
                    $event->addCategoryLeftJoin($qb, 'c', 'cat');
                }

                $subQ = clone $qb;
                $subQ->resetQueryParts();

                $alias = str_replace('contact.attribution.', '', $context);

                $expr = $subQ->expr()->andX(
                    $subQ->expr()->eq("{$alias}e.event_type", $subQ->expr()->literal('decision')),
                    $subQ->expr()->eq("{$alias}log.lead_id", 'log.lead_id')
                );

                $subsetFilters = ['log.campaign_id', 'c.name', 'channel', 'channel_action', 'e.name'];
                if ($event->hasFilter($subsetFilters)) {
                    // Must use the same filters for determining the min of a given subset
                    $filters = $event->getReport()->getFilters();
                    foreach ($filters as $filter) {
                        if (in_array($filter['column'], $subsetFilters)) {
                            $filterParam = $event->createParameterName();
                            if (isset($filter['formula'])) {
                                $x = "({$filter['formula']}) as {$alias}_{$filter['column']}";
                            } else {
                                $x = $alias.$filter['column'];
                            }

                            $expr->add(
                                $expr->{$filter['operator']}($x, ":$filterParam")
                            );
                            $qb->setParameter($filterParam, $filter['value']);
                        }
                    }
                }

                $subQ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', "{$alias}log")
                    ->join("{$alias}log", MAUTIC_TABLE_PREFIX.'campaign_events', "{$alias}e", "{$alias}log.event_id = {$alias}e.id")
                    ->join("{$alias}e", MAUTIC_TABLE_PREFIX.'campaigns', "{$alias}c", "{$alias}e.campaign_id = {$alias}c.id")
                    ->where($expr);

                if ('multi' != $alias) {
                    // Get the min/max row and group by lead for first touch or last touch events
                    $func = ('first' == $alias) ? 'min' : 'max';
                    $subQ->select("$func({$alias}log.date_triggered)")
                        ->setMaxResults(1);
                    $qb->andWhere(
                        $qb->expr()->eq('log.date_triggered', sprintf('(%s)', $subQ->getSQL()))
                    )->groupBy('l.id');
                } else {
                    // Get the total count of records for this lead that match the filters to divide the attribution by
                    $subQ->select('count(*)')
                        ->groupBy("{$alias}log.lead_id");
                    $qb->addSelect(sprintf('(%s) activity_count', $subQ->getSQL()));
                }

                break;
            case self::CONTEXT_COMPANIES:
                $event->applyDateFilters($qb, 'date_added', 'comp');
                $qb->from(MAUTIC_TABLE_PREFIX.'companies', 'comp');

                if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
                    $qb->leftJoin('comp', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = comp.owner_id');
                }

                break;
        }

        if (!$event->checkContext(self::CONTEXT_COMPANIES) && $this->companyReportData->eventHasCompanyColumns($event)) {
            $event->addCompanyLeftJoin($qb);
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGraphEvent $event
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        if (!$event->checkContext([
            self::CONTEXT_LEADS,
            self::CONTEXT_LEAD_POINT_LOG,
            self::CONTEXT_CONTACT_ATTRIBUTION_MULTI,
            self::CONTEXT_COMPANIES,
        ])) {
            return;
        }

        $graphs       = $event->getRequestedGraphs();
        $qb           = $event->getQueryBuilder();
        $pointLogRepo = $this->leadModel->getPointLogRepository();
        $companyRepo  = $this->companyModel->getRepository();

        foreach ($graphs as $g) {
            $queryBuilder = clone $qb;
            $options      = $event->getOptions($g);
            /** @var ChartQuery $chartQuery */
            $chartQuery    = clone $options['chartQuery'];
            $attributionQb = clone $queryBuilder;

            $chartQuery->applyDateFilters($queryBuilder, 'date_added', 'l');

            if ($queryBuilder->getQueryPart('from')[0]['alias'] === 'lp') {
                $queryBuilder->resetQueryPart('join');
                $queryBuilder->leftJoin('lp', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lp.lead_id');
            }

            switch ($g) {
                case 'mautic.lead.graph.pie.attribution_stages':
                case 'mautic.lead.graph.pie.attribution_campaigns':
                case 'mautic.lead.graph.pie.attribution_actions':
                case 'mautic.lead.graph.pie.attribution_channels':
                    $attributionQb->resetQueryParts(['select', 'orderBy']);
                    $outerQb = clone $attributionQb;
                    $outerQb->resetQueryParts()
                        ->select('slice, sum(contact_attribution) as total_attribution')
                        ->groupBy('slice');

                    $groupBy = str_replace('mautic.lead.graph.pie.attribution_', '', $g);
                    switch ($groupBy) {
                        case 'stages':
                            $attributionQb->select('CONCAT_WS(\':\', s.id, s.name) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, s.id');
                            break;
                        case 'campaigns':
                            $attributionQb->select(
                                'CONCAT_WS(\':\', c.id, c.name) as slice, l.attribution as contact_attribution'
                            )
                                ->groupBy('l.id, c.id');
                            break;
                        case 'actions':
                            $attributionQb->select('SUBSTRING_INDEX(e.type, \'.\', -1) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, SUBSTRING_INDEX(e.type, \'.\', -1)');
                            break;
                        case 'channels':
                            $attributionQb->select('SUBSTRING_INDEX(e.type, \'.\', 1) as slice, l.attribution as contact_attribution')
                                ->groupBy('l.id, SUBSTRING_INDEX(e.type, \'.\', 1)');
                            break;
                    }

                    $outerQb->from(sprintf('(%s) subq', $attributionQb->getSQL()));
                    $outerQb->setParameters(
                        $attributionQb->getParameters()
                    );

                    $chart = new PieChart();
                    $data  = $outerQb->execute()->fetchAll();

                    foreach ($data as $row) {
                        switch ($groupBy) {
                            case 'actions':
                                $label = $this->channelActions[$row['slice']];
                                break;
                            case 'channels':
                                $label = $this->channels[$row['slice']];
                                break;

                            default:
                                $label = (empty($row['slice'])) ? $this->translator->trans('mautic.core.none') : $row['slice'];
                        }
                        $chart->setDataset($label, $row['total_attribution']);
                    }

                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa-dollar',
                        ]
                    );
                    break;

                case 'mautic.lead.graph.line.leads':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'l');
                    $leads = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.leads'), $leads);
                    $queryBuilder->andwhere($qb->expr()->isNotNull('l.date_identified'));
                    $identified = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.identified'), $identified);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;

                case 'mautic.lead.graph.line.points':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'lp');
                    $leads = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.graph.line.points'), $leads);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;

                case 'mautic.lead.table.most.points':
                    $queryBuilder->select('l.id, l.email as title, sum(lp.delta) as points')
                        ->groupBy('l.id, l.email')
                        ->orderBy('points', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-asterisk';
                    $graphData['link']      = 'mautic_contact_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.countries':
                    $queryBuilder->select('l.country as title, count(l.country) as quantity')
                        ->groupBy('l.country')
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-globe';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.cities':
                    $queryBuilder->select('l.city as title, count(l.city) as quantity')
                        ->groupBy('l.city')
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $pointLogRepo->getMostLeads($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-university';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.events':
                    $queryBuilder->select('lp.event_name as title, count(lp.event_name) as events')
                        ->groupBy('lp.event_name')
                        ->orderBy('events', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-calendar';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.top.actions':
                    $queryBuilder->select('lp.action_name as title, count(lp.action_name) as actions')
                        ->groupBy('lp.action_name')
                        ->orderBy('actions', 'DESC');
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $pointLogRepo->getMostPoints($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-bolt';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.lead.table.pie.company.country':
                    $counts       = $companyRepo->getCompaniesByGroup($queryBuilder, 'companycountry');
                    $chart        = new PieChart();
                    $companyCount = 0;
                    foreach ($counts as $count) {
                        if ($count['companycountry'] != '') {
                            $chart->setDataset($count['companycountry'], $count['companies']);
                        }
                        $companyCount += $count['companies'];
                    }
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companyCount);
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa fa-globe',
                        ]
                    );
                    break;
                case 'mautic.lead.graph.line.companies':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_added', 'comp');
                    $companies = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companies);
                    $data         = $chart->render();
                    $data['name'] = $g;
                    $event->setGraph($g, $data);
                    break;
                case 'mautic.lead.graph.pie.companies.industry':
                    $counts       = $companyRepo->getCompaniesByGroup($queryBuilder, 'companyindustry');
                    $chart        = new PieChart();
                    $companyCount = 0;
                    foreach ($counts as $count) {
                        if ($count['companyindustry'] != '') {
                            $chart->setDataset($count['companyindustry'], $count['companies']);
                        }
                        $companyCount += $count['companies'];
                    }
                    $chart->setDataset($options['translator']->trans('mautic.lead.all.companies'), $companyCount);
                    $event->setGraph(
                        $g,
                        [
                            'data'      => $chart->render(),
                            'name'      => $g,
                            'iconClass' => 'fa fa-industry',
                        ]
                    );
                    break;
                case 'mautic.lead.company.table.top.cities':
                    $queryBuilder->select('comp.companycity as title, count(comp.companycity) as quantity')
                        ->groupBy('comp.companycity')
                        ->andWhere(
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->isNotNull('comp.companycity'),
                                $queryBuilder->expr()->neq('comp.companycity', $queryBuilder->expr()->literal(''))
                            )
                        )
                        ->orderBy('quantity', 'DESC');
                    $limit  = 10;
                    $offset = 0;

                    $items                  = $companyRepo->getMostCompanies($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-building';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     * @param array              $filters
     */
    private function injectPointsReportData(ReportBuilderEvent $event, array $columns, array $filters)
    {
        $pointColumns = [
            'lp.id' => [
                'label' => 'mautic.lead.report.points.id',
                'type'  => 'int',
            ],
            'lp.type' => [
                'label' => 'mautic.lead.report.points.type',
                'type'  => 'string',
            ],
            'lp.event_name' => [
                'label' => 'mautic.lead.report.points.event_name',
                'type'  => 'string',
            ],
            'lp.action_name' => [
                'label' => 'mautic.lead.report.points.action_name',
                'type'  => 'string',
            ],
            'lp.delta' => [
                'label' => 'mautic.lead.report.points.delta',
                'type'  => 'int',
            ],
            'lp.date_added' => [
                'label'          => 'mautic.lead.report.points.date_added',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(lp.date_added)',
            ],
        ];
        $data = [
            'display_name' => 'mautic.lead.report.points.table',
            'columns'      => array_merge($columns, $pointColumns, $event->getIpColumn()),
            'filters'      => array_merge($filters, $pointColumns),
        ];
        $event->addTable(self::CONTEXT_LEAD_POINT_LOG, $data, self::GROUP_CONTACTS);

        // Register graphs
        $context = self::CONTEXT_LEAD_POINT_LOG;
        $event->addGraph($context, 'line', 'mautic.lead.graph.line.points')
            ->addGraph($context, 'table', 'mautic.lead.table.most.points')
            ->addGraph($context, 'table', 'mautic.lead.table.top.countries')
            ->addGraph($context, 'table', 'mautic.lead.table.top.cities')
            ->addGraph($context, 'table', 'mautic.lead.table.top.events')
            ->addGraph($context, 'table', 'mautic.lead.table.top.actions');
    }

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     * @param array              $filters
     */
    private function injectFrequencyReportData(ReportBuilderEvent $event, array $columns, array $filters)
    {
        $frequencyColumns = [
            'lf.frequency_number' => [
                'label' => 'mautic.lead.report.frequency.frequency_number',
                'type'  => 'int',
            ],
            'lf.frequency_time' => [
                'label' => 'mautic.lead.report.frequency.frequency_time',
                'type'  => 'string',
            ],
            'lf.channel' => [
                'label' => 'mautic.lead.report.frequency.channel',
                'type'  => 'string',
            ],
            'lf.preferred_channel' => [
                'label' => 'mautic.lead.report.frequency.preferred_channel',
                'type'  => 'boolean',
            ],
            'lf.pause_from_date' => [
                'label' => 'mautic.lead.report.frequency.pause_from_date',
                'type'  => 'datetime',
            ],
            'lf.pause_to_date' => [
                'label' => 'mautic.lead.report.frequency.pause_to_date',
                'type'  => 'datetime',
            ],
            'lf.date_added' => [
                'label'          => 'mautic.lead.report.frequency.date_added',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(lf.date_added)',
            ],
        ];
        $data = [
            'display_name' => 'mautic.lead.report.frequency.messages',
            'columns'      => array_merge($columns, $frequencyColumns),
            'filters'      => array_merge($filters, $frequencyColumns),
        ];
        $event->addTable(self::CONTEXT_CONTACT_FREQUENCYRULES, $data, self::GROUP_CONTACTS);
    }

    /**
     * @param ReportBuilderEvent $event
     * @param array              $columns
     * @param array              $filters
     * @param string             $type
     */
    private function injectAttributionReportData(ReportBuilderEvent $event, array $columns, array $filters, $type)
    {
        $attributionColumns = [
            'log.campaign_id' => [
                'label' => 'mautic.lead.report.attribution.campaign_id',
                'type'  => 'int',
                'link'  => 'mautic_campaign_action',
            ],
            'log.date_triggered' => [
                'label'          => 'mautic.lead.report.attribution.action_date',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(log.date_triggered)',
            ],
            'c.name' => [
                'alias' => 'campaign_name',
                'label' => 'mautic.lead.report.attribution.campaign_name',
                'type'  => 'string',
            ],
            'l.stage_id' => [
                'label' => 'mautic.lead.report.attribution.stage_id',
                'type'  => 'int',
                'link'  => 'mautic_stage_action',
            ],
            's.name' => [
                'alias' => 'stage_name',
                'label' => 'mautic.lead.report.attribution.stage_name',
                'type'  => 'string',
            ],
            'channel' => [
                'alias'   => 'channel',
                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', 1)',
                'label'   => 'mautic.lead.report.attribution.channel',
                'type'    => 'string',
            ],
            'channel_action' => [
                'alias'   => 'channel_action',
                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', -1)',
                'label'   => 'mautic.lead.report.attribution.channel_action',
                'type'    => 'string',
            ],
            'e.name' => [
                'alias' => 'action_name',
                'label' => 'mautic.lead.report.attribution.action_name',
                'type'  => 'string',
            ],
        ];

        $columns = array_merge($columns, $event->getCategoryColumns('cat.'), $attributionColumns);
        $filters = array_merge($filters, $event->getCategoryColumns('cat.'), $attributionColumns);

        // Setup available channels
        $availableChannels = $this->campaignModel->getEvents();
        $channels          = [];
        $channelActions    = [];
        foreach ($availableChannels['decision'] as $channel => $decision) {
            $parts                  = explode('.', $channel);
            $channelName            = $parts[0];
            $channels[$channelName] = $this->translator->hasId('mautic.channel.'.$channelName) ? $this->translator->trans(
                'mautic.channel.'.$channelName
            ) : ucfirst($channelName);
            unset($parts[0]);
            $actionValue = implode('.', $parts);

            if ($this->translator->hasId('mautic.channel.action.'.$channel)) {
                $actionName = $this->translator->trans('mautic.channel.action.'.$channel);
            } elseif ($this->translator->hasId('mautic.campaign.'.$channel)) {
                $actionName = $this->translator->trans('mautic.campaign.'.$channel);
            } else {
                $actionName = $channelName.': '.$actionValue;
            }
            $channelActions[$actionValue] = $actionName;
        }
        $filters['channel'] = [
            'label' => 'mautic.lead.report.attribution.channel',
            'type'  => 'select',
            'list'  => $channels,
        ];
        $filters['channel_action'] = [
            'label' => 'mautic.lead.report.attribution.channel_action',
            'type'  => 'select',
            'list'  => $channelActions,
        ];
        $this->channelActions = $channelActions;
        $this->channels       = $channels;
        unset($channelActions, $channels);

        // Setup available channels
        $campaigns                  = $this->campaignModel->getRepository()->getSimpleList();
        $filters['log.campaign_id'] = [
            'label' => 'mautic.lead.report.attribution.filter.campaign',
            'type'  => 'select',
            'list'  => $campaigns,
        ];
        unset($campaigns);

        // Setup stages list
        $userStages = $this->stageModel->getUserStages();
        $stages     = [];
        foreach ($userStages as $stage) {
            $stages[$stage['id']] = $stage['name'];
        }
        $filters['l.stage_id'] = [
            'label' => 'mautic.lead.report.attribution.filter.stage',
            'type'  => 'select',
            'list'  => $stages,
        ];
        unset($stages);

        $context = "contact.attribution.$type";
        $event
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_stages')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_campaigns')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_actions')
            ->addGraph($context, 'pie', 'mautic.lead.graph.pie.attribution_channels');

        $data = [
            'display_name' => 'mautic.lead.report.attribution.'.$type,
            'columns'      => $columns,
            'filters'      => $filters,
        ];

        $event->addTable($context, $data, self::GROUP_CONTACTS);
    }

    /**
     * @param ReportDataEvent $event
     */
    public function onReportDisplay(ReportDataEvent $event)
    {
        $data = $event->getData();

        if ($event->checkContext([
            self::CONTEXT_CONTACT_ATTRIBUTION_FIRST,
            self::CONTEXT_CONTACT_ATTRIBUTION_LAST,
            self::CONTEXT_CONTACT_ATTRIBUTION_MULTI,
            self::CONTEXT_CONTACT_MESSAGE_FREQUENCY,
        ])) {
            if (isset($data[0]['channel']) || isset($data[0]['channel_action']) || (isset($data[0]['activity_count']) && isset($data[0]['attribution']))) {
                foreach ($data as $key => &$row) {
                    if (isset($row['channel'])) {
                        $row['channel'] = $this->channels[$row['channel']];
                    }

                    if (isset($row['channel_action'])) {
                        $row['channel_action'] = $this->channelActions[$row['channel_action']];
                    }

                    if (isset($row['activity_count']) && isset($row['attribution'])) {
                        $row['attribution'] = round($row['attribution'] / $row['activity_count'], 2);
                    }

                    if (isset($row['attribution'])) {
                        $row['attribution'] = number_format($row['attribution'], 2);
                    }

                    unset($row);
                }
            }
        }

        $event->setData($data);
        unset($data);
    }
}

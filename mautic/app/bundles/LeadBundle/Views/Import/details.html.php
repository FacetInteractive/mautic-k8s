<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set('headerTitle', $item->getName());
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item])
);
$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'routeBase'       => 'import',
            'langVar'         => 'lead.import',
            'templateButtons' => [
                'close' => $view['security']->hasEntityAccess(
                    $permissions['lead:imports:viewown'],
                    $permissions['lead:imports:viewother'],
                    $item->getCreatedBy()
                ),
            ],
            'routeVars' => [
                'close' => [
                    'object' => $app->getRequest()->get('object', 'contacts'),
                ],
            ],
        ]
    )
);
$detailRowTmpl = 'MauticCoreBundle:Helper:detail_row.html.php';

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- asset detail collapseable -->
            <div class="collapse" id="asset-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', ['entity' => $item]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.source.file',
                                    'value' => $item->getOriginalFile(),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.status',
                                    'value' => $view->render('MauticCoreBundle:Helper:label.html.php', [
                                        'text' => 'mautic.lead.import.status.'.$item->getStatus(),
                                        'type' => $item->getSatusLabelClass(),
                                    ]),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.status.info',
                                    'value' => $item->getStatusInfo(),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.line.count',
                                    'value' => $item->getLineCount(),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.date.started',
                                    'value' => $view['date']->toFull($item->getDateStarted()),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.date.ended',
                                    'value' => $view['date']->toFull($item->getDateEnded()),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.runtime',
                                    'value' => $item->getRunTime() ? $view['date']->formatRange($item->getRunTime()) : '',
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.speed',
                                    'value' => $view['translator']->trans('mautic.lead.import.speed.value', ['%speed%' => $item->getSpeed()]),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.progress',
                                    'value' => $item->getProgressPercentage().'%',
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.mapped.fields',
                                    'value' => $view['formatter']->arrayToString($item->getMatchedFields()),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.default.options',
                                    'value' => $view['formatter']->arrayToString($item->getDefaults()),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.csv.headers',
                                    'value' => $view['formatter']->arrayToString($item->getHeaders()),
                                ]); ?>
                                <?php echo $view->render($detailRowTmpl, [
                                    'label' => 'mautic.lead.import.csv.parser.config',
                                    'value' => $view['formatter']->arrayToString($item->getParserConfig()),
                                ]); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ asset detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- asset detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#asset-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ asset detail collapseable toggler -->

            <?php if ($item->getDateStarted()) : ?>
            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-row-statuses"></span>
                                        <?php echo $view['translator']->trans('mautic.lead.import.row.statuses'); ?>
                                    </h5>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:chart.html.php',
                                    [
                                        'chartData'   => $item->getRowStatusesPieChart($view['translator']),
                                        'chartType'   => 'pie',
                                        'chartHeight' => 210,
                                    ]
                                ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-row-statuses"></span>
                                        <?php echo $view['translator']->trans('mautic.lead.import.processed.rows.minute'); ?>
                                    </h5>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:chart.html.php',
                                    [
                                        'chartData'   => $importedRowsChart,
                                        'chartType'   => 'line',
                                        'chartHeight' => 210,
                                    ]
                                ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->
            <?php endif; ?>
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md preview-detail">
        <?php if (!empty($failedRows) && count($failedRows)) : ?>
        <h3><?php echo $view['translator']->trans('mautic.lead.import.failed.rows'); ?></h3>
            <table class="table">
                <thead>
                    <tr>
                    <?php foreach (['mautic.lead.import.csv.line.number', 'mautic.core.error.message'] as $headItem) : ?>
                        <th><?php echo $view['translator']->trans($headItem); ?></th>
                    <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failedRows as $row) : ?>
                        <?php if (is_array($row['properties'])) : ?>
                            <tr>
                                <td>
                                    <?php echo $row['properties']['line']; ?>
                                </td>
                                <td>
                                    <?php
                                    $error = 'N/A';
                                    if (isset($row['properties']['error'])):
                                        $error = $row['properties']['error'];
                                        if (preg_match('/SQLSTATE\[\w+\]: (.*)/', $error, $matches)):
                                            $error = $matches[1];
                                        endif;
                                    endif;
                                    echo $error;
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <i><?php echo $view['translator']->trans('mautic.lead.import.no.failed.rows'); ?></i>
        <?php endif; ?>
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $view->escape($item->getId()); ?>"/>
</div>
<!--/ end: box layout -->

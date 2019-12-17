<?php if (!empty($trackables)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered click-list">
            <thead>
            <tr>
                <td><?php echo $view['translator']->trans('mautic.trackable.click_url'); ?></td>
                <td><?php echo $view['translator']->trans('mautic.trackable.click_count'); ?></td>
                <td><?php echo $view['translator']->trans('mautic.trackable.click_unique_count'); ?></td>
                <td><?php echo $view['translator']->trans('mautic.trackable.click_track_id'); ?></td>
            </tr>
            </thead>
            <tbody>
                <?php 
                    $totalClicks       = 0;
                    $totalUniqueClicks = 0;
                    foreach ($trackables as $link):
                        $totalClicks += $link['hits'];
                        $totalUniqueClicks += $link['unique_hits'];
                        ?>
                        <tr>
                            <td class="long-text"><a href="<?php echo $link['url']; ?>"><?php echo $link['url']; ?></a></td>
                            <td class="text-center"><?php echo $link['hits']; ?></td>
                            <td class="text-center"><?php echo $link['unique_hits']; ?></td>
                            <td><?php echo $link['redirect_id']; ?></td>
                        </tr>
                <?php endforeach; ?>

                <tr>
                    <td class="long-text"><?php echo $view['translator']->trans('mautic.trackable.total_clicks'); ?></td>
                    <td class="text-center"><?php echo $totalClicks; ?></td>
                    <td class="text-center"><?php echo $totalUniqueClicks; ?></td>
                    <td></td>
                </tr>

            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php',
        [
            'header'  => 'mautic.trackable.click_counts.header_none',
            'message' => 'mautic.trackable.click_counts.none',
        ]
    ); ?>
    <div class="clearfix"></div>
<?php endif; ?>
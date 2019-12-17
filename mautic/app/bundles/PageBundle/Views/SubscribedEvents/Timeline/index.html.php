<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$timeOnPage = $view['translator']->trans('mautic.core.unknown');
if ($event['extra']['hit']['dateLeft']) {
    $timeOnPage = ($event['extra']['hit']['dateLeft']->getTimestamp() - $event['extra']['hit']['dateHit']->getTimestamp());

    // format the time
    if ($timeOnPage > 60) {
        $sec        = $timeOnPage % 60;
        $min        = floor($timeOnPage / 60);
        $timeOnPage = $min.'m '.$sec.'s';
    } else {
        $timeOnPage .= 's';
    }
}

$query = $event['extra']['hit']['query'];
?>

<?php if (isset($event['extra'])) : ?>
<dl class="dl-horizontal">
    <dt><?php echo $view['translator']->trans('mautic.page.time.on.page'); ?>:</dt>
    <dd><?php echo $timeOnPage; ?></dd>
    <dt><?php echo $view['translator']->trans('mautic.page.referrer'); ?>:</dt>
    <dd><?php echo $event['extra']['hit']['referer'] ? $view['assets']->makeLinks($event['extra']['hit']['referer']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
    <dt><?php echo $view['translator']->trans('mautic.page.url'); ?>:</dt>
    <dd><?php echo $event['extra']['hit']['url'] ? $view['assets']->makeLinks($event['extra']['hit']['url']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>

    <?php if (isset($event['extra']['hit']['device']) and !empty($event['extra']['hit']['device'])): ?>
        <dt><?php echo $view['translator']->trans('mautic.core.timeline.device.name'); ?></dt>
        <dd class="ellipsis">
            <?php echo $event['extra']['hit']['device']; ?>
        </dd>
    <?php endif; ?>

    <dt><?php echo $view['translator']->trans('mautic.core.timeline.device.os'); ?></dt>

    <?php if (!empty($event['extra']['hit']['deviceOsName'])): ?>
        <dd class="ellipsis">
            <?php echo $event['extra']['hit']['deviceOsName']; ?>
        </dd>
    <?php endif; ?>

    <?php if (!empty($event['extra']['hit']['deviceBrand'])): ?>
        <dt><?php echo $view['translator']->trans('mautic.core.timeline.device.brand'); ?></dt>
        <dd class="ellipsis">
            <?php echo $event['extra']['hit']['deviceBrand']; ?>
        </dd>
    <?php endif; ?>

    <?php if (!empty($event['extra']['hit']['deviceModel'])): ?>
        <dt><?php echo $view['translator']->trans('mautic.core.timeline.device.model'); ?></dt>
        <dd class="ellipsis">
            <?php echo $event['extra']['hit']['deviceModel']; ?>
        </dd>
    <?php endif; ?>

    <?php if (isset($event['extra']['hit']['sourceName'])): ?>

        <dt><?php echo $view['translator']->trans('mautic.core.source'); ?>:</dt>
        <dd>
            <?php if (isset($event['extra']['hit']['sourceRoute'])): ?>
            <a href="<?php echo $event['extra']['hit']['sourceRoute']; ?>" data-toggle="ajax">
                <?php echo $event['extra']['hit']['sourceName']; ?>
            </a>
            <?php else: ?>
            <?php echo $event['extra']['hit']['sourceName']; ?>
            <?php endif; ?>
        </dd>

        <?php if (!empty($event['extra']['hit']['clientInfo']) && is_array($event['extra']['hit']['clientInfo'])): ?>
            <dt><?php echo $view['translator']->trans('mautic.core.timeline.device.client.info'); ?></dt>
            <dd class="ellipsis">
                <?php foreach ($event['extra']['hit']['clientInfo'] as $clientInfo) : ?>
                    <?php echo $clientInfo; ?>
                <?php endforeach; ?>
            </dd>
        <?php endif; ?>

    <?php endif; ?>
</dl>
<div class="small">
    <?php echo $event['extra']['hit']['userAgent']; ?>
</div>
<?php endif; ?>

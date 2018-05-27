<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$isPrototype = ($form->vars['name'] == '__name__');
$filterType  = $form['field']->vars['value'];
$filterGroup = $form['object']->vars['value'];
$inGroup     = $form->vars['data']['glue'] === 'and';
$objectIcon  = (isset($form->vars['data']['object']) && $form->vars['data']['object'] == 'company') ? 'fa-building' : 'fa-user';
?>

<div class="panel<?php echo ($isPrototype || ($inGroup && !$first)) ? ' in-group' : ''; ?>">
    <div class="panel-footer<?php if (!$isPrototype && $form->vars['name'] === '0') {
    echo ' hide';
} ?>">
        <div class="col-sm-2 pl-0">
            <?php echo $view['form']->widget($form['glue']); ?>
        </div>
    </div>
    <div class="panel-body">
        <div class="col-xs-6 col-sm-3 field-name">
            <i class="object-icon fa <?php echo $objectIcon; ?>"></i> <span><?php echo ($isPrototype) ? '__label__' : $form->parent->parent->vars['fields'][$filterGroup][$filterType]['label']; ?></span>
        </div>

        <div class="col-xs-6 col-sm-3 padding-none">
            <?php echo $view['form']->widget($form['operator']); ?>
        </div>

        <?php $hasErrors = count($form['filter']->vars['errors']) || count($form['display']->vars['errors']); ?>
        <div class="col-xs-10 col-sm-5 padding-none<?php if ($hasErrors) {
    echo ' has-error';
} ?>">
            <?php echo $view['form']->widget($form['filter']); ?>
            <?php echo $view['form']->errors($form['filter']); ?>
            <?php echo $view['form']->widget($form['display']); ?>
            <?php echo $view['form']->errors($form['display']); ?>
        </div>

        <div class="col-xs-2 col-sm-1">
            <a href="javascript: void(0);" class="remove-selected btn btn-default text-danger pull-right"><i class="fa fa-trash-o"></i></a>
        </div>
        <?php echo $view['form']->widget($form['field']); ?>
        <?php echo $view['form']->widget($form['type']); ?>
        <?php echo $view['form']->widget($form['object']); ?>
    </div>
</div>
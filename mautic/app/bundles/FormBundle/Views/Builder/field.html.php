<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$template       = '<div class="col-md-6">{content}</div>';
$toggleTemplate = '<div class="col-md-3">{content}</div>';
$properties     = (isset($form['properties'])) ? $form['properties'] : [];

$showAttributes = isset($form['labelAttributes']) || isset($form['inputAttributes']) || isset($form['containerAttributes']) || isset($properties['labelAttributes']) || isset($form['alias']);
$showBehavior   = isset($form['showWhenValueExists']) || isset($properties['showWhenValueExists']);

$placeholder = '';
if (isset($properties['placeholder'])):
    $placeholder = $view['form']->rowIfExists($properties, 'placeholder', $template);
    unset($properties['placeholder']);
    unset($form['properties']['placeholder']);
endif;

$customAttributes = '';
if (isset($properties['labelAttributes'])):
    $customAttributes = $view['form']->rowIfExists($properties, 'labelAttributes', $template);
    unset($properties['labelAttributes']);
    unset($form['properties']['labelAttributes']);
endif;

$showProperties = false;
if (isset($form['properties']) && count($form['properties'])):
    // Only show if there is at least one non-hidden field
    foreach ($form['properties'] as $property):
        if ('hidden' != $property->vars['block_prefixes'][1]):
            $showProperties = true;
            break;
        endif;
    endforeach;
endif;

// Check for validation errors to show on tabs
$generalTabError    = (isset($form['label']) && ($view['form']->containsErrors($form['label'])));
$propertiesTabError = (isset($form['properties']) && ($view['form']->containsErrors($form['properties'])));
?>


<div class="bundle-form">
    <div class="bundle-form-header">
        <h3 class="mb-lg"><?php echo $fieldHeader; ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>

    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a<?php if ($generalTabError) {
    echo ' class="text-danger" ';
} ?> href="#general" aria-controls="general" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.general'); ?>
                    <?php if ($generalTabError): ?>
                        <i class="fa fa-warning"></i>
                    <?php endif; ?>
                </a>
            </li>

            <?php if (isset($form['leadField'])): ?>
            <li role="presentation">
                <a href="#leadfields" aria-controls="leadfields" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.leadfield'); ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if (isset($form['isRequired'])): ?>
            <li role="presentation">
                <a href="#required" aria-controls="required" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.validation'); ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showProperties): ?>
            <li role="presentation">
                <a<?php if ($propertiesTabError) {
    echo ' class="text-danger" ';
} ?> href="#properties" aria-controls="properties" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.properties'); ?>
                    <?php if ($propertiesTabError): ?>
                        <i class="fa fa-warning"></i>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showAttributes): ?>
            <li role="presentation">
                <a href="#attributes" aria-controls="attributes" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.attributes'); ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showBehavior): ?>
            <li role="progressive-profiling">
                <a href="#progressive-profiling" aria-controls="progressive-profiling" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.form.field.section.progressive.profiling'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content pa-lg">
            <div role="tabpanel" class="tab-pane active" id="general">
                <div class="row">
                    <?php echo $view['form']->rowIfExists($form, 'label', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'showLabel', $toggleTemplate); ?>
                    <?php echo $view['form']->rowIfExists($form, 'saveResult', $toggleTemplate); ?>
                    <?php echo $view['form']->rowIfExists($form, 'defaultValue', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'helpMessage', $template); ?>
                    <?php echo $placeholder; ?>
                </div>
            </div>

            <?php if (isset($form['leadField'])): ?>
            <div role="tabpanel" class="tab-pane" id="leadfields">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['leadField']); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($form['isRequired'])): ?>
            <div role="tabpanel" class="tab-pane" id="required">
                    <div class="row">
                        <?php echo $view['form']->rowIfExists($form, 'validationMessage', $template); ?>
                        <?php echo $view['form']->rowIfExists($form, 'isRequired', $toggleTemplate); ?>
                    </div>
            </div>
            <?php endif; ?>

            <?php if ($showProperties): ?>
            <div role="tabpanel" class="tab-pane" id="properties">
                <?php echo $view['form']->errors($form['properties']); ?>
                <?php if (isset($properties['syncList'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <?php echo $view['form']->row($form['properties']['syncList']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (isset($properties['list'])): ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $view['form']->row($form['properties']['list']); ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($properties['optionlist'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <?php echo $view['form']->row($form['properties']['optionlist']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <?php
                    foreach ($properties as $name => $property):
                    if ($form['properties'][$name]->isRendered() || $name == 'labelAttributes') {
                        continue;
                    }

                    if ($form['properties'][$name]->vars['block_prefixes'][1] == 'hidden') :
                        echo $view['form']->row($form['properties'][$name]);
                    else:
                    $col = ($name == 'text') ? 12 : 6;
                    ?>
                    <div class="col-md-<?php echo $col; ?>">
                        <?php echo $view['form']->row($form['properties'][$name]); ?>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>


            <?php if ($showAttributes): ?>
            <div role="tabpanel" class="tab-pane" id="attributes">
                <div class="row">
                    <?php echo $view['form']->rowIfExists($form, 'alias', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'labelAttributes', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'inputAttributes', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'containerAttributes', $template); ?>
                    <?php echo $customAttributes; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showBehavior): ?>
            <div role="tabpanel" class="tab-pane" id="progressive-profiling">
                <div class="row">
                    <?php echo $view['form']->rowIfExists($form, 'showWhenValueExists', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'showAfterXSubmissions', $template); ?>
                    <?php echo $view['form']->rowIfExists($form, 'isAutoFill', $template); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $view['form']->end($form); ?>
</div>

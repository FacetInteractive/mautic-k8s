<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\FormBundle\EventListener\CalendarSubscriber;
use Mautic\FormBundle\EventListener\CampaignSubscriber;
use Mautic\FormBundle\EventListener\DashboardSubscriber;
use Mautic\FormBundle\EventListener\EmailSubscriber;
use Mautic\FormBundle\EventListener\FormSubscriber;
use Mautic\FormBundle\EventListener\LeadSubscriber;
use Mautic\FormBundle\EventListener\PageSubscriber;
use Mautic\FormBundle\EventListener\PointSubscriber;
use Mautic\FormBundle\EventListener\ReportSubscriber;
use Mautic\FormBundle\EventListener\SearchSubscriber;
use Mautic\FormBundle\EventListener\WebhookSubscriber;
use Mautic\FormBundle\Form\Type\ActionType;
use Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType;
use Mautic\FormBundle\Form\Type\CampaignEventFormSubmitType;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\Form\Type\FormFieldCaptchaType;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\Form\Type\FormFieldGroupType;
use Mautic\FormBundle\Form\Type\FormFieldHTMLType;
use Mautic\FormBundle\Form\Type\FormFieldPageBreakType;
use Mautic\FormBundle\Form\Type\FormFieldPlaceholderType;
use Mautic\FormBundle\Form\Type\FormFieldSelectType;
use Mautic\FormBundle\Form\Type\FormFieldTextType;
use Mautic\FormBundle\Form\Type\FormListType;
use Mautic\FormBundle\Form\Type\FormType;
use Mautic\FormBundle\Form\Type\PointActionFormSubmitType;
use Mautic\FormBundle\Form\Type\SubmitActionEmailType;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Helper\TokenHelper;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Model\SubmissionResultLoader;
use Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator;
use Mautic\FormBundle\Validator\UploadFieldValidator;

return [
    'routes' => [
        'main' => [
            'mautic_formaction_action' => [
                'path'       => '/forms/action/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Action:execute',
            ],
            'mautic_formfield_action' => [
                'path'       => '/forms/field/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Field:execute',
            ],
            'mautic_form_index' => [
                'path'       => '/forms/{page}',
                'controller' => 'MauticFormBundle:Form:index',
            ],
            'mautic_form_results' => [
                'path'       => '/forms/results/{objectId}/{page}',
                'controller' => 'MauticFormBundle:Result:index',
            ],
            'mautic_form_export' => [
                'path'       => '/forms/results/{objectId}/export/{format}',
                'controller' => 'MauticFormBundle:Result:export',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_form_results_action' => [
                'path'       => '/forms/results/{formId}/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Result:execute',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
            'mautic_form_action' => [
                'path'       => '/forms/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Form:execute',
            ],
        ],
        'api' => [
            'mautic_api_formstandard' => [
                'standard_entity' => true,
                'name'            => 'forms',
                'path'            => '/forms',
                'controller'      => 'MauticFormBundle:Api\FormApi',
            ],
            'mautic_api_formresults' => [
                'path'       => '/forms/{formId}/submissions',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntities',
            ],
            'mautic_api_formresult' => [
                'path'       => '/forms/{formId}/submissions/{submissionId}',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntity',
            ],
            'mautic_api_contactformresults' => [
                'path'       => '/forms/{formId}/submissions/contact/{contactId}',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntitiesForContact',
            ],
            'mautic_api_formdeletefields' => [
                'path'       => '/forms/{formId}/fields/delete',
                'controller' => 'MauticFormBundle:Api\FormApi:deleteFields',
                'method'     => 'DELETE',
            ],
            'mautic_api_formdeleteactions' => [
                'path'       => '/forms/{formId}/actions/delete',
                'controller' => 'MauticFormBundle:Api\FormApi:deleteActions',
                'method'     => 'DELETE',
            ],
        ],
        'public' => [
            'mautic_form_file_download' => [
                'path'       => '/forms/results/file/{submissionId}/{field}',
                'controller' => 'MauticFormBundle:Result:downloadFile',
            ],
            'mautic_form_postresults' => [
                'path'       => '/form/submit',
                'controller' => 'MauticFormBundle:Public:submit',
            ],
            'mautic_form_generateform' => [
                'path'       => '/form/generate.js',
                'controller' => 'MauticFormBundle:Public:generate',
            ],
            'mautic_form_postmessage' => [
                'path'       => '/form/message',
                'controller' => 'MauticFormBundle:Public:message',
            ],
            'mautic_form_preview' => [
                'path'       => '/form/{id}',
                'controller' => 'MauticFormBundle:Public:preview',
                'defaults'   => [
                    'id' => '0',
                ],
            ],
            'mautic_form_embed' => [
                'path'       => '/form/embed/{id}',
                'controller' => 'MauticFormBundle:Public:embed',
            ],
            'mautic_form_postresults_ajax' => [
                'path'       => '/form/submit/ajax',
                'controller' => 'MauticFormBundle:Ajax:submit',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'items' => [
                'mautic.form.forms' => [
                    'route'    => 'mautic_form_index',
                    'access'   => ['form:forms:viewown', 'form:forms:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 200,
                ],
            ],
        ],
    ],

    'categories' => [
        'form' => null,
    ],

    'services' => [
        'events' => [
            'mautic.form.subscriber' => [
                'class'     => FormSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.helper.mailer',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.validation.subscriber' => [
                'class'     => \Mautic\FormBundle\EventListener\FormValidationSubscriber::class,
            ],
            'mautic.form.pagebundle.subscriber' => [
                'class'     => PageSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.pointbundle.subscriber' => [
                'class'     => PointSubscriber::class,
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.form.reportbundle.subscriber' => [
                'class'     => ReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.company_report_data',
                ],
            ],
            'mautic.form.campaignbundle.subscriber' => [
                'class'     => CampaignSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'mautic.campaign.model.event',
                ],
            ],
            'mautic.form.calendarbundle.subscriber' => [
                'class' => CalendarSubscriber::class,
            ],
            'mautic.form.leadbundle.subscriber' => [
                'class'     => LeadSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                ],
            ],
            'mautic.form.emailbundle.subscriber' => [
                'class' => EmailSubscriber::class,
            ],
            'mautic.form.search.subscriber' => [
                'class'     => SearchSubscriber::class,
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.webhook.subscriber' => [
                'class'       => WebhookSubscriber::class,
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
            'mautic.form.dashboard.subscriber' => [
                'class'     => DashboardSubscriber::class,
                'arguments' => [
                    'mautic.form.model.submission',
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.stats.subscriber' => [
                'class'     => \Mautic\FormBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.form' => [
                'class'     => FormType::class,
                'arguments' => 'mautic.factory',
                'alias'     => 'mauticform',
            ],
            'mautic.form.type.field' => [
                'class'       => FieldType::class,
                'alias'       => 'formfield',
                'arguments'   => [
                    'translator',
                ],
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.action' => [
                'class' => ActionType::class,
                'alias' => 'formaction',
            ],
            'mautic.form.type.field_propertytext' => [
                'class' => FormFieldTextType::class,
                'alias' => 'formfield_text',
            ],
            'mautic.form.type.field_propertyhtml' => [
                'class' => FormFieldHTMLType::class,
                'alias' => 'formfield_html',
            ],
            'mautic.form.type.field_propertyplaceholder' => [
                'class' => FormFieldPlaceholderType::class,
                'alias' => 'formfield_placeholder',
            ],
            'mautic.form.type.field_propertyselect' => [
                'class' => FormFieldSelectType::class,
                'alias' => 'formfield_select',
            ],
            'mautic.form.type.field_propertycaptcha' => [
                'class' => FormFieldCaptchaType::class,
                'alias' => 'formfield_captcha',
            ],
            'mautic.form.type.field_propertypagebreak' => [
                'class'     => FormFieldPageBreakType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertytel' => [
                'class'     => \Mautic\FormBundle\Form\Type\FormFieldTelType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertyfile' => [
                'class'     => FormFieldFileType::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertygroup' => [
                'class' => FormFieldGroupType::class,
                'alias' => 'formfield_group',
            ],
            'mautic.form.type.pointaction_formsubmit' => [
                'class' => PointActionFormSubmitType::class,
                'alias' => 'pointaction_formsubmit',
            ],
            'mautic.form.type.form_list' => [
                'class'     => FormListType::class,
                'arguments' => 'mautic.factory',
                'alias'     => 'form_list',
            ],
            'mautic.form.type.campaignevent_formsubmit' => [
                'class' => CampaignEventFormSubmitType::class,
                'alias' => 'campaignevent_formsubmit',
            ],
            'mautic.form.type.campaignevent_form_field_value' => [
                'class'     => CampaignEventFormFieldValueType::class,
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignevent_form_field_value',
            ],
            'mautic.form.type.form_submitaction_sendemail' => [
                'class'       => SubmitActionEmailType::class,
                'arguments'   => [
                    'translator',
                    'mautic.helper.core_parameters',
                ],
                'alias'       => 'form_submitaction_sendemail',
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.form_submitaction_repost' => [
                'class'       => \Mautic\FormBundle\Form\Type\SubmitActionRepostType::class,
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
        ],
        'models' => [
            'mautic.form.model.action' => [
                'class' => ActionModel::class,
            ],
            'mautic.form.model.field' => [
                'class'     => FieldModel::class,
                'arguments' => [
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.form.model.form' => [
                'class'     => FormModel::class,
                'arguments' => [
                    'request_stack',
                    'mautic.helper.templating',
                    'mautic.helper.theme',
                    'mautic.schema.helper.factory',
                    'mautic.form.model.action',
                    'mautic.form.model.field',
                    'mautic.lead.model.lead',
                    'mautic.helper.form.field_helper',
                    'mautic.lead.model.field',
                    'mautic.form.helper.form_uploader',
                    'mautic.form.model.submission_result_loader',
                ],
            ],
            'mautic.form.model.submission' => [
                'class'     => SubmissionModel::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.templating',
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.field',
                    'mautic.lead.model.company',
                    'mautic.helper.form.field_helper',
                    'mautic.form.validator.upload_field_validator',
                    'mautic.form.helper.form_uploader',
                    'mautic.lead.service.device_tracking_service',
                    'mautic.form.service.field.value.transformer',
                    'mautic.helper.template.date',
                ],
            ],
            'mautic.form.model.submission_result_loader' => [
                'class'     => SubmissionResultLoader::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'other' => [
            'mautic.helper.form.field_helper' => [
                'class'     => FormFieldHelper::class,
                'arguments' => [
                    'translator',
                    'validator',
                ],
            ],
            'mautic.form.helper.form_uploader' => [
                'class'     => FormUploader::class,
                'arguments' => [
                    'mautic.helper.file_uploader',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.helper.token' => [
                'class'     => TokenHelper::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.security',
                ],
            ],
            'mautic.form.service.field.value.transformer' => [
                'class'     => \Mautic\FormBundle\Event\Service\FieldValueTransformer::class,
                'arguments' => [
                    'router',
                ],
            ],
        ],
        'validator' => [
            'mautic.form.validator.upload_field_validator' => [
                'class'     => UploadFieldValidator::class,
                'arguments' => [
                    'mautic.core.validator.file_upload',
                ],
            ],
            'mautic.form.validator.constraint.file_extension_constraint_validator' => [
                'class'     => FileExtensionConstraintValidator::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
                'tags' => [
                    'name'  => 'validator.constraint_validator',
                    'alias' => 'file_extension_constraint_validator',
                ],
            ],
        ],
    ],

    'parameters' => [
        'form_upload_dir'        => '%kernel.root_dir%/../media/files/form',
        'blacklisted_extensions' => ['php', 'sh'],
    ],
];

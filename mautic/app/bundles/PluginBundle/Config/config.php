<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_integration_auth_callback_secure' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback',
            ],
            'mautic_integration_auth_postauth_secure' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus',
            ],
            'mautic_plugin_index' => [
                'path'       => '/plugins',
                'controller' => 'MauticPluginBundle:Plugin:index',
            ],
            'mautic_plugin_config' => [
                'path'       => '/plugins/config/{name}/{page}',
                'controller' => 'MauticPluginBundle:Plugin:config',
            ],
            'mautic_plugin_info' => [
                'path'       => '/plugins/info/{name}',
                'controller' => 'MauticPluginBundle:Plugin:info',
            ],
            'mautic_plugin_reload' => [
                'path'       => '/plugins/reload',
                'controller' => 'MauticPluginBundle:Plugin:reload',
            ],
        ],
        'public' => [
            'mautic_integration_auth_user' => [
                'path'       => '/plugins/integrations/authuser/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authUser',
            ],
            'mautic_integration_auth_callback' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback',
            ],
            'mautic_integration_auth_postauth' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus',
            ],
        ],
    ],
    'menu' => [
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.plugin.plugins' => [
                    'id'        => 'mautic_plugin_root',
                    'iconClass' => 'fa-plus-circle',
                    'access'    => 'plugin:plugins:manage',
                    'route'     => 'mautic_plugin_index',
                ],
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.pointbundle.subscriber' => [
                'class' => 'Mautic\PluginBundle\EventListener\PointSubscriber',
            ],
            'mautic.plugin.formbundle.subscriber' => [
                'class'       => 'Mautic\PluginBundle\EventListener\FormSubscriber',
                'methodCalls' => [
                    'setIntegrationHelper' => [
                        'mautic.helper.integration',
                    ],
                ],
            ],
            'mautic.plugin.campaignbundle.subscriber' => [
                'class'       => 'Mautic\PluginBundle\EventListener\CampaignSubscriber',
                'methodCalls' => [
                    'setIntegrationHelper' => [
                        'mautic.helper.integration',
                    ],
                ],
            ],
            'mautic.plugin.leadbundle.subscriber' => [
                'class'     => 'Mautic\PluginBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.plugin.model.plugin',
                ],
            ],
            'mautic.plugin.integration.subscriber' => [
                'class' => 'Mautic\PluginBundle\EventListener\IntegrationSubscriber',
            ],
        ],
        'forms' => [
            'mautic.form.type.integration.details' => [
                'class' => 'Mautic\PluginBundle\Form\Type\DetailsType',
                'alias' => 'integration_details',
            ],
            'mautic.form.type.integration.settings' => [
                'class'     => 'Mautic\PluginBundle\Form\Type\FeatureSettingsType',
                'arguments' => [
                    'session',
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                ],
                'alias' => 'integration_featuresettings',
            ],
            'mautic.form.type.integration.fields' => [
                'class'     => 'Mautic\PluginBundle\Form\Type\FieldsType',
                'alias'     => 'integration_fields',
                'arguments' => 'translator',
            ],
            'mautic.form.type.integration.company.fields' => [
                'class'     => 'Mautic\PluginBundle\Form\Type\CompanyFieldsType',
                'alias'     => 'integration_company_fields',
                'arguments' => 'translator',
            ],
            'mautic.form.type.integration.keys' => [
                'class' => 'Mautic\PluginBundle\Form\Type\KeysType',
                'alias' => 'integration_keys',
            ],
            'mautic.form.type.integration.list' => [
                'class'     => 'Mautic\PluginBundle\Form\Type\IntegrationsListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'integration_list',
            ],
            'mautic.form.type.integration.config' => [
                'class' => 'Mautic\PluginBundle\Form\Type\IntegrationConfigType',
                'alias' => 'integration_config',
            ],
            'mautic.form.type.integration.campaign' => [
                'class' => 'Mautic\PluginBundle\Form\Type\IntegrationCampaignsType',
                'alias' => 'integration_campaign_status',
            ],
        ],
        'other' => [
            'mautic.helper.integration' => [
                'class'     => \Mautic\PluginBundle\Helper\IntegrationHelper::class,
                'arguments' => [
                    'kernel',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.paths',
                    'mautic.helper.bundle',
                    'mautic.helper.core_parameters',
                    'mautic.helper.templating',
                    'mautic.plugin.model.plugin',
                ],
            ],
        ],
        'models' => [
            'mautic.plugin.model.plugin' => [
                'class'     => 'Mautic\PluginBundle\Model\PluginModel',
                'arguments' => [
                    'mautic.lead.model.field',
                ],
            ],

            'mautic.plugin.model.integration_entity' => [
                'class' => Mautic\PluginBundle\Model\IntegrationEntityModel::class,
            ],
        ],
    ],
];

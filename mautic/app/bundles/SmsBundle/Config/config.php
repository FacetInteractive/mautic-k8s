<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'services' => [
        'events' => [
            'mautic.sms.campaignbundle.subscriber' => [
                'class'     => 'Mautic\SmsBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.sms.model.sms',
                    'mautic.sms.transport_chain',
                ],
            ],
            'mautic.sms.smsbundle.subscriber' => [
                'class'     => 'Mautic\SmsBundle\EventListener\SmsSubscriber',
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.helper.sms',
                ],
            ],
            'mautic.sms.channel.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.sms.transport_chain',
                ],
            ],
            'mautic.sms.message_queue.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\MessageQueueSubscriber::class,
                'arguments' => [
                    'mautic.sms.model.sms',
                ],
            ],
            'mautic.sms.stats.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.sms.configbundle.subscriber' => [
                'class' => Mautic\SmsBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.sms.subscriber.contact_tracker' => [
                'class'     => \Mautic\SmsBundle\EventListener\TrackingSubscriber::class,
                'arguments' => [
                    'mautic.sms.repository.stat',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.sms' => [
                'class'     => 'Mautic\SmsBundle\Form\Type\SmsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'sms',
            ],
            'mautic.form.type.smsconfig' => [
                'class' => 'Mautic\SmsBundle\Form\Type\ConfigType',
                'alias' => 'smsconfig',
            ],
            'mautic.form.type.smssend_list' => [
                'class'     => 'Mautic\SmsBundle\Form\Type\SmsSendType',
                'arguments' => 'router',
                'alias'     => 'smssend_list',
            ],
            'mautic.form.type.sms_list' => [
                'class' => 'Mautic\SmsBundle\Form\Type\SmsListType',
                'alias' => 'sms_list',
            ],
            'mautic.form.type.sms.config.form' => [
                'class'     => \Mautic\SmsBundle\Form\Type\ConfigType::class,
                'alias'     => 'smsconfig',
                'arguments' => ['mautic.sms.transport_chain', 'translator'],
            ],
        ],
        'helpers' => [
            'mautic.helper.sms' => [
                'class'     => 'Mautic\SmsBundle\Helper\SmsHelper',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead',
                    'mautic.helper.phone_number',
                    'mautic.sms.model.sms',
                    'mautic.helper.integration',
                ],
                'alias' => 'sms_helper',
            ],
        ],
        'other' => [
            'mautic.sms.api' => [
                'class'     => 'Mautic\SmsBundle\Api\TwilioApi',
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.helper.phone_number',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
                'alias' => 'sms_api',
            ],
            'mautic.sms.transport_chain' => [
                'class'     => \Mautic\SmsBundle\Sms\TransportChain::class,
                'arguments' => [
                    '%mautic.sms_transport%',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.sms.transport.twilio' => [
                'class'        => \Mautic\SmsBundle\Api\TwilioApi::class,
                'arguments'    => [
                    'mautic.page.model.trackable',
                    'mautic.helper.phone_number',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Twilio',
                ],
            ],
        ],
        'models' => [
            'mautic.sms.model.sms' => [
                'class'     => 'Mautic\SmsBundle\Model\SmsModel',
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.lead.model.lead',
                    'mautic.channel.model.queue',
                    'mautic.sms.transport_chain',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.twilio' => [
                'class' => \Mautic\SmsBundle\Integration\TwilioIntegration::class,
            ],
        ],
        'repositories' => [
            'mautic.sms.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\SmsBundle\Entity\Stat::class,
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_sms_index' => [
                'path'       => '/sms/{page}',
                'controller' => 'MauticSmsBundle:Sms:index',
            ],
            'mautic_sms_action' => [
                'path'       => '/sms/{objectAction}/{objectId}',
                'controller' => 'MauticSmsBundle:Sms:execute',
            ],
            'mautic_sms_contacts' => [
                'path'       => '/sms/view/{objectId}/contact/{page}',
                'controller' => 'MauticSmsBundle:Sms:contacts',
            ],
        ],
        'public' => [
            'mautic_receive_sms' => [
                'path'       => '/sms/receive',
                'controller' => 'MauticSmsBundle:Api\SmsApi:receive',
            ],
        ],
        'api' => [
            'mautic_api_smsesstandard' => [
                'standard_entity' => true,
                'name'            => 'smses',
                'path'            => '/smses',
                'controller'      => 'MauticSmsBundle:Api\SmsApi',
            ],
            'mautic_api_smses_send' => [
                'path'       => '/smses/{id}/contact/{contactId}/send',
                'controller' => 'MauticSmsBundle:Api\SmsApi:send',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.sms.smses' => [
                    'route'  => 'mautic_sms_index',
                    'access' => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent' => 'mautic.core.channels',
                    'checks' => [
                        'integration' => [
                            'Twilio' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'priority' => 70,
                ],
            ],
        ],
    ],
    'parameters' => [
        'sms_enabled'              => false,
        'sms_username'             => null,
        'sms_password'             => null,
        'sms_sending_phone_number' => null,
        'sms_frequency_number'     => null,
        'sms_frequency_time'       => null,
        'sms_transport'            => 'mautic.sms.transport.twilio',
    ],
];

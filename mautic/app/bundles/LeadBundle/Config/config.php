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
            'mautic_plugin_timeline_index' => [
                'path'         => '/plugin/{integration}/timeline/{page}',
                'controller'   => 'MauticLeadBundle:Timeline:pluginIndex',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_plugin_timeline_view' => [
                'path'         => '/plugin/{integration}/timeline/view/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Timeline:pluginView',
                'requirements' => [
                    'integration' => '.+',
                    'leadId'      => '\d+',
                ],
            ],
            'mautic_segment_batch_contact_set' => [
                'path'       => '/segments/batch/contact/set',
                'controller' => 'MauticLeadBundle:BatchSegment:set',
            ],
            'mautic_segment_batch_contact_view' => [
                'path'       => '/segments/batch/contact/view',
                'controller' => 'MauticLeadBundle:BatchSegment:index',
            ],
            'mautic_segment_index' => [
                'path'       => '/segments/{page}',
                'controller' => 'MauticLeadBundle:List:index',
            ],
            'mautic_segment_action' => [
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute',
            ],
            'mautic_contactfield_index' => [
                'path'       => '/contacts/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index',
            ],
            'mautic_contactfield_action' => [
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute',
            ],
            'mautic_contact_index' => [
                'path'       => '/contacts/{page}',
                'controller' => 'MauticLeadBundle:Lead:index',
            ],
            'mautic_contactnote_index' => [
                'path'       => '/contacts/notes/{leadId}/{page}',
                'controller' => 'MauticLeadBundle:Note:index',
                'defaults'   => [
                    'leadId' => 0,
                ],
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contactnote_action' => [
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contacttimeline_action' => [
                'path'         => '/contacts/timeline/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Timeline:index',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_timeline_export_action' => [
                'path'         => '/contacts/timeline/batchExport/{leadId}',
                'controller'   => 'MauticLeadBundle:Timeline:batchExport',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_auditlog_action' => [
                'path'         => '/contacts/auditlog/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Auditlog:index',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_auditlog_export_action' => [
                'path'         => '/contacts/auditlog/batchExport/{leadId}',
                'controller'   => 'MauticLeadBundle:Auditlog:batchExport',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_export_action' => [
                'path'         => '/contacts/contact/export/{contactId}',
                'controller'   => 'MauticLeadBundle:Lead:contactExport',
                'requirements' => [
                    'contactId' => '\d+',
                ],
            ],
            // @deprecated 2.9.1 to be removed in 3.0. Use mautic_import_index instead.
            'mautic_contact_import_index' => [
                'path'       => '/{object}/import/{page}',
                'controller' => 'MauticLeadBundle:Import:index',
                'defaults'   => [
                    'object' => 'contacts',
                ],
            ],
            // @deprecated 2.9.1 to be removed in 3.0. Use mautic_import_action instead.
            'mautic_contact_import_action' => [
                'path'       => '/{object}/import/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Import:execute',
                'defaults'   => [
                    'object' => 'contacts',
                ],
            ],
            'mautic_import_index' => [
                'path'       => '/{object}/import/{page}',
                'controller' => 'MauticLeadBundle:Import:index',
            ],
            'mautic_import_action' => [
                'path'       => '/{object}/import/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Import:execute',
            ],
            'mautic_contact_action' => [
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute',
            ],
            'mautic_company_index' => [
                'path'       => '/companies/{page}',
                'controller' => 'MauticLeadBundle:Company:index',
            ],
            'mautic_company_action' => [
                'path'       => '/companies/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Company:execute',
            ],
            'mautic_segment_contacts' => [
                'path'       => '/segment/view/{objectId}/contact/{page}',
                'controller' => 'MauticLeadBundle:List:contacts',
            ],
        ],
        'api' => [
            'mautic_api_contactsstandard' => [
                'standard_entity' => true,
                'name'            => 'contacts',
                'path'            => '/contacts',
                'controller'      => 'MauticLeadBundle:Api\LeadApi',
            ],
            'mautic_api_dncaddcontact' => [
                'path'       => '/contacts/{id}/dnc/{channel}/add',
                'controller' => 'MauticLeadBundle:Api\LeadApi:addDnc',
                'method'     => 'POST',
                'defaults'   => [
                    'channel' => 'email',
                ],
            ],
            'mautic_api_dncremovecontact' => [
                'path'       => '/contacts/{id}/dnc/{channel}/remove',
                'controller' => 'MauticLeadBundle:Api\LeadApi:removeDnc',
                'method'     => 'POST',
            ],
            'mautic_api_getcontactevents' => [
                'path'       => '/contacts/{id}/activity',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getActivity',
            ],
            'mautic_api_getcontactsevents' => [
                'path'       => '/contacts/activity',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getAllActivity',
            ],
            'mautic_api_getcontactnotes' => [
                'path'       => '/contacts/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes',
            ],
            'mautic_api_getcontactdevices' => [
                'path'       => '/contacts/{id}/devices',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getDevices',
            ],
            'mautic_api_getcontactcampaigns' => [
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns',
            ],
            'mautic_api_getcontactssegments' => [
                'path'       => '/contacts/{id}/segments',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists',
            ],
            'mautic_api_getcontactscompanies' => [
                'path'       => '/contacts/{id}/companies',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCompanies',
            ],
            'mautic_api_utmcreateevent' => [
                'path'       => '/contacts/{id}/utm/add',
                'controller' => 'MauticLeadBundle:Api\LeadApi:addUtmTags',
                'method'     => 'POST',
            ],
            'mautic_api_utmremoveevent' => [
                'path'       => '/contacts/{id}/utm/{utmid}/remove',
                'controller' => 'MauticLeadBundle:Api\LeadApi:removeUtmTags',
                'method'     => 'POST',
            ],
            'mautic_api_getcontactowners' => [
                'path'       => '/contacts/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners',
            ],
            'mautic_api_getcontactfields' => [
                'path'       => '/contacts/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields',
            ],
            'mautic_api_getcontactsegments' => [
                'path'       => '/contacts/list/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists',
            ],
            'mautic_api_segmentsstandard' => [
                'standard_entity' => true,
                'name'            => 'lists',
                'path'            => '/segments',
                'controller'      => 'MauticLeadBundle:Api\ListApi',
            ],
            'mautic_api_segmentaddcontact' => [
                'path'       => '/segments/{id}/contact/{leadId}/add',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST',
            ],
            'mautic_api_segmentaddcontacts' => [
                'path'       => '/segments/{id}/contacts/add',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLeads',
                'method'     => 'POST',
            ],
            'mautic_api_segmentremovecontact' => [
                'path'       => '/segments/{id}/contact/{leadId}/remove',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST',
            ],
            'mautic_api_companiesstandard' => [
                'standard_entity' => true,
                'name'            => 'companies',
                'path'            => '/companies',
                'controller'      => 'MauticLeadBundle:Api\CompanyApi',
            ],
            'mautic_api_companyaddcontact' => [
                'path'       => '/companies/{companyId}/contact/{contactId}/add',
                'controller' => 'MauticLeadBundle:Api\CompanyApi:addContact',
                'method'     => 'POST',
            ],
            'mautic_api_companyremovecontact' => [
                'path'       => '/companies/{companyId}/contact/{contactId}/remove',
                'controller' => 'MauticLeadBundle:Api\CompanyApi:removeContact',
                'method'     => 'POST',
            ],
            'mautic_api_fieldsstandard' => [
                'standard_entity' => true,
                'name'            => 'fields',
                'path'            => '/fields/{object}',
                'controller'      => 'MauticLeadBundle:Api\FieldApi',
                'defaults'        => [
                    'object' => 'contact',
                ],
            ],
            'mautic_api_notesstandard' => [
                'standard_entity' => true,
                'name'            => 'notes',
                'path'            => '/notes',
                'controller'      => 'MauticLeadBundle:Api\NoteApi',
            ],
            'mautic_api_devicesstandard' => [
                'standard_entity' => true,
                'name'            => 'devices',
                'path'            => '/devices',
                'controller'      => 'MauticLeadBundle:Api\DeviceApi',
            ],
            'mautic_api_tagsstandard' => [
                'standard_entity' => true,
                'name'            => 'tags',
                'path'            => '/tags',
                'controller'      => 'MauticLeadBundle:Api\TagApi',
            ],

            // @deprecated 2.6.0 to be removed in 3.0
            'bc_mautic_api_segmentaddcontact' => [
                'path'       => '/segments/{id}/contact/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST',
            ],
            'bc_mautic_api_segmentremovecontact' => [
                'path'       => '/segments/{id}/contact/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST',
            ],
            'bc_mautic_api_companyaddcontact' => [
                'path'       => '/companies/{companyId}/contact/add/{contactId}',
                'controller' => 'MauticLeadBundle:Api\CompanyApi:addContact',
                'method'     => 'POST',
            ],
            'bc_mautic_api_companyremovecontact' => [
                'path'       => '/companies/{companyId}/contact/remove/{contactId}',
                'controller' => 'MauticLeadBundle:Api\CompanyApi:removeContact',
                'method'     => 'POST',
            ],
            'bc_mautic_api_dncaddcontact' => [
                'path'       => '/contacts/{id}/dnc/add/{channel}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:addDnc',
                'method'     => 'POST',
                'defaults'   => [
                    'channel' => 'email',
                ],
            ],
            'bc_mautic_api_dncremovecontact' => [
                'path'       => '/contacts/{id}/dnc/remove/{channel}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:removeDnc',
                'method'     => 'POST',
            ],
            // @deprecated 2.10.0 to be removed in 3.0
            'bc_mautic_api_getcontactevents' => [
                'path'       => '/contacts/{id}/events',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEvents',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.lead.leads' => [
                    'iconClass' => 'fa-user',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route'     => 'mautic_contact_index',
                    'priority'  => 80,
                ],
                'mautic.companies.menu.index' => [
                    'route'     => 'mautic_company_index',
                    'iconClass' => 'fa-building-o',
                    'access'    => ['lead:leads:viewother'],
                    'priority'  => 75,
                ],
                'mautic.lead.list.menu.index' => [
                    'iconClass' => 'fa-pie-chart',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route'     => 'mautic_segment_index',
                    'priority'  => 70,
                ],
            ],
        ],
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.lead.field.menu.index' => [
                    'id'        => 'mautic_lead_field',
                    'iconClass' => 'fa-list',
                    'route'     => 'mautic_contactfield_index',
                    'access'    => 'lead:fields:full',
                ],
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.lead.subscriber' => [
                'class'     => Mautic\LeadBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.lead.event.dispatcher',
                    'mautic.helper.template.dnc_reason',
                ],
                'methodCalls' => [
                    'setModelFactory' => ['mautic.model.factory'],
                ],
            ],
            'mautic.lead.subscriber.company' => [
                'class'     => 'Mautic\LeadBundle\EventListener\CompanySubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.lead.emailbundle.subscriber' => [
                'class' => 'Mautic\LeadBundle\EventListener\EmailSubscriber',
            ],
            'mautic.lead.formbundle.subscriber' => [
                'class'     => 'Mautic\LeadBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.lead.campaignbundle.subscriber' => [
                'class'     => 'Mautic\LeadBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field',
                    'mautic.lead.model.list',
                    'mautic.lead.model.company',
                    'mautic.campaign.model.campaign',
                ],
            ],
            'mautic.lead.campaignbundle.action_delete_contacts.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\CampaignActionDeleteContactSubscriber::class,
                'arguments' => [
                   'mautic.lead.model.lead',
                   'mautic.campaign.helper.removed_contact_tracker',
                ],
            ],
            'mautic.lead.reportbundle.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.stage.model.stage',
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.company',
                    'mautic.lead.model.company_report_data',
                    'mautic.lead.reportbundle.fields_builder',
                ],
            ],
            'mautic.lead.reportbundle.segment_subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\SegmentReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.reportbundle.fields_builder',
                ],
            ],
            'mautic.lead.reportbundle.report_utm_tag_subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ReportUtmTagSubscriber::class,
                'arguments' => [
                    'mautic.lead.reportbundle.fields_builder',
                    'mautic.lead.model.company_report_data',
                ],
            ],
            'mautic.lead.calendarbundle.subscriber' => [
                'class' => 'Mautic\LeadBundle\EventListener\CalendarSubscriber',
            ],
            'mautic.lead.pointbundle.subscriber' => [
                'class' => 'Mautic\LeadBundle\EventListener\PointSubscriber',
            ],
            'mautic.lead.search.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.webhook.subscriber' => [
                'class'       => 'Mautic\LeadBundle\EventListener\WebhookSubscriber',
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
            'mautic.lead.dashboard.subscriber' => [
                'class'     => 'Mautic\LeadBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.list',
                ],
            ],
            'mautic.lead.maintenance.subscriber' => [
                'class'     => 'Mautic\LeadBundle\EventListener\MaintenanceSubscriber',
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.lead.stats.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.button.subscriber' => [
                'class' => \Mautic\LeadBundle\EventListener\ButtonSubscriber::class,
            ],
            'mautic.lead.import.subscriber' => [
                'class'     => Mautic\LeadBundle\EventListener\ImportSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.lead.configbundle.subscriber' => [
                'class' => Mautic\LeadBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.lead.timeline_events.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\TimelineEventLogSubscriber::class,
                'arguments' => [
                    'translator',
                    'mautic.lead.repository.lead_event_log',
                ],
            ],
            'mautic.lead.timeline_events.campaign.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\TimelineEventLogCampaignSubscriber::class,
                'arguments' => [
                    'mautic.lead.repository.lead_event_log',
                    'mautic.helper.user',
                    'translator',
                ],
            ],
            'mautic.lead.timeline_events.segment.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\TimelineEventLogSegmentSubscriber::class,
                'arguments' => [
                    'mautic.lead.repository.lead_event_log',
                    'mautic.helper.user',
                    'translator',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.subscriber.segment' => [
                'class'     => 'Mautic\LeadBundle\EventListener\SegmentSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.lead' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadType',
                'arguments' => ['mautic.factory', 'mautic.lead.model.company'],
                'alias'     => 'lead',
            ],
            'mautic.form.type.leadlist' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ListType',
                'arguments' => [
                    'translator',
                    'mautic.lead.model.list',
                    'mautic.email.model.email',
                    'mautic.security',
                    'mautic.lead.model.lead',
                    'mautic.stage.model.stage',
                    'mautic.category.model.category',
                    'mautic.helper.user',
                    'mautic.campaign.model.campaign',
                    'mautic.asset.model.asset',
                ],
                'alias' => 'leadlist',
            ],
            'mautic.form.type.leadlist_choices' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadListType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'leadlist_choices',
            ],
            'mautic.form.type.leadlist_filter' => [
                'class'       => 'Mautic\LeadBundle\Form\Type\FilterType',
                'alias'       => 'leadlist_filter',
                'arguments'   => ['mautic.factory'],
                'methodCalls' => [
                    'setConnection' => [
                        'database_connection',
                    ],
                ],
            ],
            'mautic.form.type.leadfield' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\FieldType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'leadfield',
            ],
            'mautic.form.type.lead.submitaction.pointschange' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\FormSubmitActionPointsChangeType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'lead_submitaction_pointschange',
            ],
            'mautic.form.type.lead.submitaction.addutmtags' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ActionAddUtmTagsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_action_addutmtags',
            ],
            'mautic.form.type.lead.submitaction.removedonotcontact' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ActionRemoveDoNotContact',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_action_removedonotcontact',
            ],
            'mautic.form.type.lead.submitaction.changelist' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\EventListType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'leadlist_action_type',
            ],
            'mautic.form.type.leadpoints_trigger' => [
                'class' => 'Mautic\LeadBundle\Form\Type\PointTriggerType',
                'alias' => 'leadpoints_trigger',
            ],
            'mautic.form.type.leadpoints_action' => [
                'class' => 'Mautic\LeadBundle\Form\Type\PointActionType',
                'alias' => 'leadpoints_action',
            ],
            'mautic.form.type.leadlist_trigger' => [
                'class' => 'Mautic\LeadBundle\Form\Type\ListTriggerType',
                'alias' => 'leadlist_trigger',
            ],
            'mautic.form.type.leadlist_action' => [
                'class' => 'Mautic\LeadBundle\Form\Type\ListActionType',
                'alias' => 'leadlist_action',
            ],
            'mautic.form.type.updatelead_action' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\UpdateLeadActionType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'updatelead_action',
            ],
            'mautic.form.type.updatecompany_action' => [
                'class'     => Mautic\LeadBundle\Form\Type\UpdateCompanyActionType::class,
                'arguments' => ['mautic.lead.model.field'],
                'alias'     => 'updatecompany_action',
            ],
            'mautic.form.type.leadnote' => [
                'class' => Mautic\LeadBundle\Form\Type\NoteType::class,
                'alias' => 'leadnote',
            ],
            'mautic.form.type.leaddevice' => [
                'class' => Mautic\LeadBundle\Form\Type\DeviceType::class,
                'alias' => 'leaddevice',
            ],
            'mautic.form.type.lead_import' => [
                'class' => 'Mautic\LeadBundle\Form\Type\LeadImportType',
                'alias' => 'lead_import',
            ],
            'mautic.form.type.lead_field_import' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadImportFieldType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'lead_field_import',
            ],
            'mautic.form.type.lead_quickemail' => [
                'class'     => \Mautic\LeadBundle\Form\Type\EmailType::class,
                'arguments' => ['mautic.helper.user'],
                'alias'     => 'lead_quickemail',
            ],
            'mautic.form.type.lead_tags' => [
                'class'     => \Mautic\LeadBundle\Form\Type\TagListType::class,
                'alias'     => 'lead_tags',
                'arguments' => ['translator'],
            ],
            'mautic.form.type.lead_tag' => [
                'class'     => \Mautic\LeadBundle\Form\Type\TagType::class,
                'alias'     => 'lead_tag',
                'arguments' => ['doctrine.orm.entity_manager'],
            ],
            'mautic.form.type.modify_lead_tags' => [
                'class'     => \Mautic\LeadBundle\Form\Type\ModifyLeadTagsType::class,
                'alias'     => 'modify_lead_tags',
                'arguments' => ['translator'],
            ],
            'mautic.form.type.lead_entity_tag' => [
                'class' => \Mautic\LeadBundle\Form\Type\TagEntityType::class,
                'alias' => \Mautic\LeadBundle\Form\Type\TagEntityType::class,
            ],
            'mautic.form.type.lead_batch' => [
                'class' => 'Mautic\LeadBundle\Form\Type\BatchType',
                'alias' => 'lead_batch',
            ],
            'mautic.form.type.lead_batch_dnc' => [
                'class' => 'Mautic\LeadBundle\Form\Type\DncType',
                'alias' => 'lead_batch_dnc',
            ],
            'mautic.form.type.lead_batch_stage' => [
                'class' => 'Mautic\LeadBundle\Form\Type\StageType',
                'alias' => 'lead_batch_stage',
            ],
            'mautic.form.type.lead_batch_owner' => [
                'class' => 'Mautic\LeadBundle\Form\Type\OwnerType',
                'alias' => 'lead_batch_owner',
            ],
            'mautic.form.type.lead_merge' => [
                'class' => 'Mautic\LeadBundle\Form\Type\MergeType',
                'alias' => 'lead_merge',
            ],
            'mautic.form.type.lead_contact_frequency_rules' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ContactFrequencyType',
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
                'alias' => 'lead_contact_frequency_rules',
            ],
            'mautic.form.type.contact_channels' => [
                'class'     => \Mautic\LeadBundle\Form\Type\ContactChannelsType::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.type.campaignevent_lead_field_value' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadFieldValueType',
                'arguments' => [
                    'translator',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field',
                ],
                'alias' => 'campaignevent_lead_field_value',
            ],
            'mautic.form.type.campaignevent_lead_device' => [
                'class' => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadDeviceType',
                'alias' => 'campaignevent_lead_device',
            ],
            'mautic.form.type.campaignevent_lead_tags' => [
                'class'     => Mautic\LeadBundle\Form\Type\CampaignEventLeadTagsType::class,
                'arguments' => ['translator'],
                'alias'     => 'campaignevent_lead_tags',
            ],
            'mautic.form.type.campaignevent_lead_segments' => [
                'class' => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadSegmentsType',
                'alias' => 'campaignevent_lead_segments',
            ],
            'mautic.form.type.campaignevent_lead_campaigns' => [
                'class'     => Mautic\LeadBundle\Form\Type\CampaignEventLeadCampaignsType::class,
                'alias'     => 'campaignevent_lead_campaigns',
                'arguments' => ['mautic.lead.model.list'],
            ],
            'mautic.form.type.campaignevent_lead_owner' => [
                'class' => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadOwnerType',
                'alias' => 'campaignevent_lead_owner',
            ],
            'mautic.form.type.lead_fields' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadFieldsType',
                'arguments' => [
                    'mautic.lead.model.field',
                ],
                'alias'     => 'leadfields_choices',
            ],
            'mautic.form.type.lead_dashboard_leads_in_time_widget' => [
                'class' => 'Mautic\LeadBundle\Form\Type\DashboardLeadsInTimeWidgetType',
                'alias' => 'lead_dashboard_leads_in_time_widget',
            ],
            'mautic.form.type.lead_dashboard_leads_lifetime_widget' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\DashboardLeadsLifetimeWidgetType',
                'arguments' => ['mautic.factory'],
                'alias'     => 'lead_dashboard_leads_lifetime_widget',
            ],
            'mautic.company.type.form' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\CompanyType',
                'arguments' => ['doctrine.orm.entity_manager', 'mautic.security', 'router', 'translator'],
                'alias'     => 'company',
            ],
            'mautic.company.campaign.action.type.form' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\AddToCompanyActionType',
                'arguments' => ['router'],
                'alias'     => 'addtocompany_action',
            ],
            'mautic.lead.events.changeowner.type.form' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ChangeOwnerType',
                'arguments' => ['mautic.user.model.user'],
            ],
            'mautic.company.list.type.form' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\CompanyListType',
                'arguments' => [
                    'mautic.lead.model.company',
                    'mautic.helper.user',
                    'translator',
                    'router',
                    'database_connection',
                ],
                'alias' => 'company_list',
            ],
            'mautic.form.type.lead_categories' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadCategoryType',
                'arguments' => ['mautic.category.model.category'],
                'alias'     => 'leadcategory_choices',
            ],
            'mautic.company.merge.type.form' => [
                'class' => 'Mautic\LeadBundle\Form\Type\CompanyMergeType',
                'alias' => 'company_merge',
            ],
            'mautic.form.type.company_change_score' => [
                'class' => 'Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType',
                'alias' => 'scorecontactscompanies_action',
            ],
            'mautic.form.type.config.form' => [
                'class' => Mautic\LeadBundle\Form\Type\ConfigType::class,
                'alias' => 'leadconfig',
            ],
        ],
        'other' => [
            'mautic.lead.doctrine.subscriber' => [
                'class'     => 'Mautic\LeadBundle\EventListener\DoctrineSubscriber',
                'tag'       => 'doctrine.event_subscriber',
                'arguments' => ['monolog.logger.mautic'],
            ],
            'mautic.validator.leadlistaccess' => [
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
                'arguments' => ['mautic.factory'],
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access',
            ],
            \Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class => [
                'class'     => \Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class,
                'tag'       => 'validator.constraint_validator',
                'arguments' => [
                    'mautic.lead.model.list',
                    'mautic.helper.field.alias',
                    '@doctrine.orm.entity_manager',
                ],
            ],
            \Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class => [
                'class'     => \Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class,
                'tag'       => 'validator.constraint_validator',
                'arguments' => [
                    'mautic.lead.model.list',
                    'mautic.helper.field.alias',
                ],
            ],
            'mautic.lead.constraint.alias' => [
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator',
                'arguments' => ['mautic.factory'],
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'uniqueleadlist',
            ],
            'mautic.lead.event.dispatcher' => [
                'class'     => \Mautic\LeadBundle\Helper\LeadChangeEventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.merger' => [
                'class'     => \Mautic\LeadBundle\Deduplicate\ContactMerger::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.merged_records',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.deduper' => [
                'class'     => \Mautic\LeadBundle\Deduplicate\ContactDeduper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.merger',
                    'mautic.lead.repository.lead',
                ],
            ],
            'mautic.lead.helper.primary_company' => [
                'class'     => \Mautic\LeadBundle\Helper\PrimaryCompanyHelper::class,
                'arguments' => [
                    'mautic.lead.repository.company_lead',
                ],
            ],
            'mautic.lead.validator.length' => [
                'class'     => Mautic\LeadBundle\Validator\Constraints\LengthValidator::class,
                'tag'       => 'validator.constraint_validator',
            ],
        ],
        'repositories' => [
            'mautic.lead.repository.company' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\Company::class,
                ],
            ],
            'mautic.lead.repository.company_lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\CompanyLead::class,
                ],
            ],
            'mautic.lead.repository.dnc' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\DoNotContact::class,
                ],
            ],
            'mautic.lead.repository.lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\Lead::class,
                ],
            ],
            'mautic.lead.repository.frequency_rule' => [
                'class'     => \Mautic\LeadBundle\Entity\FrequencyRuleRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\FrequencyRule::class,
                ],
            ],
            'mautic.lead.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadEventLog::class,
                ],
            ],
            'mautic.lead.repository.lead_device' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadDevice::class,
                ],
            ],
            'mautic.lead.repository.lead_list' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadList::class,
                ],
            ],
            'mautic.lead.repository.merged_records' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\MergeRecord::class,
                ],
            ],
            'mautic.lead.repository.field' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadField::class,
                ],
            ],
            //  Segment Filter Query builders
            'mautic.lead.query.builder.basic' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.foreign.value' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.foreign.func' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.special.dnc' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.special.integration' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.special.sessions' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.complex_relation.value' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name'],
            ],
            'mautic.lead.query.builder.special.leadlist' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder::class,
                'arguments' => [
                    'mautic.lead.model.random_parameter_name',
                    'mautic.lead.repository.lead_segment_query_builder',
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead_segment_filter_factory', ],
            ],
        ],
        'helpers' => [
            'mautic.helper.template.avatar' => [
                'class'     => Mautic\LeadBundle\Templating\Helper\AvatarHelper::class,
                'arguments' => ['mautic.factory'],
                'alias'     => 'lead_avatar',
            ],
            'mautic.helper.field.alias' => [
                'class'     => \Mautic\LeadBundle\Helper\FieldAliasHelper::class,
                'arguments' => ['mautic.lead.model.field'],
            ],
            'mautic.helper.template.dnc_reason' => [
                'class'     => Mautic\LeadBundle\Templating\Helper\DncReasonHelper::class,
                'arguments' => ['translator'],
                'alias'     => 'lead_dnc_reason',
            ],
        ],
        'models' => [
            'mautic.lead.model.lead' => [
                'class'     => \Mautic\LeadBundle\Model\LeadModel::class,
                'arguments' => [
                    'request_stack',
                    'mautic.helper.cookie',
                    'mautic.helper.ip_lookup',
                    'mautic.helper.paths',
                    'mautic.helper.integration',
                    'mautic.lead.model.field',
                    'mautic.lead.model.list',
                    'form.factory',
                    'mautic.lead.model.company',
                    'mautic.category.model.category',
                    'mautic.channel.helper.channel_list',
                    'mautic.helper.core_parameters',
                    'mautic.validator.email',
                    'mautic.user.provider',
                    'mautic.tracker.contact',
                    'mautic.tracker.device',
                    'mautic.lead.model.legacy_lead',
                    'mautic.lead.model.ipaddress',
                ],
            ],

            // Deprecated support for circular dependency
            'mautic.lead.model.legacy_lead' => [
                'class'     => \Mautic\LeadBundle\Model\LegacyLeadModel::class,
                'arguments' => [
                    'service_container',
                ],
            ],
            'mautic.lead.model.field' => [
                'class'     => 'Mautic\LeadBundle\Model\FieldModel',
                'arguments' => [
                    'mautic.schema.helper.index',
                    'mautic.schema.helper.column',
                    'mautic.lead.model.list',
                ],
            ],
            'mautic.lead.model.list' => [
                'class'     => \Mautic\LeadBundle\Model\ListModel::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead_segment_service',
                ],
            ],
            'mautic.lead.repository.lead_segment_filter_descriptor' => [
                'class'     => \Mautic\LeadBundle\Services\ContactSegmentFilterDictionary::class,
                'arguments' => [],
            ],
            'mautic.lead.repository.lead_segment_query_builder' => [
                'class'     => Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.random_parameter_name',
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.model.lead_segment_service' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentService::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_factory',
                    'mautic.lead.repository.lead_segment_query_builder',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.model.lead_segment_filter_factory' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentFilterFactory::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_schema_cache',
                    '@service_container',
                    'mautic.lead.model.lead_segment_decorator_factory',
                ],
            ],
            'mautic.lead.model.lead_segment_schema_cache' => [
                'class'     => \Mautic\LeadBundle\Segment\TableSchemaColumnsCache::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.model.relative_date' => [
                'class'     => \Mautic\LeadBundle\Segment\RelativeDate::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.lead.model.lead_segment_filter_operator' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentFilterOperator::class,
                'arguments' => [
                    'translator',
                    'event_dispatcher',
                    'mautic.lead.segment.operator_options',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_factory' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class,
                'arguments' => [
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                    'mautic.lead.model.lead_segment_decorator_base',
                    'mautic.lead.model.lead_segment_decorator_custom_mapped',
                    'mautic.lead.model.lead_segment.decorator.date.optionFactory',
                    'mautic.lead.model.lead_segment_decorator_company',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_base' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_custom_mapped' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_company' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_date' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\DateDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.lead.model.lead_segment.decorator.date.optionFactory' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_decorator_date',
                    'mautic.lead.model.relative_date',
                    'mautic.lead.model.lead_segment.timezoneResolver',
                ],
            ],
            'mautic.lead.model.lead_segment.timezoneResolver' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.lead.model.random_parameter_name' => [
                'class'     => \Mautic\LeadBundle\Segment\RandomParameterName::class,
            ],
            'mautic.lead.segment.operator_options' => [
                'class'     => \Mautic\LeadBundle\Segment\OperatorOptions::class,
            ],
            'mautic.lead.model.note' => [
                'class' => 'Mautic\LeadBundle\Model\NoteModel',
            ],
            'mautic.lead.model.device' => [
                'class'     => Mautic\LeadBundle\Model\DeviceModel::class,
                'arguments' => [
                    'mautic.lead.repository.lead_device',
                ],
            ],
            'mautic.lead.model.company' => [
                'class'     => 'Mautic\LeadBundle\Model\CompanyModel',
                'arguments' => [
                    'mautic.lead.model.field',
                    'session',
                    'mautic.validator.email',
                ],
            ],
            'mautic.lead.model.import' => [
                'class'     => Mautic\LeadBundle\Model\ImportModel::class,
                'arguments' => [
                    'mautic.helper.paths',
                    'mautic.lead.model.lead',
                    'mautic.core.model.notification',
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.company',
                ],
            ],
            'mautic.lead.model.tag' => [
                'class' => \Mautic\LeadBundle\Model\TagModel::class,
            ],
            'mautic.lead.model.company_report_data' => [
                'class'     => \Mautic\LeadBundle\Model\CompanyReportData::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'translator',
                ],
            ],
            'mautic.lead.reportbundle.fields_builder' => [
                'class'     => \Mautic\LeadBundle\Report\FieldsBuilder::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.model.list',
                    'mautic.user.model.user',
                ],
            ],
            'mautic.lead.model.dnc' => [
                'class'     => \Mautic\LeadBundle\Model\DoNotContact::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.dnc',
                ],
            ],
            'mautic.lead.model.segment.action' => [
                'class'     => \Mautic\LeadBundle\Model\SegmentActionModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.lead.factory.device_detector_factory' => [
                'class' => \Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory::class,
            ],
            'mautic.lead.service.contact_tracking_service' => [
                'class'     => \Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingService::class,
                'arguments' => [
                    'mautic.helper.cookie',
                    'mautic.lead.repository.lead_device',
                    'mautic.lead.repository.lead',
                    'mautic.lead.repository.merged_records',
                    'request_stack',
                ],
            ],
            'mautic.lead.service.device_creator_service' => [
                'class' => \Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService::class,
            ],
            'mautic.lead.service.device_tracking_service' => [
                'class'     => \Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService::class,
                'arguments' => [
                    'mautic.helper.cookie',
                    'doctrine.orm.entity_manager',
                    'mautic.lead.repository.lead_device',
                    'mautic.helper.random',
                    'request_stack',
                    'mautic.security',
                ],
            ],
            'mautic.tracker.contact' => [
                'class'     => \Mautic\LeadBundle\Tracker\ContactTracker::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.lead.service.contact_tracking_service',
                    'mautic.tracker.device',
                    'mautic.security',
                    'monolog.logger.mautic',
                    'mautic.helper.ip_lookup',
                    'request_stack',
                    'mautic.helper.core_parameters',
                    'event_dispatcher',
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.tracker.device' => [
                'class'     => \Mautic\LeadBundle\Tracker\DeviceTracker::class,
                'arguments' => [
                    'mautic.lead.service.device_creator_service',
                    'mautic.lead.factory.device_detector_factory',
                    'mautic.lead.service.device_tracking_service',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.model.ipaddress' => [
                'class'     => Mautic\LeadBundle\Model\IpAddressModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'command' => [
            'mautic.lead.command.deduplicate' => [
                'class'     => \Mautic\LeadBundle\Command\DeduplicateCommand::class,
                'arguments' => [
                    'mautic.lead.deduper',
                    'translator',
                ],
                'tag' => 'console.command',
            ],
        ],
    ],
    'parameters' => [
        'parallel_import_limit'               => 1,
        'background_import_if_more_rows_than' => 0,
    ],
];

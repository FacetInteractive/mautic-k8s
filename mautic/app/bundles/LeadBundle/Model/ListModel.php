<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\Event\ListPreProcessListEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ListModel
 * {@inheritdoc}
 */
class ListModel extends FormModel
{
    use OperatorListTrait;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var ContactSegmentService
     */
    private $leadSegmentService;

    /**
     * ListModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, ContactSegmentService $leadSegment)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->leadSegmentService   = $leadSegment;
    }

    /**
     * Used by addLead and removeLead functions.
     *
     * @var array
     */
    private $leadChangeLists = [];

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\LeadListRepository
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function getRepository()
    {
        /** @var \Mautic\LeadBundle\Entity\LeadListRepository $repo */
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');

        $repo->setDispatcher($this->dispatcher);
        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * Returns the repository for the table that houses the leads associated with a list.
     *
     * @return \Mautic\LeadBundle\Entity\ListLeadRepository
     */
    public function getListLeadRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:ListLead');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:lists';
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param bool $unlock
     *
     * @return mixed|void
     */
    public function saveEntity($entity, $unlock = true)
    {
        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = $entity->getName();
        }
        $alias = $this->cleanAlias($alias, '', false, '-');

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $existing  = $repo->getLists($this->userHelper->getUser(), $testAlias, $entity->getId());
        $count     = count($existing);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $existing  = $repo->getLists($this->userHelper->getUser(), $testAlias, $entity->getId());
            $count     = count($existing);
            ++$aliasTag;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        $event = $this->dispatchEvent('pre_save', $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew, $event);
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(['LeadList'], 'Entity must be of class LeadList()');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('leadlist', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new LeadList();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(['LeadList'], 'Entity must be of class LeadList()');
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::LIST_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::LIST_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::LIST_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::LIST_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadListEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Get a list of field choices for filters.
     *
     * @return array
     */
    public function getChoiceFields()
    {
        //field choices
        $choices['lead'] = [
            'date_added' => [
                'label'      => $this->translator->trans('mautic.core.date.added'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_identified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_identified'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'last_active' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.last_active'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_modified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_modified'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'owner_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.owner'),
                'properties' => [
                    'type'     => 'lookup_id',
                    'callback' => 'activateSegmentFilterTypeahead',
                ],
                'operators' => $this->getOperatorsForFieldType('lookup_id'),
                'object'    => 'lead',
            ],
            'points' => [
                'label'      => $this->translator->trans('mautic.lead.lead.event.points'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'leadlist' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lists'),
                'properties' => [
                    'type' => 'leadlist',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.campaign'),
                'properties' => [
                    'type' => 'campaign',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'lead_asset_download' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_asset_download'),
                'properties' => ['type' => 'assets'],
                'operators'  => $this->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ],
            'lead_email_received' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_received'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            'in',
                            '!in',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_sent' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            'in',
                            '!in',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_sent_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'lt',
                            'gte',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_read_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'lt',
                            'gte',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_read_count' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'tags' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.tags'),
                'properties' => [
                    'type' => 'tags',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_type' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_type'),
                'properties' => [
                    'type' => 'device_type',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_brand' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_brand'),
                'properties' => [
                    'type' => 'device_brand',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_os' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_os'),
                'properties' => [
                    'type' => 'device_os',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_model' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_model'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'like',
                            'regexp',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'dnc_bounced' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_manual_email' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_manual_email'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_bounced_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'hit_url' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'hit_url_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'lt',
                            'gte',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'hit_url_count' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'sessions' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.session'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'referer' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.referer'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'url_title' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.url_title'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'source' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.source'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'source_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.source.id'),
                'properties' => [
                    'type' => 'number',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'notification' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.notification'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'page_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.page_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'email_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.email_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'redirect_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.redirect_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'stage' => [
                'label'      => $this->translator->trans('mautic.lead.lead.field.stage'),
                'properties' => [
                    'type' => 'stage',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'empty',
                            '!empty',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'globalcategory' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.categories'),
                'properties' => [
                    'type' => 'globalcategory',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'utm_campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcampaign'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_content' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcontent'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_medium' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmmedium'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_source' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmsource'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_term' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmterm'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
        ];

        // Add custom choices
        if ($this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE)) {
            $event = new LeadListFiltersChoicesEvent($choices, $this->getOperatorsForFieldType(), $this->translator);
            $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE, $event);
            $choices = $event->getChoices();
        }

        //get list of custom fields
        $fields = $this->em->getRepository('MauticLeadBundle:LeadField')->getEntities(
            [
                'filter' => [
                    'where'         => [
                        [
                            'expr' => 'eq',
                            'col'  => 'f.isListable',
                            'val'  => true,
                        ],
                        [
                            'expr' => 'eq',
                            'col'  => 'f.isPublished',
                            'val'  => true,
                        ],
                    ],
                ],
                'orderBy' => 'f.object',
            ]
        );
        foreach ($fields as $field) {
            $type               = $field->getType();
            $properties         = $field->getProperties();
            $properties['type'] = $type;
            if (in_array($type, ['select', 'multiselect', 'boolean'])) {
                if ('boolean' == $type) {
                    //create a lookup list with ID
                    $properties['list'] = [
                        0 => $properties['no'],
                        1 => $properties['yes'],
                    ];
                } else {
                    $properties['callback'] = 'activateLeadFieldTypeahead';
                    $properties['list']     = (isset($properties['list'])) ? FormFieldHelper::formatList(
                        FormFieldHelper::FORMAT_ARRAY,
                        FormFieldHelper::parseList($properties['list'])
                    ) : '';
                }
            }
            $choices[$field->getObject()][$field->getAlias()] = [
                'label'      => $field->getLabel(),
                'properties' => $properties,
                'object'     => $field->getObject(),
            ];

            $choices[$field->getObject()][$field->getAlias()]['operators'] = $this->getOperatorsForFieldType($type);
        }

        foreach ($choices as $key => $choice) {
            $cmp = function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            };
            uasort($choice, $cmp);
            $choices[$key] = $choice;
        }

        return $choices;
    }

    /**
     * @param string $alias
     *
     * @return array
     */
    public function getUserLists($alias = '')
    {
        $user = (!$this->security->isGranted('lead:lists:viewother')) ?
            $this->userHelper->getUser() : false;
        $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getLists($user, $alias);

        return $lists;
    }

    /**
     * Get a list of global lead lists.
     *
     * @return mixed
     */
    public function getGlobalLists()
    {
        $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getGlobalLists();

        return $lists;
    }

    /**
     * Get a list of preference center lead lists.
     *
     * @return mixed
     */
    public function getPreferenceCenterLists()
    {
        $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getPreferenceCenterList();

        return $lists;
    }

    /**
     * @param LeadList $entity
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getVersionNew(LeadList $entity)
    {
        $id       = $entity->getId();
        $list     = ['id' => $id, 'filters' => $entity->getFilters()];
        $dtHelper = new DateTimeHelper();

        $batchLimiters = [
            'dateTime' => $dtHelper->toUtcString(),
        ];

        return $this->leadSegmentService->getNewLeadListLeadsCount($entity, $batchLimiters);
    }

    /**
     * @param LeadList $entity
     *
     * @return mixed
     */
    public function getVersionOld(LeadList $entity)
    {
        $id       = $entity->getId();
        $list     = ['id' => $id, 'filters' => $entity->getFilters()];
        $dtHelper = new DateTimeHelper();

        $batchLimiters = [
            'dateTime' => $dtHelper->toUtcString(),
        ];

        $newLeadsCount = $this->getLeadsByList(
            $list,
            true,
            [
                'countOnly'     => true,
                'newOnly'       => true,
                'batchLimiters' => $batchLimiters,
            ]
        );

        $return = array_shift($newLeadsCount);

        return $return;
    }

    /**
     * @param LeadList             $leadList
     * @param int                  $limit
     * @param bool                 $maxLeads
     * @param OutputInterface|null $output
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function rebuildListLeads(LeadList $leadList, $limit = 100, $maxLeads = false, OutputInterface $output = null)
    {
        defined('MAUTIC_REBUILDING_LEAD_LISTS') or define('MAUTIC_REBUILDING_LEAD_LISTS', 1);

        $dtHelper = new DateTimeHelper();

        $batchLimiters = ['dateTime' => $dtHelper->toUtcString()];
        $list          = ['id' => $leadList->getId(), 'filters' => $leadList->getFilters()];

        $this->dispatcher->dispatch(
            LeadEvents::LIST_PRE_PROCESS_LIST, new ListPreProcessListEvent($list, false)
        );

        try {
            // Get a count of leads to add
            $newLeadsCount = $this->leadSegmentService->getNewLeadListLeadsCount($leadList, $batchLimiters);
        } catch (FieldNotFoundException $e) {
            // A field from filter does not exist anymore. Do not rebuild.
            return 0;
        } catch (SegmentNotFoundException $e) {
            // A segment from filter does not exist anymore. Do not rebuild.
            return 0;
        }

        // Ensure the same list is used each batch <- would love to know how
        $batchLimiters['maxId'] = (int) $newLeadsCount[$leadList->getId()]['maxId'];

        // Number of total leads to process
        $leadCount = (int) $newLeadsCount[$leadList->getId()]['count'];

        $this->logger->info('Segment QB - No new leads for segment found');

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_added', ['%leads%' => $leadCount, '%batch%' => $limit]));
        }

        // Handle by batches
        $start = $lastRoundPercentage = $leadsProcessed = 0;

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            $maxCount = ($maxLeads) ? $maxLeads : $leadCount;

            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            // Add leads
            while ($start < $leadCount) {
                // Keep CPU down for large lists; sleep per $limit batch
                $this->batchSleep();

                $this->logger->debug(sprintf('Segment QB - Fetching new leads for segment [%d] %s', $leadList->getId(), $leadList->getName()));
                $newLeadList = $this->leadSegmentService->getNewLeadListLeads($leadList, $batchLimiters, $limit);

                if (empty($newLeadList[$leadList->getId()])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                $this->logger->debug(sprintf('Segment QB - Adding %d new leads to segment [%d] %s', count($newLeadList[$leadList->getId()]), $leadList->getId(), $leadList->getName()));
                foreach ($newLeadList[$leadList->getId()] as $l) {
                    $this->logger->debug(sprintf('Segment QB - Adding lead #%s to segment [%d] %s', $l['id'], $leadList->getId(), $leadList->getName()));

                    $this->addLead($l, $leadList, false, true, -1, $dtHelper->getLocalDateTime());

                    ++$leadsProcessed;
                    if ($output && $leadsProcessed < $maxCount) {
                        $progress->setProgress($leadsProcessed);
                    }

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                $this->logger->info(sprintf('Segment QB - Added %d new leads to segment [%d] %s', count($newLeadList[$leadList->getId()]), $leadList->getId(), $leadList->getName()));

                $start += $limit;

                // Dispatch batch event
                if (count($newLeadList[$leadList->getId()]) && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    $this->dispatcher->dispatch(
                        LeadEvents::LEAD_LIST_BATCH_CHANGE,
                        new ListChangeEvent($newLeadList[$leadList->getId()], $leadList, true)
                    );
                }

                unset($newLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        // Unset max ID to prevent capping at newly added max ID
        unset($batchLimiters['maxId']);

        $orphanLeadsCount = $this->leadSegmentService->getOrphanedLeadListLeadsCount($leadList);

        // Ensure the same list is used each batch
        $batchLimiters['maxId'] = (int) $orphanLeadsCount[$leadList->getId()]['maxId'];

        // Restart batching
        $start     = $lastRoundPercentage     = 0;
        $leadCount = $orphanLeadsCount[$leadList->getId()]['count'];

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_removed', ['%leads%' => $leadCount, '%batch%' => $limit]));
        }

        if ($leadCount) {
            $maxCount = ($maxLeads) ? $maxLeads : $leadCount;

            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            // Remove leads
            while ($start < $leadCount) {
                // Keep CPU down for large lists; sleep per $limit batch
                $this->batchSleep();

                $removeLeadList = $this->leadSegmentService->getOrphanedLeadListLeads($leadList, [], $limit);

                if (empty($removeLeadList[$leadList->getId()])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                $processedLeads = [];
                foreach ($removeLeadList[$leadList->getId()] as $l) {
                    $this->removeLead($l, $leadList, false, true, true);
                    $processedLeads[] = $l;
                    ++$leadsProcessed;
                    if ($output && $leadsProcessed < $maxCount) {
                        $progress->setProgress($leadsProcessed);
                    }

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                // Dispatch batch event
                if (count($processedLeads) && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    $this->dispatcher->dispatch(
                        LeadEvents::LEAD_LIST_BATCH_CHANGE,
                        new ListChangeEvent($processedLeads, $leadList, false)
                    );
                }

                $start += $limit;

                unset($removeLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        return $leadsProcessed;
    }

    /**
     * Add lead to lists.
     *
     * @param array|Lead     $lead
     * @param array|LeadList $lists
     * @param bool           $manuallyAdded
     * @param bool           $batchProcess
     * @param int            $searchListLead  0 = reference, 1 = yes, -1 = known to not exist
     * @param \DateTime      $dateManipulated
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function addLead($lead, $lists, $manuallyAdded = false, $batchProcess = false, $searchListLead = 1, $dateManipulated = null)
    {
        if (null == $dateManipulated) {
            $dateManipulated = new \DateTime();
        }

        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            //make sure they are ints
            $searchForLists = [];
            foreach ($lists as $k => &$l) {
                $l = (int) $l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities([
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists,
                            ],
                        ],
                    ],
                ]);

                foreach ($listEntities as $list) {
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);
        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = [$lists->getId()];
        }

        if (!is_array($lists)) {
            $lists = [$lists];
        }

        $persistLists   = [];
        $dispatchEvents = [];

        foreach ($lists as $listId) {
            if (!isset($this->leadChangeLists[$listId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            if ($searchListLead == -1) {
                $listLead = null;
            } elseif ($searchListLead) {
                $listLead = $this->getListLeadRepository()->findOneBy(
                    [
                        'lead' => $lead,
                        'list' => $this->leadChangeLists[$listId],
                    ]
                );
            } else {
                $listLead = $this->em->getReference('MauticLeadBundle:ListLead',
                    [
                        'lead' => $leadId,
                        'list' => $listId,
                    ]
                );
            }

            if (null != $listLead) {
                if ($manuallyAdded && $listLead->wasManuallyRemoved()) {
                    $listLead->setManuallyRemoved(false);
                    $listLead->setManuallyAdded($manuallyAdded);

                    $persistLists[]   = $listLead;
                    $dispatchEvents[] = $listId;
                } else {
                    // Detach from Doctrine
                    $this->em->detach($listLead);

                    continue;
                }
            } else {
                $listLead = new ListLead();
                $listLead->setList($this->leadChangeLists[$listId]);
                $listLead->setLead($lead);
                $listLead->setManuallyAdded($manuallyAdded);
                $listLead->setDateAdded($dateManipulated);

                $persistLists[]   = $listLead;
                $dispatchEvents[] = $listId;
            }
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);
        }

        // Clear ListLead entities from Doctrine memory
        $this->em->clear('Mautic\LeadBundle\Entity\ListLead');

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        } elseif (!empty($dispatchEvents) && ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE))) {
            foreach ($dispatchEvents as $listId) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$listId]);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);

                unset($event);
            }
        }

        unset($lead, $persistLists, $lists);
    }

    /**
     * Remove a lead from lists.
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyRemoved
     * @param bool $batchProcess
     * @param bool $skipFindOne
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeLead($lead, $lists, $manuallyRemoved = false, $batchProcess = false, $skipFindOne = false)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            //make sure they are ints
            $searchForLists = [];
            foreach ($lists as $k => &$l) {
                $l = (int) $l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities([
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists,
                            ],
                        ],
                    ],
                ]);

                foreach ($listEntities as $list) {
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);
        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = [$lists->getId()];
        }

        if (!is_array($lists)) {
            $lists = [$lists];
        }

        $persistLists   = [];
        $deleteLists    = [];
        $dispatchEvents = [];

        foreach ($lists as $listId) {
            if (!isset($this->leadChangeLists[$listId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            $listLead = (!$skipFindOne) ?
                $this->getListLeadRepository()->findOneBy([
                    'lead' => $lead,
                    'list' => $this->leadChangeLists[$listId],
                ]) :
                $this->em->getReference('MauticLeadBundle:ListLead', [
                    'lead' => $leadId,
                    'list' => $listId,
                ]);

            if (null == $listLead) {
                // Lead is not part of this list
                continue;
            }

            if (($manuallyRemoved && $listLead->wasManuallyAdded()) || (!$manuallyRemoved && !$listLead->wasManuallyAdded())) {
                //lead was manually added and now manually removed or was not manually added and now being removed
                $deleteLists[]    = $listLead;
                $dispatchEvents[] = $listId;
            } elseif ($manuallyRemoved && !$listLead->wasManuallyAdded()) {
                $listLead->setManuallyRemoved(true);

                $persistLists[]   = $listLead;
                $dispatchEvents[] = $listId;
            }

            unset($listLead);
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);
        }

        if (!empty($deleteLists)) {
            $this->getRepository()->deleteEntities($deleteLists);
        }

        // Clear ListLead entities from Doctrine memory
        $this->em->clear('Mautic\LeadBundle\Entity\ListLead');

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        } elseif (!empty($dispatchEvents) && ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE))) {
            foreach ($dispatchEvents as $listId) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$listId], false);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);

                unset($event);
            }
        }

        unset($lead, $deleteLists, $persistLists, $lists);
    }

    /**
     * @deprecated in 2.14, to be removed in Mautic 3 - Use methods in the ContactSegmentService class
     *
     * @param       $lists
     * @param bool  $idOnly
     * @param array $args
     *
     * @return array
     */
    public function getLeadsByList($lists, $idOnly = false, array $args = [])
    {
        $args['idOnly'] = $idOnly;

        return $this->getRepository()->getLeadsByList($lists, $args, $this->logger);
    }

    /**
     * Batch sleep according to settings.
     */
    protected function batchSleep()
    {
        $leadSleepTime = $this->coreParametersHelper->getParameter('batch_lead_sleep_time', false);
        if (false === $leadSleepTime) {
            $leadSleepTime = $this->coreParametersHelper->getParameter('batch_sleep_time', 1);
        }

        if (empty($leadSleepTime)) {
            return;
        }

        if ($leadSleepTime < 1) {
            usleep($leadSleepTime * 1000000);
        } else {
            sleep($leadSleepTime);
        }
    }

    /**
     * Get a list of top (by leads added) lists.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopLists($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.date_added) AS leads, ll.id, ll.name, ll.alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = t.leadlist_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('ll.is_published', ':published'))
            ->setParameter('published', true)
            ->groupBy('ll.id')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('ll.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of top (by leads added) lists.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getLifeCycleSegments($limit, $dateFrom, $dateTo, $filters, $segments)
    {
        if (!empty($segments)) {
            $segmentlist = "'".implode("','", $segments)."'";
        }
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.date_added) AS leads, ll.id, ll.name as name,ll.alias as alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = t.leadlist_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('ll.is_published', ':published'))
            ->setParameter('published', true)
            ->groupBy('ll.id');

        if ($limit) {
            $q->setMaxResults($limit);
        }
        if (!empty($segments)) {
            $q->andWhere('ll.id IN ('.$segmentlist.')');
        }
        if (!empty($dateFrom)) {
            $q->andWhere("l.date_added >= '".$dateFrom->format('Y-m-d')."'");
        }
        if (!empty($dateTo)) {
            $q->andWhere("l.date_added <= '".$dateTo->format('Y-m-d')." 23:59:59'");
        }
        if (!empty($options['canViewOthers'])) {
            $q->andWhere('ll.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->execute()->fetchAll();

        if (in_array(0, $segments)) {
            $qAll = $this->em->getConnection()->createQueryBuilder();
            $qAll->select('COUNT(t.date_added) AS leads, 0 as id, "All Contacts" as name, "" as alias')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 't');

            if (!empty($options['canViewOthers'])) {
                $qAll->andWhere('ll.created_by = :userId')
                    ->setParameter('userId', $this->userHelper->getUser()->getId());
            }
            if (!empty($dateFrom)) {
                $qAll->andWhere("t.date_added >= '".$dateFrom->format('Y-m-d')."'");
            }
            if (!empty($dateTo)) {
                $qAll->andWhere("t.date_added <= '".$dateTo->format('Y-m-d')." 23:59:59'");
            }
            $resultsAll = $qAll->execute()->fetchAll();
            $results    = array_merge($results, $resultsAll);
        }

        return $results;
    }

    /**
     * @param           $unit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param           $dateFormat
     * @param           $filter
     * @param           $canViewOthers
     * @param           $listName
     *
     * @return array
     */
    public function getLifeCycleSegmentChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat, $filter, $canViewOthers, $listName)
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        if (isset($filter['flag'])) {
            unset($filter['flag']);
        }

        $allLists = $query->getCountQuery('leads', 'id', 'date_added', null);

        $lists = $query->count('leads', 'id', 'date_added', $filter, null);

        $all        = $query->fetchCount($allLists);
        $identified = $lists;

        $chart->setDataset($listName, $identified);

        if (isset($filter['leadlist_id']['value'])) {
            $chart->setDataset(
                $this->translator->trans('mautic.lead.lifecycle.graph.pie.all.lists'),
                $all
            );
        }

        return $chart->render(false);
    }

    /**
     * @param           $unit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param null      $dateFormat
     * @param array     $filter
     *
     * @return array
     */
    public function getStagesBarChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [])
    {
        $data['values'] = [];
        $data['labels'] = [];

        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(l.id) as leads, s.name as stage')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'stages', 's', 's.id=l.stage_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('s.is_published', ':published'))

            ->andWhere($q->expr()->gte('t.date_added', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('t.date_added', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d'.' 23:59:59'))
            ->setParameter('published', true);

        if (isset($filter['leadlist_id']['value'])) {
            $q->andWhere($q->expr()->eq('t.leadlist_id', ':leadlistid'))->setParameter('leadlistid', $filter['leadlist_id']['value']);
        }

        $q->groupBy('s.name');

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('s.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->execute()->fetchAll();

        foreach ($results as $result) {
            $percentage       = $result['leads'];
            $data['labels'][] = substr($result['stage'], 0, 12);
            $data['values'][] = $result['leads'];
        }
        $data['xAxes'][] = ['display' => true];
        $data['yAxes'][] = ['display' => true];

        $baseData = [
            'label' => $this->translator->trans('mautic.lead.leads'),
            'data'  => $data['values'],
        ];

        $chart = new BarChart($data['labels']);

        $datasetId  = count($data['values']);
        $datasets[] = array_merge($baseData, $chart->generateColors(3));

        $chartData = [
            'labels'   => $data['labels'],
            'datasets' => $datasets,
            'options'  => [
                'xAxes' => $data['xAxes'],
                'yAxes' => $data['yAxes'],
            ], ];

        return $chartData;
    }

    /**
     * @param           $unit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param null      $dateFormat
     * @param array     $filter
     *
     * @return array
     */
    public function getDeviceGranularityData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [])
    {
        $data['values'] = [];
        $data['labels'] = [];

        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(l.id) as leads, ds.device')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'page_hits', 'h', 'h.lead_id=l.id')
            ->join('h', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id = h.device_id')
            ->orderBy('ds.device', 'DESC')
            ->andWhere($q->expr()->gte('t.date_added', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('t.date_added', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d'.' 23:59:59'));

        if (isset($filter['leadlist_id']['value'])) {
            $q->andWhere($q->expr()->eq('t.leadlist_id', ':leadlistid'))->setParameter(
                'leadlistid',
                $filter['leadlist_id']['value']
            );
        }

        $q->groupBy('ds.device');

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('l.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->execute()->fetchAll();

        foreach ($results as $result) {
            $data['labels'][] = substr(empty($result['device']) ? $this->translator->trans('mautic.core.no.info') : $result['device'], 0, 12);
            $data['values'][] = $result['leads'];
        }

        $data['xAxes'][] = ['display' => true];
        $data['yAxes'][] = ['display' => true];

        $baseData = [
            'label' => $this->translator->trans('mautic.core.device'),
            'data'  => $data['values'],
        ];

        $chart = new BarChart($data['labels']);

        $datasets[] = array_merge($baseData, $chart->generateColors(2));

        $chartData = [
            'labels'   => $data['labels'],
            'datasets' => $datasets,
            'options'  => [
                'xAxes' => $data['xAxes'],
                'yAxes' => $data['yAxes'],
            ],
        ];

        return $chartData;
    }

    /**
     * Get line chart data of hits.
     *
     * @param string    $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     *
     * @return array
     */
    public function getSegmentContactsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [])
    {
        $chart    = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query    = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $contacts = $query->fetchTimeData('lead_lists_leads', 'date_added', $filter);
        $chart->setDataset($this->translator->trans('mautic.lead.segments.contacts'), $contacts);

        return $chart->render();
    }

    /**
     * Is custom field used in at least one defined segment?
     *
     * @param LeadField $field
     *
     * @return bool
     */
    public function isFieldUsed(LeadField $field)
    {
        $segments = $this->getFieldSegments($field);

        return 0 < $segments->count();
    }

    public function getFieldSegments(LeadField $field)
    {
        $alias       = $field->getAlias();
        $aliasLength = mb_strlen($alias);
        $likeContent = "%;s:5:\"field\";s:${aliasLength}:\"{$alias}\";%";

        $filter = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=> $likeContent],
            ],
        ];

        return $this->getEntities(['filter' => $filter]);
    }

    /**
     * Get segments which are dependent on given segment.
     *
     * @param int $segmentId
     *
     * @return array
     */
    public function getSegmentsWithDependenciesOnSegment($segmentId)
    {
        $page  = 1;
        $limit = 1000;
        $start = 0;

        $filter = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=>'%s:8:"leadlist"%'],
                ['column' => 'l.id', 'expr' => 'neq', 'value'=>$segmentId],
            ],
        ];

        $entities = $this->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
            ]
        );
        $dependents = [];

        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if ($eachFilter['type'] === 'leadlist' && in_array($segmentId, $eachFilter['filter'])) {
                    $dependents[] = $entity->getName();
                }
            }
        }

        return $dependents;
    }

    /**
     * Get segments which are used as a dependent by other segments to prevent batch deletion of them.
     *
     * @param array $segmentIds
     *
     * @return array
     */
    public function canNotBeDeleted($segmentIds)
    {
        $filter = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=>'%s:8:"leadlist"%'],
            ],
        ];

        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );

        $idsNotToBeDeleted   = [];
        $namesNotToBeDeleted = [];
        $dependency          = [];

        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if ($eachFilter['type'] !== 'leadlist') {
                    continue;
                }

                $idsNotToBeDeleted = array_unique(array_merge($idsNotToBeDeleted, $eachFilter['filter']));
                foreach ($eachFilter['filter'] as $val) {
                    if (!empty($dependency[$val])) {
                        $dependency[$val] = array_merge($dependency[$val], [$entity->getId()]);
                        $dependency[$val] = array_unique($dependency[$val]);
                    } else {
                        $dependency[$val] = [$entity->getId()];
                    }
                }
            }
        }
        foreach ($dependency as $key => $value) {
            if (array_intersect($value, $segmentIds) === $value) {
                $idsNotToBeDeleted = array_unique(array_diff($idsNotToBeDeleted, [$key]));
            }
        }

        $idsNotToBeDeleted = array_intersect($segmentIds, $idsNotToBeDeleted);

        foreach ($idsNotToBeDeleted as $val) {
            $namesNotToBeDeleted[$val] = $this->getEntity($val)->getName();
        }

        return $namesNotToBeDeleted;
    }
}

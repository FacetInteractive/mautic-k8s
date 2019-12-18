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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class FieldModel.
 */
class FieldModel extends FormModel
{
    public static $coreFields = [
        // Listed according to $order for installation
        'title' => [
            'type'       => 'lookup',
            'properties' => ['list' => 'Mr|Mrs|Miss'],
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'lead',
        ],
        'firstname' => [
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'lastname' => [
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'company' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'position' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'email' => [
            'type'     => 'email',
            'unique'   => true,
            'fixed'    => true,
            'short'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'mobile' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'phone' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'points' => [
            'type'     => 'number',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
            'default'  => 0,
        ],
        'fax' => [
            'type'     => 'tel',
            'listable' => true,
            'object'   => 'lead',
        ],
        'address1' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'address2' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'city' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'state' => [
            'type'     => 'region',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'zipcode' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'country' => [
            'type'     => 'country',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'preferred_locale' => [
            'type'     => 'locale',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'timezone' => [
            'type'     => 'timezone',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'last_active' => [
            'type'     => 'datetime',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'attribution_date' => [
            'type'     => 'datetime',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'lead',
        ],
        'attribution' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'precision' => 2],
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'lead',
        ],
        'website' => [
            'type'     => 'url',
            'listable' => true,
            'object'   => 'lead',
        ],
        'facebook' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'foursquare' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'googleplus' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'instagram' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'linkedin' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'skype' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
        'twitter' => [
            'listable' => true,
            'group'    => 'social',
            'object'   => 'lead',
        ],
    ];

    public static $coreCompanyFields = [
        // Listed according to $order for installation
        'companyaddress1' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyaddress2' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyemail' => [
            'type'     => 'email',
            'unique'   => true,
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyphone' => [
            'type'     => 'tel',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companycity' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companystate' => [
            'type'     => 'region',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyzipcode' => [
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companycountry' => [
            'type'     => 'country',
            'fixed'    => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companyname' => [
            'fixed'    => true,
            'required' => true,
            'listable' => true,
            'object'   => 'company',
        ],
        'companywebsite' => [
            'fixed'    => true,
            'type'     => 'url',
            'listable' => true,
            'object'   => 'company',
        ],
        'companynumber_of_employees' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'precision' => 0],
            'group'      => 'professional',
            'listable'   => true,
            'object'     => 'company',
        ],
        'companyfax' => [
            'type'     => 'tel',
            'listable' => true,
            'group'    => 'professional',
            'object'   => 'company',
        ],
        'companyannual_revenue' => [
            'type'       => 'number',
            'properties' => ['roundmode' => 4, 'precision' => 2],
            'listable'   => true,
            'group'      => 'professional',
            'object'     => 'company',
        ],
        'companyindustry' => [
            'type'       => 'select',
            'group'      => 'professional',
            'properties' => ['list' => 'Agriculture|Apparel|Banking|Biotechnology|Chemicals|Communications|Construction|Education|Electronics|Energy|Engineering|Entertainment|Environmental|Finance|Food & Beverage|Government|Healthcare|Hospitality|Insurance|Machinery|Manufacturing|Media|Not for Profit|Recreation|Retail|Shipping|Technology|Telecommunications|Transportation|Utilities|Other'],
            'fixed'      => true,
            'listable'   => true,
            'object'     => 'company',
        ],
        'companydescription' => [
            'fixed'    => true,
            'group'    => 'professional',
            'listable' => true,
            'object'   => 'company',
        ],
    ];

    /**
     * @var IndexSchemaHelper
     */
    private $indexSchemaHelper;

    /**
     * @var ColumnSchemaHelper
     */
    private $columnSchemaHelper;

    /**
     * @var array
     */
    protected $uniqueIdentifierFields = [];

    /**
     * @var ListModel
     */
    private $leadListModel;

    /**
     * FieldModel constructor.
     *
     * @param IndexSchemaHelper  $indexSchemaHelper
     * @param ColumnSchemaHelper $columnSchemaHelper
     * @param ListModel          $leadListModel
     */
    public function __construct(
        IndexSchemaHelper $indexSchemaHelper,
        ColumnSchemaHelper $columnSchemaHelper,
        ListModel $leadListModel
    ) {
        $this->indexSchemaHelper  = $indexSchemaHelper;
        $this->columnSchemaHelper = $columnSchemaHelper;
        $this->leadListModel      = $leadListModel;
    }

    /**
     * @return LeadFieldRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadField');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:fields';
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
        if ($id === null) {
            return new LeadField();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Returns lead custom fields.
     *
     * @param $args
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        return $this->em->getRepository(LeadField::class)->getEntities($args);
    }

    /**
     * @return array
     */
    public function getLeadFields()
    {
        $leadFields = $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.object',
                        'expr'   => 'like',
                        'value'  => 'lead',
                    ],
                ],
            ],
        ]);

        return $leadFields;
    }

    /**
     * @return array
     */
    public function getCompanyFields()
    {
        $companyFields = $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.object',
                        'expr'   => 'like',
                        'value'  => 'company',
                    ],
                ],
            ],
        ]);

        return $companyFields;
    }

    /**
     * @param object $entity
     * @param bool   $unlock
     *
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $isNew = $entity->getId() ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);
        $objects = ['lead' => 'leads', 'company' => 'companies'];
        $alias   = $entity->getAlias();
        $object  = $objects[$entity->getObject()];
        $type    = $entity->getType();

        if ($type == 'time') {
            //time does not work well with list filters
            $entity->setIsListable(false);
        }

        // Save the entity now if it's an existing entity
        if (!$isNew) {
            $event = $this->dispatchEvent('pre_save', $entity, $isNew);
            $this->getRepository()->saveEntity($entity);
            $this->dispatchEvent('post_save', $entity, $isNew, $event);
        }

        // Create the field as its own column in the leads table.
        /** @var ColumnSchemaHelper $leadsSchema */
        $leadsSchema = $this->columnSchemaHelper->setName($object);
        $isUnique    = $entity->getIsUniqueIdentifier();

        // If the column does not exist in the contacts table, add it
        if (!$leadsSchema->checkColumnExists($alias)) {
            $schemaDefinition = self::getSchemaDefinition($alias, $type, $isUnique);

            $leadsSchema->addColumn($schemaDefinition);

            try {
                $leadsSchema->executeChanges();
            } catch (DriverException $e) {
                $this->logger->addWarning($e->getMessage());

                if ($e->getErrorCode() === 1118 /* ER_TOO_BIG_ROWSIZE */) {
                    throw new DBALException($this->translator->trans('mautic.core.error.max.field'));
                } else {
                    throw $e;
                }
            }

            // If this is a new contact field, and it was successfully added to the contacts table, save it
            if ($isNew === true) {
                $event = $this->dispatchEvent('pre_save', $entity, $isNew);
                $this->getRepository()->saveEntity($entity);
                $this->dispatchEvent('post_save', $entity, $isNew, $event);
            }

            // Update the unique_identifier_search index and add an index for this field
            /** @var \Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper $modifySchema */
            $modifySchema = $this->indexSchemaHelper->setName($object);

            if ('string' == $schemaDefinition['type']) {
                try {
                    $modifySchema->addIndex([$alias], $alias.'_search');
                    $modifySchema->allowColumn($alias);

                    if ($isUnique) {
                        // Get list of current uniques
                        $uniqueIdentifierFields = $this->getUniqueIdentifierFields();

                        // Always use email
                        $indexColumns   = ['email'];
                        $indexColumns   = array_merge($indexColumns, array_keys($uniqueIdentifierFields));
                        $indexColumns[] = $alias;

                        // Only use three to prevent max key length errors
                        $indexColumns = array_slice($indexColumns, 0, 3);
                        $modifySchema->addIndex($indexColumns, 'unique_identifier_search');
                    }

                    $modifySchema->executeChanges();
                } catch (DriverException $e) {
                    if ($e->getErrorCode() === 1069 /* ER_TOO_MANY_KEYS */) {
                        $this->logger->addWarning($e->getMessage());
                    } else {
                        throw $e;
                    }
                }
            }
        }

        // Update order of the other fields.
        $this->reorderFieldsByEntity($entity);
    }

    /**
     * Build schema for each entity.
     *
     * @param array $entities
     * @param bool  $unlock
     *
     * @return array|void
     *
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function saveEntities($entities, $unlock = true)
    {
        foreach ($entities as $entity) {
            $this->saveEntity($entity, $unlock);
        }
    }

    /**
     * @param object $entity
     *
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function deleteEntity($entity)
    {
        parent::deleteEntity($entity);

        switch ($entity->getObject()) {
            case 'lead':
                $this->columnSchemaHelper->setName('leads')->dropColumn($entity->getAlias())->executeChanges();
                break;
            case 'company':
                $this->columnSchemaHelper->setName('companies')->dropColumn($entity->getAlias())->executeChanges();
                break;
        }
    }

    /**
     * Delete an array of entities.
     *
     * @param array $ids
     *
     * @return array
     *
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function deleteEntities($ids)
    {
        $entities = parent::deleteEntities($ids);

        /** @var LeadField $entity */
        foreach ($entities as $entity) {
            switch ($entity->getObject()) {
                case 'lead':
                    $this->columnSchemaHelper->setName('leads')->dropColumn($entity->getAlias())->executeChanges();
                    break;
                case 'company':
                    $this->columnSchemaHelper->setName('companies')->dropColumn($entity->getAlias())->executeChanges();
                    break;
            }
        }

        return $entities;
    }

    /**
     * Is field used in segment filter?
     *
     * @param LeadField $field
     *
     * @return bool
     */
    public function isUsedField(LeadField $field)
    {
        return $this->leadListModel->isFieldUsed($field);
    }

    /**
     * Returns list of all segments that use $field.
     *
     * @param LeadField $field
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFieldSegments(LeadField $field)
    {
        return $this->leadListModel->getFieldSegments($field);
    }

    /**
     * Filter used field ids.
     *
     * @param array $ids
     *
     * @return array
     */
    public function filterUsedFieldIds(array $ids)
    {
        return array_filter($ids, function ($id) {
            return $this->isUsedField($this->getEntity($id)) === false;
        });
    }

    /**
     * Reorder fields based on passed entity position.
     *
     * @param $entity
     */
    public function reorderFieldsByEntity($entity)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        $fields = $this->getRepository()->findBy([], ['order' => 'ASC']);
        $count  = 1;
        $order  = $entity->getOrder();
        $id     = $entity->getId();
        $hit    = false;
        foreach ($fields as $field) {
            if ($id !== $field->getId()) {
                if ($order === $field->getOrder()) {
                    if ($hit) {
                        $field->setOrder($count - 1);
                    } else {
                        $field->setOrder($count + 1);
                    }
                } else {
                    $field->setOrder($count);
                }
                $this->em->persist($field);
            } else {
                $hit = true;
            }
            ++$count;
        }
        $this->em->flush();
    }

    /**
     * Reorders fields by a list of field ids.
     *
     * @param array $list
     * @param int   $start Number to start the order by (used for paginated reordering)
     */
    public function reorderFieldsByList(array $list, $start = 1)
    {
        $fields = $this->getRepository()->findBy([], ['order' => 'ASC']);
        foreach ($fields as $field) {
            if (in_array($field->getId(), $list)) {
                $order = ((int) array_search($field->getId(), $list) + $start);
                $field->setOrder($order);
                $this->em->persist($field);
            }
        }
        $this->em->flush();
    }

    /**
     * Get list of custom field values for autopopulate fields.
     *
     * @param $type
     * @param $filter
     * @param $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        return $this->em->getRepository('MauticLeadBundle:Lead')->getValueList($type, $filter, $limit);
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
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('leadfield', $entity, $options);
    }

    /**
     * @param $entity
     * @param properties
     *
     * @return bool
     */
    public function setFieldProperties(&$entity, $properties)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadEntity']);
        }

        if (!empty($properties) && is_array($properties)) {
            $properties = InputHelper::clean($properties);
        } else {
            $properties = [];
        }

        //validate properties
        $type   = $entity->getType();
        $result = FormFieldHelper::validateProperties($type, $properties);
        if ($result[0]) {
            $entity->setProperties($properties);

            return true;
        } else {
            return $result[1];
        }
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
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(['LeadField']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::FIELD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadFieldEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param bool|true $byGroup
     * @param bool|true $alphabetical
     * @param array     $filters
     *
     * @return array
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = ['isPublished' => true, 'object' => 'lead'])
    {
        $forceFilters = [];
        foreach ($filters as $col => $val) {
            $forceFilters[] = [
                'column' => "f.{$col}",
                'expr'   => 'eq',
                'value'  => $val,
            ];
        }
        // Get a list of custom form fields
        $fields = $this->getEntities([
            'filter' => [
                'force' => $forceFilters,
            ],
            'orderBy'    => 'f.order',
            'orderByDir' => 'asc',
        ]);

        $leadFields = [];

        /** @var LeadField $f * */
        foreach ($fields as $f) {
            if ($byGroup) {
                $fieldName                              = $this->translator->trans('mautic.lead.field.group.'.$f->getGroup());
                $leadFields[$fieldName][$f->getAlias()] = $f->getLabel();
            } else {
                $leadFields[$f->getAlias()] = $f->getLabel();
            }
        }

        if ($alphabetical) {
            // Sort the groups
            uksort($leadFields, 'strnatcmp');

            if ($byGroup) {
                // Sort each group by translation
                foreach ($leadFields as $group => &$fieldGroup) {
                    uasort($fieldGroup, 'strnatcmp');
                }
            }
        }

        return $leadFields;
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getPublishedFieldArrays($object = 'lead')
    {
        return $this->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                        [
                            'column' => 'f.object',
                            'expr'   => 'eq',
                            'value'  => $object,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getFieldListWithProperties($object = 'lead')
    {
        $forceFilters[] = [
            'column' => 'f.object',
            'expr'   => 'eq',
            'value'  => $object,
        ];
        $contactFields = $this->getEntities(
            [
                'filter' => [
                    'force' => $forceFilters,
                ],
                'ignore_paginator' => true,
                'hydration_mode'   => 'hydrate_array',
            ]
        );

        $fields = [];
        foreach ($contactFields as $contactField) {
            $fields[$contactField['alias']] = [
                'label'        => $contactField['label'],
                'alias'        => $contactField['alias'],
                'type'         => $contactField['type'],
                'group'        => $contactField['group'],
                'group_label'  => $this->translator->trans('mautic.lead.field.group.'.$contactField['group']),
                'defaultValue' => $contactField['defaultValue'],
                'properties'   => $contactField['properties'],
            ];
        }

        return $fields;
    }

    /**
     * Get the fields for a specific group.
     *
     * @param       $group
     * @param array $filters
     *
     * @return array
     */
    public function getGroupFields($group, $filters = ['isPublished' => true])
    {
        $forceFilters = [
            [
                'column' => 'f.group',
                'expr'   => 'eq',
                'value'  => $group,
            ],
        ];
        foreach ($filters as $col => $val) {
            $forceFilters[] = [
                'column' => "f.{$col}",
                'expr'   => 'eq',
                'value'  => $val,
            ];
        }
        // Get a list of custom form fields
        $fields = $this->getEntities([
            'filter' => [
                'force' => $forceFilters,
            ],
            'orderBy'    => 'f.order',
            'orderByDir' => 'asc',
        ]);

        $leadFields = [];

        foreach ($fields as $f) {
            $leadFields[$f->getAlias()] = $f->getLabel();
        }

        return $leadFields;
    }

    /**
     * Retrieves a list of published fields that are unique identifers.
     *
     * @deprecated to be removed in 3.0
     *
     * @return array
     */
    public function getUniqueIdentiferFields($filters = [])
    {
        return $this->getUniqueIdentifierFields($filters);
    }

    /**
     * Retrieves a list of published fields that are unique identifers.
     *
     * @param array $filters
     *
     * @return mixed
     */
    public function getUniqueIdentifierFields($filters = [])
    {
        $filters['isPublished']       = isset($filters['isPublished']) ? $filters['isPublished'] : true;
        $filters['isUniqueIdentifer'] = isset($filters['isUniqueIdentifer']) ? $filters['isUniqueIdentifer'] : true;
        $filters['object']            = isset($filters['object']) ? $filters['object'] : 'lead';

        $key = base64_encode(json_encode($filters));
        if (!isset($this->uniqueIdentifierFields[$key])) {
            $this->uniqueIdentifierFields[$key] = $this->getFieldList(false, true, $filters);
        }

        return $this->uniqueIdentifierFields[$key];
    }

    /**
     * Get the MySQL database type based on the field type
     * Use a static function so that it's accessible from DoctrineSubscriber
     * without causing a circular service injection error.
     *
     * @param      $alias
     * @param      $type
     * @param bool $isUnique
     *
     * @return array
     */
    public static function getSchemaDefinition($alias, $type, $isUnique = false)
    {
        // Unique is always a string in order to control index length
        if ($isUnique) {
            return [
                'name'    => $alias,
                'type'    => 'string',
                'options' => [
                    'notnull' => false,
                ],
            ];
        }

        switch ($type) {
            case 'datetime':
            case 'date':
            case 'time':
            case 'boolean':
                $schemaType = $type;
                break;
            case 'number':
                $schemaType = 'float';
                break;
            case 'timezone':
            case 'locale':
            case 'country':
            case 'email':
            case 'lookup':
            case 'select':
            case 'multiselect':
            case 'region':
            case 'tel':
                $schemaType = 'string';
                break;
            case 'text':
                $schemaType = (strpos($alias, 'description') !== false) ? 'text' : 'string';
                break;
            default:
                $schemaType = 'text';
        }

        return [
            'name'    => $alias,
            'type'    => $schemaType,
            'options' => ['notnull' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
    {
        return $this->getRepository()->findOneByAlias($alias);
    }
}

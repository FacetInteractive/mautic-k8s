<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Report;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Model\UserModel;

class FieldsBuilder
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var UserModel
     */
    private $userModel;

    public function __construct(FieldModel $fieldModel, ListModel $listModel, UserModel $userModel)
    {
        $this->fieldModel = $fieldModel;
        $this->listModel  = $listModel;
        $this->userModel  = $userModel;
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function getLeadFieldsColumns($prefix)
    {
        $baseColumns  = $this->getBaseLeadColumns();
        $leadFields   = $this->fieldModel->getLeadFields();
        $fieldColumns = $this->getFieldColumns($leadFields, $prefix);

        return array_merge($baseColumns, $fieldColumns);
    }

    /**
     * @param string $prefix
     * @param string $segmentPrefix
     *
     * @return array
     */
    public function getLeadFilter($prefix, $segmentPrefix)
    {
        $filters = $this->getLeadFieldsColumns($prefix);

        $segmentPrefix = $this->sanitizePrefix($segmentPrefix);
        $prefix        = $this->sanitizePrefix($prefix);

        // Append segment filters
        $userSegments = $this->listModel->getUserLists();

        $list = [];
        foreach ($userSegments as $segment) {
            $list[$segment['id']] = $segment['name'];
        }

        $segmentKey           = $segmentPrefix.'leadlist_id';
        $filters[$segmentKey] = [
            'alias'     => 'segment_id',
            'label'     => 'mautic.core.filter.lists',
            'type'      => 'select',
            'list'      => $list,
            'operators' => [
                'eq' => 'mautic.core.operator.equals',
            ],
        ];

        $ownerPrefix           = $prefix.'owner_id';
        $filters[$ownerPrefix] = [
            'label' => 'mautic.lead.list.filter.owner',
            'type'  => 'select',
            'list'  => $this->userModel->getUserList('', 0),
        ];

        return $filters;
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function getCompanyFieldsColumns($prefix)
    {
        $baseColumns   = $this->getBaseCompanyColumns();
        $companyFields = $this->fieldModel->getCompanyFields();
        $fieldColumns  = $this->getFieldColumns($companyFields, $prefix);

        return array_merge($baseColumns, $fieldColumns);
    }

    /**
     * @return array
     */
    private function getBaseLeadColumns()
    {
        $columns = [
            'l.id' => [
                'label' => 'mautic.lead.report.contact_id',
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
            ],
            'i.ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'text',
            ],
            'l.date_identified' => [
                'label'          => 'mautic.lead.report.date_identified',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(l.date_identified)',
            ],
            'l.points' => [
                'label' => 'mautic.lead.points',
                'type'  => 'int',
            ],
            'l.owner_id' => [
                'label' => 'mautic.lead.report.owner_id',
                'type'  => 'int',
                'link'  => 'mautic_user_action',
            ],
            'u.first_name' => [
                'label' => 'mautic.lead.report.owner_firstname',
                'type'  => 'string',
            ],
            'u.last_name' => [
                'label' => 'mautic.lead.report.owner_lastname',
                'type'  => 'string',
            ],
        ];

        return $columns;
    }

    /**
     * @return array
     */
    private function getBaseCompanyColumns()
    {
        $columns = [
            'comp.id' => [
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyname' => [
                'label' => 'mautic.lead.report.company.company_name',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycity' => [
                'label' => 'mautic.lead.report.company.company_city',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companystate' => [
                'label' => 'mautic.lead.report.company.company_state',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycountry' => [
                'label' => 'mautic.lead.report.company.company_country',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyindustry' => [
                'label' => 'mautic.lead.report.company.company_industry',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
        ];

        return $columns;
    }

    /**
     * @param LeadField[] $fields
     * @param string      $prefix
     *
     * @return array
     */
    private function getFieldColumns($fields, $prefix)
    {
        $prefix = $this->sanitizePrefix($prefix);

        $columns = [];
        foreach ($fields as $field) {
            switch ($field->getType()) {
                case 'boolean':
                    $type = 'bool';
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $type = 'datetime';
                    break;
                case 'time':
                    $type = 'time';
                    break;
                case 'url':
                    $type = 'url';
                    break;
                case 'email':
                    $type = 'email';
                    break;
                case 'number':
                    $type = 'float';
                    break;
                default:
                    $type = 'string';
                    break;
            }
            $columns[$prefix.$field->getAlias()] = [
                'label' => $field->getLabel(),
                'type'  => $type,
            ];
        }

        return $columns;
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    private function sanitizePrefix($prefix)
    {
        if (strpos($prefix, '.') === false) {
            $prefix .= '.';
        }

        return $prefix;
    }
}

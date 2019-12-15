<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Entity\RegexTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;

/**
 * Class BaseDecorator.
 */
class BaseDecorator implements FilterDecoratorInterface
{
    use RegexTrait;

    /**
     * @var ContactSegmentFilterOperator
     */
    protected $contactSegmentFilterOperator;

    /**
     * BaseDecorator constructor.
     *
     * @param ContactSegmentFilterOperator $contactSegmentFilterOperator
     */
    public function __construct(
        ContactSegmentFilterOperator $contactSegmentFilterOperator
    ) {
        $this->contactSegmentFilterOperator = $contactSegmentFilterOperator;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|string
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $contactSegmentFilterCrate->getField();
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isContactType()) {
            return MAUTIC_TABLE_PREFIX.'leads';
        }

        return MAUTIC_TABLE_PREFIX.'companies';
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $operator = $this->contactSegmentFilterOperator->fixOperator($contactSegmentFilterCrate->getOperator());

        switch ($operator) {
            case 'startsWith':
            case 'endsWith':
            case 'contains':
                return 'like';
                break;
        }

        return $operator;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return BaseFilterQueryBuilder::getServiceId();
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     * @param array|string              $argument
     *
     * @return array|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        if (is_array($argument)) {
            $result = [];
            foreach ($argument as $arg) {
                $result[] = $this->getParameterHolder($contactSegmentFilterCrate, $arg);
            }

            return $result;
        }

        return ':'.$argument;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return array|bool|float|null|string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $filter = $contactSegmentFilterCrate->getFilter();

        if ($contactSegmentFilterCrate->filterValueDoNotNeedAdjustment()) {
            return $filter;
        }

        switch ($contactSegmentFilterCrate->getOperator()) {
            case 'like':
            case '!like':
                return strpos($filter, '%') === false ? '%'.$filter.'%' : $filter;
            case 'contains':
                return '%'.$filter.'%';
            case 'startsWith':
                return $filter.'%';
            case 'endsWith':
                return '%'.$filter;
            case 'regexp':
            case '!regexp':
                return $this->prepareRegex($filter);
            case 'multiselect':
            case '!multiselect':
                $filter = (array) $filter;

                foreach ($filter as $key => $value) {
                    $filter[$key] = sprintf('(([|]|^)%s([|]|$))', $value);
                }

                return $filter;
        }

        return $filter;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return bool
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return false;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|CompositeExpression|string
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return null;
    }
}

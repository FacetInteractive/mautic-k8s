<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Interface FilterQueryBuilderInterface.
 */
interface FilterQueryBuilderInterface
{
    /**
     * @param QueryBuilder         $queryBuilder
     * @param ContactSegmentFilter $filter
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter);

    /**
     * @return string returns the service id in the DIC container
     */
    public static function getServiceId();
}

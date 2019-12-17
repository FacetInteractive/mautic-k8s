<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class LeadListFilteringEvent.
 *
 * Please refer to LeadListRepository.php, inside getListFilterExpr method, for examples
 */
class LeadListFilteringEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $details;

    /**
     * @var int
     */
    protected $leadId;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var bool
     */
    protected $isFilteringDone;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $subQuery;

    /**
     * @var string
     */
    protected $func;

    /**
     * @param array         $details
     * @param int           $leadId
     * @param string        $alias
     * @param string        $func
     * @param QueryBuilder  $queryBuilder
     * @param EntityManager $entityManager
     */
    public function __construct(
        $details,
        $leadId,
        $alias,
        $func,
        QueryBuilder $queryBuilder,
        EntityManager $entityManager
    ) {
        $this->details         = $details;
        $this->leadId          = $leadId;
        $this->alias           = $alias;
        $this->func            = $func;
        $this->queryBuilder    = $queryBuilder;
        $this->em              = $entityManager;
        $this->isFilteringDone = false;
        $this->subQuery        = '';
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return int
     */
    public function getLeadId()
    {
        return $this->leadId;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param bool
     */
    public function setFilteringStatus($status)
    {
        $this->isFilteringDone = $status;
    }

    /**
     * @param string
     */
    public function setSubQuery($query)
    {
        $this->subQuery = $query;

        $this->setFilteringStatus(true);
    }

    /**
     * @return array
     */
    public function isFilteringDone()
    {
        return $this->isFilteringDone;
    }

    /**
     * @return string
     */
    public function getSubQuery()
    {
        return $this->subQuery;
    }
}

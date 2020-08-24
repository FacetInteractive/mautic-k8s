<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * LeadRepository.
 */
class LeadRepository extends CommonRepository implements CustomFieldRepositoryInterface
{
    use CustomFieldRepositoryTrait {
        prepareDbalFieldsForSave as defaultPrepareDbalFieldsForSave;
    }

    use ExpressionHelperTrait;
    use OperatorListTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    private $availableSocialFields = [];

    /**
     * @var array
     */
    private $availableSearchFields = [];

    /**
     * Required to get the color based on a lead's points.
     *
     * @var TriggerModel
     */
    private $triggerModel;

    /**
     * Used by search functions to search social profiles.
     *
     * @param array $fields
     */
    public function setAvailableSocialFields(array $fields)
    {
        $this->availableSocialFields = $fields;
    }

    /**
     * Used by search functions to search using aliases as commands.
     *
     * @param array $fields
     */
    public function setAvailableSearchFields(array $fields)
    {
        $this->availableSearchFields = $fields;
    }

    /**
     * Sets trigger model.
     *
     * @param TriggerModel $triggerModel
     */
    public function setTriggerModel(TriggerModel $triggerModel)
    {
        $this->triggerModel = $triggerModel;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get a list of leads based on field value.
     *
     * @param string $field
     * @param string $value
     * @param ?int   $ignoreId
     * @param bool   $indexByColumn
     *
     * @return array
     */
    public function getLeadsByFieldValue($field, $value, $ignoreId = null, $indexByColumn = false)
    {
        $results = $this->getEntities([
            'qb'               => $this->buildQueryForGetLeadsByFieldValue($field, $value, $ignoreId),
            'ignore_paginator' => true,
        ]);

        if (!$indexByColumn) {
            return $results;
        }

        return array_combine(array_map(function (Lead $lead) use ($field) {
            return $lead->getFieldValue($field);
        }, $results), $results);
    }

    /**
     * Builds the query for the getLeadsByFieldValue method.
     *
     * @internal
     *
     * @param string $field
     * @param string $value
     * @param ?int   $ignoreId
     *
     * @return QueryBuilder
     */
    protected function buildQueryForGetLeadsByFieldValue($field, $value, $ignoreId = null)
    {
        $col = 'l.'.$field;

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        if ($ignoreId) {
            $q->where('l.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        if (is_array($value)) {
            /**
             * Bind each value to specific named parameters.
             *
             * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/query-builder.html#line-number-0a267d5a2c69797a7656aae33fcc140d16b0a566-72
             */
            $valueParams = [];
            for ($i = 0; $i < count($value); ++$i) {
                $valueParams[':'.$this->generateRandomParameterName()] = $value[$i];
            }

            $q->andWhere(
                $q->expr()->in($col, array_keys($valueParams))
            );

            foreach ($valueParams as $param => $value) {
                $q->setParameter(ltrim($param, ':'), $value);
            }

            return $q;
        }

        $q->andWhere("$col = :search")->setParameter('search', $value);

        return $q;
    }

    /**
     * @param $email
     *
     * @return Lead[]
     */
    public function getContactsByEmail($email)
    {
        $contacts = $this->getLeadsByFieldValue('email', $email);

        // Attempt to search for contacts without a + suffix
        if (empty($contacts) && preg_match('#^(.*?)\+(.*?)@(.*?)$#', $email, $parts)) {
            $email    = $parts[1].'@'.$parts[3];
            $contacts = $this->getLeadsByFieldValue('email', $email);
        }

        return $contacts;
    }

    /**
     * Get a list of lead entities.
     *
     * @param     $uniqueFieldsWithData
     * @param int $leadId
     * @param int $limit
     *
     * @return array
     */
    public function getLeadsByUniqueFields($uniqueFieldsWithData, $leadId = null, $limit = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.*')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        // loop through the fields and
        foreach ($uniqueFieldsWithData as $col => $val) {
            $q->orWhere("l.$col = :".$col)
                ->setParameter($col, $val);
        }

        // if we have a lead ID lets use it
        if (!empty($leadId)) {
            // make sure that its not the id we already have
            $q->andWhere('l.id != :leadId')
                ->setParameter('leadId', $leadId);
        }

        if ($limit) {
            $q->setMaxResults($limit);
        }

        $results = $q->execute()->fetchAll();

        // Collect the IDs
        $leads = [];
        foreach ($results as $r) {
            $leads[$r['id']] = $r;
        }

        // Get entities
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from('MauticLeadBundle:Lead', 'l');

        $q->where(
            $q->expr()->in('l.id', ':ids')
        )
            ->setParameter('ids', array_keys($leads))
            ->orderBy('l.dateAdded', 'DESC')
            ->addOrderBy('l.id', 'DESC');
        $entities = $q->getQuery()
            ->getResult();

        /** @var Lead $lead */
        foreach ($entities as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
            if (!empty($this->triggerModel)) {
                $lead->setColor($this->triggerModel->getColorForLeadPoints($lead->getPoints()));
            }

            $lead->setFields(
                $this->formatFieldValues($leads[$lead->getId()])
            );
        }

        return $entities;
    }

    /**
     * Get list of lead Ids by unique field data.
     *
     * @param $uniqueFieldsWithData is an array of columns & values to filter by
     * @param int $leadId is the current lead id. Added to query to skip and find other leads
     *
     * @return array
     */
    public function getLeadIdsByUniqueFields($uniqueFieldsWithData, $leadId = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        // loop through the fields and
        foreach ($uniqueFieldsWithData as $col => $val) {
            $q->orWhere("l.$col = :".$col)
                ->setParameter($col, $val);
        }

        // if we have a lead ID lets use it
        if (!empty($leadId)) {
            // make sure that its not the id we already have
            $q->andWhere('l.id != '.$leadId);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param string $email
     * @param bool   $all   Set to true to return all matching lead id's
     *
     * @return array|null
     */
    public function getLeadByEmail($email, $all = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('email = :search')
            ->setParameter('search', $email);

        $result = $q->execute()->fetchAll();

        if (count($result)) {
            return $all ? $result : $result[0];
        } else {
            return;
        }
    }

    /**
     * Get leads by IP address.
     *
     * @param      $ip
     * @param bool $byId
     *
     * @return array
     */
    public function getLeadsByIp($ip, $byId = false)
    {
        $q = $this->createQueryBuilder('l')
            ->leftJoin('l.ipAddresses', 'i');
        $col = ($byId) ? 'i.id' : 'i.ipAddress';
        $q->where($col.' = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.dateAdded', 'DESC');
        $results = $q->getQuery()->getResult();

        /** @var Lead $lead */
        foreach ($results as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
        }

        return $results;
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getLead($id)
    {
        $fq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $fq->select('l.*')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('l.id = '.$id);
        $results = $fq->execute()->fetchAll();

        return (isset($results[0])) ? $results[0] : [];
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function getEntity($id = 0)
    {
        try {
            $q = $this->createQueryBuilder($this->getTableAlias());
            if (is_array($id)) {
                $this->buildSelectClause($q, $id);
                $contactId = (int) $id['id'];
            } else {
                $q->select('l, u, i')
                    ->leftJoin('l.ipAddresses', 'i')
                    ->leftJoin('l.owner', 'u');
                $contactId = $id;
            }
            $q->andWhere($this->getTableAlias().'.id = :id')
                ->setParameter('id', (int) $contactId);
            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        if (null === $entity) {
            return $entity;
        }

        if ($entity->getFields()) {
            // Pulled from Doctrine memory so don't make unnecessary queries as this has already happened
            return $entity;
        }

        if (!empty($this->triggerModel)) {
            $entity->setColor($this->triggerModel->getColorForLeadPoints($entity->getPoints()));
        }

        $fieldValues = $this->getFieldValues($id);
        $entity->setFields($fieldValues);

        $entity->setAvailableSocialFields($this->availableSocialFields);

        return $entity;
    }

    /**
     * Get a contact entity with the primary company data populated.
     *
     * The primary company data will be a flat array on the entity
     * with a key of `primaryCompany`
     *
     * @param mixed $entity
     *
     * @return mixed|null
     */
    public function getEntityWithPrimaryCompany($entity)
    {
        if (is_int($entity)) {
            $entity = $this->getEntity($entity);
        }

        if ($entity instanceof Lead) {
            $id        = $entity->getId();
            $companies = $this->getEntityManager()->getRepository('MauticLeadBundle:Company')->getCompaniesForContacts([$id]);

            if (!empty($companies[$id])) {
                $primary = null;

                foreach ($companies as $company) {
                    if (isset($company['is_primary']) && $company['is_primary'] == 1) {
                        $primary = $company;
                    }
                }

                if (empty($primary)) {
                    $primary = $companies[$id][0];
                }

                $entity->setPrimaryCompany($primary);
            }
        }

        return $entity;
    }

    /**
     * Get a list of leads.
     *
     * @param array $args
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        $contacts = $this->getEntitiesWithCustomFields(
            'lead',
            $args,
            function ($r) {
                if (!empty($this->triggerModel)) {
                    $r->setColor($this->triggerModel->getColorForLeadPoints($r->getPoints()));
                }
                $r->setAvailableSocialFields($this->availableSocialFields);
            }
        );

        $contactCount = isset($contacts['results']) ? count($contacts['results']) : count($contacts);
        if ($contactCount && (!empty($args['withPrimaryCompany']) || !empty($args['withChannelRules']))) {
            $withTotalCount = (array_key_exists('withTotalCount', $args) && $args['withTotalCount']);
            /** @var Lead[] $tmpContacts */
            $tmpContacts = ($withTotalCount) ? $contacts['results'] : $contacts;

            $withCompanies   = !empty($args['withPrimaryCompany']);
            $withPreferences = !empty($args['withChannelRules']);
            $contactIds      = array_keys($tmpContacts);

            if ($withCompanies) {
                $companies = $this->getEntityManager()->getRepository('MauticLeadBundle:Company')->getCompaniesForContacts($contactIds);
            }

            if ($withPreferences) {
                /** @var FrequencyRuleRepository $frequencyRepo */
                $frequencyRepo  = $this->getEntityManager()->getRepository('MauticLeadBundle:FrequencyRule');
                $frequencyRules = $frequencyRepo->getFrequencyRules(null, $contactIds);

                /** @var DoNotContactRepository $dncRepository */
                $dncRepository = $this->getEntityManager()->getRepository('MauticLeadBundle:DoNotContact');
                $dncRules      = $dncRepository->getChannelList(null, $contactIds);
            }

            foreach ($contactIds as $id) {
                if ($withCompanies && isset($companies[$id]) && !empty($companies[$id])) {
                    $primary = null;

                    // Try to find the primary company
                    foreach ($companies[$id] as $company) {
                        if ($company['is_primary'] == 1) {
                            $primary = $company;
                        }
                    }

                    // If no primary was found, just grab the first
                    if (empty($primary)) {
                        $primary = $companies[$id][0];
                    }

                    if (is_array($tmpContacts[$id])) {
                        $tmpContacts[$id]['primaryCompany'] = $primary;
                    } elseif ($tmpContacts[$id] instanceof Lead) {
                        $tmpContacts[$id]->setPrimaryCompany($primary);
                    }
                }

                if ($withPreferences) {
                    $contactFrequencyRules = (isset($frequencyRules[$id])) ? $frequencyRules[$id] : [];
                    $contactDncRules       = (isset($dncRules[$id])) ? $dncRules[$id] : [];

                    $channelRules = Lead::generateChannelRules($contactFrequencyRules, $contactDncRules);
                    if (is_array($tmpContacts[$id])) {
                        $tmpContacts[$id]['channelRules'] = $channelRules;
                    } elseif ($tmpContacts[$id] instanceof Lead) {
                        $tmpContacts[$id]->setChannelRules($channelRules);
                    }
                }
            }

            if ($withTotalCount) {
                $contacts['results'] = $tmpContacts;
            } else {
                $contacts = $tmpContacts;
            }
        }

        return $contacts;
    }

    /**
     * @return array
     */
    public function getFieldGroups()
    {
        return ['core', 'social', 'personal', 'professional'];
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getEntitiesDbalQueryBuilder()
    {
        $alias = $this->getTableAlias();
        $dq    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'leads', $alias)
            ->leftJoin($alias, MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = '.$alias.'.owner_id');

        return $dq;
    }

    /**
     * @param $order
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEntitiesOrmQueryBuilder($order)
    {
        $alias = $this->getTableAlias();
        $q     = $this->getEntityManager()->createQueryBuilder();
        $q->select($alias.', u, i,'.$order)
            ->from('MauticLeadBundle:Lead', $alias, $alias.'.id')
            ->leftJoin($alias.'.ipAddresses', 'i')
            ->leftJoin($alias.'.owner', 'u')
            ->indexBy($alias, $alias.'.id');

        return $q;
    }

    /**
     * Get contacts for a specific channel entity.
     *
     * @param $args - same as getEntity/getEntities
     * @param        $joinTable
     * @param        $entityId
     * @param array  $filters
     * @param string $entityColumnName
     * @param array  $additionalJoins  [ ['type' => 'join|leftJoin', 'from_alias' => '', 'table' => '', 'condition' => ''], ... ]
     *
     * @return array
     */
    public function getEntityContacts($args, $joinTable, $entityId, $filters = [], $entityColumnName = 'id', array $additionalJoins = null, $contactColumnName = 'lead_id')
    {
        $qb = $this->getEntitiesDbalQueryBuilder();

        if (empty($contactColumnName)) {
            $contactColumnName = 'lead_id';
        }

        $joinCondition = $qb->expr()->andX(
            $qb->expr()->eq($this->getTableAlias().'.id', 'entity.'.$contactColumnName)
        );

        if ($entityId && $entityColumnName) {
            $joinCondition->add(
                $qb->expr()->eq("entity.{$entityColumnName}", (int) $entityId)
            );
        }

        $qb->join(
            $this->getTableAlias(),
            MAUTIC_TABLE_PREFIX.$joinTable,
            'entity',
            $joinCondition
        );

        if (is_array($additionalJoins)) {
            foreach ($additionalJoins as $t) {
                $qb->{$t['type']}(
                    $t['from_alias'],
                    MAUTIC_TABLE_PREFIX.$t['table'],
                    $t['alias'],
                    $t['condition']
                );
            }
        }

        if ($filters) {
            $expr = $qb->expr()->andX();
            foreach ($filters as $column => $value) {
                if (is_array($value)) {
                    $this->buildWhereClauseFromArray($qb, [$value]);
                } else {
                    if (strpos($column, '.') === false) {
                        $column = "entity.$column";
                    }

                    $expr->add(
                        $qb->expr()->eq($column, $qb->createNamedParameter($value))
                    );
                    $qb->andWhere($expr);
                }
            }
        }

        $args['qb'] = $qb;

        return $this->getEntities($args);
    }

    /**
     * Adds the "catch all" where clause to the QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        $columns = array_merge(
            [
                'l.firstname',
                'l.lastname',
                'l.email',
                'l.company',
                'l.city',
                'l.state',
                'l.zipcode',
                'l.country',
            ],
            $this->availableSocialFields
        );

        return $this->addStandardCatchAllWhereClause($q, $filter, $columns);
    }

    /**
     * Adds the command where clause to the QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        $command                 = $filter->command;
        $string                  = $filter->string;
        $unique                  = $this->generateRandomParameterName();
        $returnParameter         = false; //returning a parameter that is not used will lead to a Doctrine error
        list($expr, $parameters) = parent::addSearchCommandWhereClause($q, $filter);

        //DBAL QueryBuilder does not have an expr()->not() function; boo!!

        // This will be switched by some commands that use join tables as NOT EXISTS queries will be used
        $exprType = ($filter->not) ? 'negate_expr' : 'expr';

        $operators = $this->getFilterExpressionFunctions();
        $operators = array_merge($operators, [
            'x' => [
                'expr'        => 'andX',
                'negate_expr' => 'orX',
            ],
            'null' => [
                'expr'        => 'isNull',
                'negate_expr' => 'isNotNull',
            ],
        ]);

        $innerJoinTables = (isset($this->advancedFilterCommands[$command])
            && SearchStringHelper::COMMAND_NEGATE !== $this->advancedFilterCommands[$command]);
        $likeExpr = $operators['like'][$exprType];
        $eqExpr   = $operators['='][$exprType];
        $nullExpr = $operators['null'][$exprType];
        $inExpr   = $operators['in'][$exprType];
        $xExpr    = $operators['x'][$exprType];

        switch ($command) {
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous', [], null, 'en_US'):
                $expr = $q->expr()->$nullExpr('l.date_identified');
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
            case $this->translator->trans('mautic.core.searchcommand.ismine', [], null, 'en_US'):
                $expr = $q->expr()->$eqExpr('l.owner_id', $this->currentUser->getId());
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned', [], null, 'en_US'):
                $expr = $q->expr()->orX(
                    $q->expr()->$eqExpr('l.owner_id', 0),
                    $q->expr()->$nullExpr('l.owner_id')
                );
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner', [], null, 'en_US'):
                $expr = $q->expr()->orX(
                    $q->expr()->$likeExpr('u.first_name', ':'.$unique),
                    $q->expr()->$likeExpr('u.last_name', ':'.$unique)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr = $q->expr()->orX(
                    $q->expr()->$likeExpr('l.firstname', ":$unique"),
                    $q->expr()->$likeExpr('l.lastname', ":$unique")
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.email'):
            case $this->translator->trans('mautic.core.searchcommand.email', [], null, 'en_US'):
                $expr            = $q->expr()->$likeExpr('l.email', ":$unique");
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.list'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.list', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'lead_lists_leads',
                            'alias'      => 'list_lead',
                            'condition'  => 'l.id = list_lead.lead_id',
                        ],
                        [
                            'from_alias' => 'list_lead',
                            'table'      => 'lead_lists',
                            'alias'      => 'list',
                            'condition'  => 'list_lead.leadlist_id = list.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'list.alias', $eqExpr, $unique, ($filter->not) ? true : null,
                        // orX for filter->not either manuall removed or is null
                        $q->expr()->$xExpr(
                            $q->expr()->$eqExpr('list_lead.manually_removed', 0)
                        )
                    )
                );
                $filter->strict  = true;
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.core.searchcommand.ip'):
            case $this->translator->trans('mautic.core.searchcommand.ip', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'lead_ips_xref',
                            'alias'      => 'ip_lead',
                            'condition'  => 'l.id = ip_lead.lead_id',
                        ],
                        [
                            'from_alias' => 'ip_lead',
                            'table'      => 'ip_addresses',
                            'alias'      => 'ip',
                            'condition'  => 'ip_lead.ip_id = ip.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'ip.ip_address', $likeExpr, $unique, null)
                );
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.duplicate'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.duplicate', [], null, 'en_US'):
                $prateek  = explode('+', $string);
                $imploder = [];

                foreach ($prateek as $key => $value) {
                    $list       = $this->getEntityManager()->getRepository('MauticLeadBundle:LeadList')->findOneByAlias($value);
                    $imploder[] = ((!empty($list)) ? (int) $list->getId() : 0);
                }

                //logic. In query, Sum(manually_removed) should be less than the current)
                $pluck    = count($imploder);
                $imploder = (string) (implode(',', $imploder));

                $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $sq->select('duplicate.lead_id')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'duplicate')
                    ->where(
                        $q->expr()->andX(
                            $q->expr()->in('duplicate.leadlist_id', $imploder),
                            $q->expr()->eq('duplicate.manually_removed', 0)
                        )
                    )
                    ->groupBy('duplicate.lead_id')
                    ->having("COUNT(duplicate.lead_id) = $pluck");

                $expr            = $q->expr()->$inExpr('l.id', sprintf('(%s)', $sq->getSQL()));
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.tag'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.tag', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'lead_tags_xref',
                            'alias'      => 'xtag',
                            'condition'  => 'l.id = xtag.lead_id',
                        ],
                        [
                            'from_alias' => 'xtag',
                            'table'      => 'lead_tags',
                            'alias'      => 'tag',
                            'condition'  => 'xtag.tag_id = tag.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'tag.tag', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.company'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.company', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'companies_leads',
                            'alias'      => 'comp_lead',
                            'condition'  => 'l.id = comp_lead.lead_id',
                        ],
                        [
                            'from_alias' => 'comp_lead',
                            'table'      => 'companies',
                            'alias'      => 'comp',
                            'condition'  => 'comp_lead.company_id = comp.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'comp.companyname', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.stage'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.stage', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'stages',
                            'alias'      => 's',
                            'condition'  => 'l.stage_id = s.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 's.name', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            default:
                if (in_array($command, $this->availableSearchFields)) {
                    $expr = $q->expr()->$likeExpr("l.$command", ":$unique");
                }
                $returnParameter = true;
                break;
        }

        if ($this->dispatcher) {
            $event = new LeadBuildSearchEvent($filter->string, $filter->command, $unique, $filter->not, $q);
            $this->dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
            if ($event->isSearchDone()) {
                $returnParameter = $event->getReturnParameters();
                $filter->strict  = $event->getStrict();
                $expr            = $event->getSubQuery();
                $parameters      = array_merge($parameters, $event->getParameters());
            }
        }

        if ($returnParameter) {
            $string              = ($filter->strict) ? $filter->string : "{$filter->string}%";
            $parameters[$unique] = $string;
        }

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * Returns the array of search commands.
     *
     * @return array
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.lead.lead.searchcommand.isanonymous',
            'mautic.core.searchcommand.ismine',
            'mautic.lead.lead.searchcommand.isunowned',
            'mautic.lead.lead.searchcommand.list',
            'mautic.core.searchcommand.name',
            'mautic.lead.lead.searchcommand.company',
            'mautic.core.searchcommand.email',
            'mautic.lead.lead.searchcommand.owner',
            'mautic.core.searchcommand.ip',
            'mautic.lead.lead.searchcommand.tag',
            'mautic.lead.lead.searchcommand.stage',
            'mautic.lead.lead.searchcommand.duplicate',
            'mautic.lead.lead.searchcommand.email_sent',
            'mautic.lead.lead.searchcommand.email_read',
            'mautic.lead.lead.searchcommand.email_queued',
            'mautic.lead.lead.searchcommand.email_pending',
            'mautic.lead.lead.searchcommand.page_source',
            'mautic.lead.lead.searchcommand.page_source_id',
            'mautic.lead.lead.searchcommand.page_id',
            'mautic.lead.lead.searchcommand.sms_sent',
            'mautic.lead.lead.searchcommand.web_sent',
            'mautic.lead.lead.searchcommand.mobile_sent',
        ];

        if (!empty($this->availableSearchFields)) {
            $commands = array_merge($commands, $this->availableSearchFields);
        }

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * Returns the array of columns with the default order.
     *
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [
            ['l.last_active', 'DESC'],
        ];
    }

    /**
     * Updates lead's lastActive with now date/time.
     *
     * @param int $leadId
     */
    public function updateLastActive($leadId)
    {
        $dt     = new DateTimeHelper();
        $fields = ['last_active' => $dt->toUtcString()];

        $this->getEntityManager()->getConnection()->update(MAUTIC_TABLE_PREFIX.'leads', $fields, ['id' => $leadId]);
    }

    /**
     * Gets the ID of the latest ID.
     *
     * @return int
     */
    public function getMaxLeadId()
    {
        $result = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('max(id) as max_lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->execute()->fetchAll();

        return $result[0]['max_lead_id'];
    }

    /**
     * Gets names, signature and email of the user(lead owner).
     *
     * @param int $ownerId
     *
     * @return array|false
     */
    public function getLeadOwner($ownerId)
    {
        if (!$ownerId) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('u.id, u.first_name, u.last_name, u.email, u.signature')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->where('u.id = :ownerId')
            ->setParameter('ownerId', (int) $ownerId);

        $result = $q->execute()->fetch();

        // Fix the HTML markup
        if (is_array($result)) {
            foreach ($result as &$field) {
                $field = html_entity_decode($field);
            }
        }

        return $result;
    }

    /**
     * Check lead owner.
     *
     * @param Lead  $lead
     * @param array $ownerIds
     *
     * @return array|false
     */
    public function checkLeadOwner(Lead $lead, $ownerIds = [])
    {
        if (empty($ownerIds)) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('u.id')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->join('u', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.owner_id = u.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('u.id', ':ownerIds'),
                    $q->expr()->eq('l.id', ':leadId')
                )
            )
            ->setParameter('ownerIds', implode(',', $ownerIds))
            ->setParameter('leadId', $lead->getId());

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * @param array $contactIds
     *
     * @return array
     */
    public function getContacts(array $contactIds)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('l.*')->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $qb->expr()->in('l.id', $contactIds)
            );

        $results = $qb->execute()->fetchAll();

        if ($results) {
            $contacts = [];
            foreach ($results as $result) {
                $contacts[$result['id']] = $result;
            }

            return $contacts;
        }

        return [];
    }

    /**
     * @param array $ids
     *
     * @return ArrayCollection
     */
    public function getContactCollection(array $ids)
    {
        if (empty($ids)) {
            return new ArrayCollection();
        }

        $contacts = $this->getEntities(
            [
                'filter'             => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'in',
                            'value'  => $ids,
                        ],
                    ],
                ],
                'orderBy'            => 'l.id',
                'orderByDir'         => 'asc',
                'withPrimaryCompany' => true,
                'withChannelRules'   => true,
            ]
        );

        return new ArrayCollection($contacts);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'l';
    }

    /**
     * Get the count of identified contacts.
     *
     * @return int
     */
    public function getIdentifiedContactCount()
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getTableName(), $this->getTableAlias());

        $qb->where(
            $qb->expr()->isNotNull($this->getTableAlias().'.date_identified')
        );

        return (int) $qb->execute()->fetchColumn();
    }

    /**
     * Get the next contact after an specific ID; mainly used in deduplication.
     *
     * @return Lead
     */
    public function getNextIdentifiedContact($lastId)
    {
        $alias = $this->getTableAlias();
        $qb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select("$alias.id")
            ->from($this->getTableName(), $this->getTableAlias());

        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->gt("$alias.id", (int) $lastId),
                $qb->expr()->isNotNull("$alias.date_identified")
            )
        )
            ->orderBy("$alias.id")
            ->setMaxResults(1);

        $next = $qb->execute()->fetchColumn();

        return ($next) ? $this->getEntity($next) : null;
    }

    /**
     * @param QueryBuilder $q
     * @param array        $tables          $tables[0] should be primary table
     * @param bool         $innerJoinTables
     * @param null         $whereExpression
     * @param null         $having
     */
    public function applySearchQueryRelationship(QueryBuilder $q, array $tables, $innerJoinTables, $whereExpression = null, $having = null)
    {
        $primaryTable = $tables[0];
        unset($tables[0]);

        $joinType = ($innerJoinTables) ? 'join' : 'leftJoin';

        $this->useDistinctCount = true;
        $joins                  = $q->getQueryPart('join');
        if (!preg_match('/"'.preg_quote($primaryTable['alias'], '/').'"/i', json_encode($joins))) {
            $q->$joinType(
                $primaryTable['from_alias'],
                MAUTIC_TABLE_PREFIX.$primaryTable['table'],
                $primaryTable['alias'],
                $primaryTable['condition']
            );
        }
        foreach ($tables as $table) {
            $q->$joinType($table['from_alias'], MAUTIC_TABLE_PREFIX.$table['table'], $table['alias'], $table['condition']);
        }

        if ($whereExpression) {
            $q->andWhere($whereExpression);
        }

        if ($having) {
            $q->andHaving($having);
        }
        $q->groupBy('l.id');
    }

    /**
     * @param array $changes
     * @param       $id
     * @param int   $tries
     */
    protected function updateContactPoints(array $changes, $id, $tries = 1)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'leads')
            ->where('id = '.$id);

        $ph = 0;
        // Keep operator in same order as was used in Lead::adjustPoints() in order to be congruent with what was calculated in PHP
        // Again ignoring Aunt Sally here (PEMDAS)
        foreach ($changes as $operator => $points) {
            $qb->set('points', 'points '.$operator.' :points'.$ph)
                ->setParameter('points'.$ph, $points, \PDO::PARAM_INT);

            ++$ph;
        }

        try {
            $qb->execute();
        } catch (DriverException $exception) {
            $message = $exception->getMessage();

            if (strpos($message, 'Deadlock') !== false && $tries <= 3) {
                ++$tries;

                $this->updateContactPoints($changes, $id, $tries);
            }
        }

        // Query new points
        return (int) $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.points')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('l.id = '.$id)
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param Lead $entity
     */
    protected function postSaveEntity($entity)
    {
        // Check if points need to be appended
        if ($entity->getPointChanges()) {
            $newPoints = $this->updateContactPoints($entity->getPointChanges(), $entity->getId());

            // Set actual points so that code using getPoints knows the true value
            $entity->setActualPoints($newPoints);

            $changes = $entity->getChanges();

            if (isset($changes['points'])) {
                // Let's adjust the points to be more accurate in the change log
                $changes['points'][1] = $newPoints;
                $entity->setChanges($changes);
            }
        }
    }

    /**
     * @param $fields
     */
    protected function prepareDbalFieldsForSave(&$fields)
    {
        // Do not save points as they are handled by postSaveEntity
        unset($fields['points']);

        $this->defaultPrepareDbalFieldsForSave($fields);
    }
}

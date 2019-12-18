<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * IntegrationRepository.
 */
class IntegrationEntityRepository extends CommonRepository
{
    /**
     * @param      $integration
     * @param      $integrationEntity
     * @param      $internalEntity
     * @param null $internalEntityId
     * @param null $startDate
     * @param null $endDate
     * @param bool $push
     * @param int  $start
     * @param int  $limit
     *
     * @return array
     */
    public function getIntegrationsEntityId(
        $integration,
        $integrationEntity,
        $internalEntity,
        $internalEntityIds = null,
        $startDate = null,
        $endDate = null,
        $push = false,
        $start = 0,
        $limit = 0,
        $integrationEntityIds = null
    ) {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('DISTINCT(i.integration_entity_id), i.id, i.internal_entity_id, i.integration_entity, i.internal_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        $q->where('i.integration = :integration')
            ->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        if ($integrationEntity) {
            $q->andWhere('i.integration_entity = :integrationEntity')
                ->setParameter('integrationEntity', $integrationEntity);
        }

        if ($push) {
            $q->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id and l.last_active >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($internalEntityIds) {
            if (is_array($internalEntityIds)) {
                $q->andWhere('i.internal_entity_id in (:internalEntityIds)')
                    ->setParameter('internalEntityIds', $internalEntityIds, Connection::PARAM_STR_ARRAY);
            } else {
                $q->andWhere('i.internal_entity_id = :internalEntityId')
                    ->setParameter('internalEntityId', $internalEntityIds);
            }
        }

        if ($startDate and !$push) {
            $q->andWhere('i.last_sync_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate and !$push) {
            $q->andWhere('i.last_sync_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($integrationEntityIds) {
            if (is_array($integrationEntityIds)) {
                $q->andWhere('i.integration_entity_id in (:integrationEntityIds)')
                    ->setParameter('integrationEntityIds', $integrationEntityIds, Connection::PARAM_STR_ARRAY);
            } else {
                $q->andWhere('i.integration_entity_id = :integrationEntityId')
                    ->setParameter('integrationEntityId', $integrationEntityIds);
            }
        }

        if ($start) {
            $q->setFirstResult((int) $start);
        }

        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param      $integration
     * @param      $integrationEntity
     * @param      $internalEntity
     * @param      $internalEntityId
     * @param null $leadFields
     *
     * @return array
     */
    public function getIntegrationEntity($integration, $integrationEntity, $internalEntity, $internalEntityId, $leadFields = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i')
            ->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id');
        $q->select('i.integration_entity_id, i.integration_entity, i.id, i.internal_entity_id');
        if ($leadFields) {
            $q->addSelect($leadFields);
        }

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('i.integration', ':integration'),
                $q->expr()->eq('i.internal_entity', ':internalEntity'),
                $q->expr()->eq('i.integration_entity', ':integrationEntity'),
                $q->expr()->eq('i.internal_entity_id', (int) $internalEntityId)
            )
        )
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity)
            ->setParameter('integrationEntity', $integrationEntity)
            ->setMaxResults(1);

        $results = $q->execute()->fetchAll();

        return ($results) ? $results[0] : null;
    }

    /**
     * @param      $integration
     * @param      $integrationEntity
     * @param      $internalEntity
     * @param      $internalEntityId
     * @param null $leadFields
     *
     * @return IntegrationEntity[]
     */
    public function getIntegrationEntities($integration, $integrationEntity, $internalEntity, $internalEntityIds)
    {
        $q = $this->createQueryBuilder('i', 'i.internalEntityId');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('i.integration', ':integration'),
                $q->expr()->eq('i.internalEntity', ':internalEntity'),
                $q->expr()->eq('i.integrationEntity', ':integrationEntity'),
                $q->expr()->in('i.internalEntityId', ':internalEntityIds')
            )
        )
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity)
            ->setParameter('integrationEntity', $integrationEntity)
            ->setParameter('internalEntityIds', $internalEntityIds);

        $results = $q->getQuery()->getResult();

        return $results;
    }

    /**
     * @param       $integration
     * @param       $internalEntity
     * @param       $leadFields
     * @param int   $limit
     * @param null  $fromDate
     * @param null  $toDate
     * @param array $integrationEntity
     * @param array $excludeIntegrationIds
     */
    public function findLeadsToUpdate(
        $integration,
        $internalEntity,
        $leadFields,
        $limit = 25,
        $fromDate = null,
        $toDate = null,
        $integrationEntity = ['Contact', 'Lead'],
        $excludeIntegrationIds = []
    ) {
        if ($internalEntity == 'company') {
            $joinTable = 'companies';
        } else {
            $joinTable = 'leads';
        }
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i')
            ->join('i', MAUTIC_TABLE_PREFIX.$joinTable, 'l', 'l.id = i.internal_entity_id');

        if (false === $limit) {
            $q->select('count(i.integration_entity_id) as total');

            if ($integrationEntity) {
                $q->addSelect('i.integration_entity');
            }
        } else {
            $q->select('i.integration_entity_id, i.integration_entity, i.id, i.internal_entity_id,'.$leadFields);
        }

        $q->where('i.integration = :integration');

        if ($integrationEntity) {
            if (!is_array($integrationEntity)) {
                $integrationEntity = [$integrationEntity];
            }
            $sub = $q->expr()->orX();
            foreach ($integrationEntity as $key => $entity) {
                $sub->add($q->expr()->eq('i.integration_entity', ':entity'.$key));
                $q->setParameter(':entity'.$key, $entity);
            }
            $q->andWhere($sub);
        }

        $q->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        if (!empty($excludeIntegrationIds)) {
            $q->andWhere(
                $q->expr()->notIn(
                    'i.integration_entity_id',
                    array_map(
                        function ($x) {
                            return "'".$x."'";
                        },
                        $excludeIntegrationIds
                    )
                )
            );
        }

        $q->andWhere(
                $q->expr()->andX(
                    $q->expr()->isNotNull('i.integration_entity_id'),
                    $q->expr()->orX(
                        $q->expr()->andX(
                            $q->expr()->isNotNull('i.last_sync_date'),
                            $q->expr()->gt('l.date_modified', 'i.last_sync_date')
                        ),
                        $q->expr()->andX(
                            $q->expr()->isNull('i.last_sync_date'),
                            $q->expr()->isNotNull('l.date_modified'),
                            $q->expr()->gt('l.date_modified', 'l.date_added')
                        )
                    )
                )
            );

        if ($internalEntity == 'lead') {
            $q->andWhere(
                $q->expr()->andX($q->expr()->isNotNull('l.email')));
        } else {
            $q->andWhere(
                $q->expr()->andX($q->expr()->isNotNull('l.companyname')));
        }

        if ($fromDate) {
            if ($toDate) {
                $q->andWhere(
                    $q->expr()->comparison('l.date_modified', 'BETWEEN', ':dateFrom and :dateTo')
                )
                    ->setParameter('dateFrom', $fromDate)
                    ->setParameter('dateTo', $toDate);
            } else {
                $q->andWhere(
                    $q->expr()->gte('l.date_modified', ':dateFrom')
                )
                    ->setParameter('dateFrom', $fromDate);
            }
        } elseif ($toDate) {
            $q->andWhere(
                $q->expr()->lte('l.date_modified', ':dateTo')
            )
                ->setParameter('dateTo', $toDate);
        }

        // Group by email to prevent duplicates from affecting this

        if (false === $limit and $integrationEntity) {
            $q->groupBy('i.integration_entity')->having('total');
        }
        if ($limit) {
            $q->setMaxResults($limit);
        }
        $results = $q->execute()->fetchAll();
        $leads   = [];

        if ($integrationEntity) {
            foreach ($integrationEntity as $entity) {
                $leads[$entity] = (false === $limit) ? 0 : [];
            }
        }

        foreach ($results as $result) {
            if ($integrationEntity) {
                if (false === $limit) {
                    $leads[$result['integration_entity']] = (int) $result['total'];
                } else {
                    $leads[$result['integration_entity']][$result['internal_entity_id']] = $result;
                }
            } else {
                $leads[$result['internal_entity_id']] = $result['internal_entity_id'];
            }
        }

        return $leads;
    }

    /**
     * @param      $integration
     * @param      $leadFields
     * @param int  $limit
     * @param null $fromDate
     * @param null $toDate
     *
     * @return array|int
     */
    public function findLeadsToCreate($integration, $leadFields, $limit = 25, $fromDate = null, $toDate = null, $internalEntity = 'lead')
    {
        if ($internalEntity == 'company') {
            $joinTable = 'companies';
        } else {
            $joinTable = 'leads';
        }
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.$joinTable, 'l');

        if (false === $limit) {
            $q->select('count(*) as total');
        } else {
            $q->select('l.id as internal_entity_id,'.$leadFields);
        }
        if ($internalEntity == 'company') {
            $q->where('not exists (select null from '.MAUTIC_TABLE_PREFIX
                .'integration_entity i where i.integration = :integration and i.internal_entity LIKE "'.$internalEntity.'%" and i.internal_entity_id = l.id)')
                ->setParameter('integration', $integration);
        } else {
            $q->where('l.date_identified is not null')
                ->andWhere(
                    'not exists (select null from '.MAUTIC_TABLE_PREFIX
                    .'integration_entity i where i.integration = :integration and i.internal_entity LIKE "'.$internalEntity.'%" and i.internal_entity_id = l.id)'
                )
                ->setParameter('integration', $integration);
        }

        if ($internalEntity == 'company') {
            $q->andWhere('l.companyname is not null');
        } else {
            $q->andWhere('l.email is not null');
        }
        if ($limit) {
            $q->setMaxResults($limit);
        }

        if ($fromDate) {
            if ($toDate) {
                $q->andWhere(
                    $q->expr()->orX(
                        $q->expr()->andX(
                            $q->expr()->isNotNull('l.date_modified'),
                            $q->expr()->comparison('l.date_modified', 'BETWEEN', ':dateFrom and :dateTo')
                        ),
                        $q->expr()->andX(
                            $q->expr()->isNull('l.date_modified'),
                            $q->expr()->comparison('l.date_added', 'BETWEEN', ':dateFrom and :dateTo')
                        )
                    )
                )
                    ->setParameter('dateFrom', $fromDate)
                    ->setParameter('dateTo', $toDate);
            } else {
                $q->andWhere(
                    $q->expr()->orX(
                        $q->expr()->andX(
                            $q->expr()->isNotNull('l.date_modified'),
                            $q->expr()->gte('l.date_modified', ':dateFrom')
                        ),
                        $q->expr()->andX(
                            $q->expr()->isNull('l.date_modified'),
                            $q->expr()->gte('l.date_added', ':dateFrom')
                        )
                    )
                )
                    ->setParameter('dateFrom', $fromDate);
            }
        } elseif ($toDate) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->andX(
                        $q->expr()->isNotNull('l.date_modified'),
                        $q->expr()->lte('l.date_modified', ':dateTo')
                    ),
                    $q->expr()->andX(
                        $q->expr()->isNull('l.date_modified'),
                        $q->expr()->lte('l.date_added', ':dateTo')
                    )
                )
            )
                ->setParameter('dateTo', $toDate);
        }

        $results = $q->execute()->fetchAll();
        if (false === $limit) {
            return (int) $results[0]['total'];
        }

        $leads = [];
        foreach ($results as $result) {
            $leads[$result['internal_entity_id']] = $result;
        }

        return $leads;
    }

    /**
     * @param $leadId
     * @param $integration
     * @param $integrationEntity
     * @param $internalEntity
     *
     * @return int
     */
    public function getIntegrationEntityCount($leadId, $integration = null, $integrationEntity = null, $internalEntity = null)
    {
        return $this->getIntegrationEntityByLead($leadId, $integration, $integrationEntity, $internalEntity, false);
    }

    /**
     * @param $leadId
     * @param $integration
     * @param $integrationEntity
     * @param $internalEntity
     * @param int|bool $limit
     *
     * @return array|int
     */
    public function getIntegrationEntityByLead($leadId, $integration = null, $integrationEntity = null, $internalEntity = null, $limit = 100)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        if (false === $limit) {
            $q->select('count(*) as total');
        } else {
            $q->select('i.integration, i.integration_entity, i.integration_entity_id, i.date_added, i.last_sync_date, i.internal');
        }

        $q->where('i.internal not like \'%error%\' and i.integration_entity_id is not null');
        $q->orderBy('i.last_sync_date', 'DESC');

        if (empty($integration)) {
            // get list of published integrations
            $pq = $this->_em->getConnection()->createQueryBuilder()
                ->select('p.name')
                ->from(MAUTIC_TABLE_PREFIX.'plugin_integration_settings', 'p')
                ->where('p.is_published = 1');
            $rows    = $pq->execute()->fetchAll();
            $plugins = array_map(function ($i) {
                return "'${i['name']}'";
            }, $rows);
            if (count($plugins) > 0) {
                $q->andWhere($q->expr()->in('i.integration', $plugins));
            } else {
                return [];
            }
        } else {
            $q->andWhere($q->expr()->eq('i.integration', ':integration'));
            $q->setParameter('integration', $integration);
        }

        $q->andWhere(
            $q->expr()->andX(
                "i.internal_entity='lead'",
                $q->expr()->eq('i.internal_entity_id', ':internalEntityId')
            )
        );

        $q->setParameter('internalEntityId', $leadId);

        if (!empty($internalEntity)) {
            $q->andWhere($q->expr()->eq('i.internalEntity', ':internalEntity'));
            $q->setParameter('internalEntity', $internalEntity);
        }

        if (!empty($integrationEntity)) {
            $q->andWhere($q->expr()->eq('i.integrationEntity', ':integrationEntity'));
            $q->setParameter('integrationEntity', $integrationEntity);
        }

        $results = $q->execute()->fetchAll();

        if (false === $limit && count($results) > 0) {
            return (int) $results[0]['total'];
        }

        return $results;
    }

    /**
     * @param array $integrationIds
     * @param       $integration
     * @param       $internalEntityType
     */
    public function markAsDeleted(array $integrationIds, $integration, $internalEntityType)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->set('internal_entity', ':entity')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('integration', ':integration'),
                    $q->expr()->in('integration_entity_id', array_map([$q->expr(), 'literal'], $integrationIds))
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('entity', $internalEntityType.'-deleted')
            ->execute();
    }

    /**
     * @param $integration
     * @param $internalEntity
     * @param $leadId
     *
     * @return array
     */
    public function findLeadsToDelete($internalEntity, $leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity');

        $q->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->like('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->execute();
    }

    /**
     * @param $internalEntity
     * @param $leadId
     */
    public function updateErrorLeads($internalEntity, $leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->set('internal_entity', ':lead')->setParameter('lead', 'lead');

        $q->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->isNotNull('integration_entity_id'))
            ->andWhere($q->expr()->eq('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->execute();

        $z = $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity');

        $z->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->isNull('integration_entity_id'))
            ->andWhere($q->expr()->like('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->execute();
    }
}

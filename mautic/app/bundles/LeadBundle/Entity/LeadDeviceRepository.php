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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadDeviceRepository.
 */
class LeadDeviceRepository extends CommonRepository
{
    /**
     * {@inhertidoc}.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder($this->getTableAlias())
            ->select($this->getTableAlias());
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'd';
    }

    /**
     * @param      $lead
     * @param null $deviceNames
     * @param null $deviceBrands
     * @param null $deviceModels
     * @param null $deviceId
     *
     * @return array
     */
    public function getDevice($lead, $deviceNames = null, $deviceBrands = null, $deviceModels = null, $deviceOss = null, $deviceId = null)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('es.id as id, es.device as device, es.device_fingerprint')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es');
        if (!empty($statIds)) {
            $inIds = (!is_array($statIds)) ? [(int) $statIds] : $statIds;

            $sq->andWhere(
                $sq->expr()->in('es.id', $inIds)
            );
        }

        if ($deviceNames !== null) {
            if (!is_array($deviceNames)) {
                $deviceNames = [$deviceNames];
            }
            foreach ($deviceNames as $key => $deviceName) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device', ':device'.$key)
                )
                    ->setParameter('device'.$key, $deviceName);
            }
        }

        if ($deviceBrands !== null) {
            if (!is_array($deviceBrands)) {
                $deviceBrands = [$deviceBrands];
            }
            foreach ($deviceBrands as $key => $deviceBrand) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_brand', ':deviceBrand'.$key)
                )
                    ->setParameter('deviceBrand'.$key, $deviceBrand);
            }
        }

        if ($deviceModels !== null) {
            if (!is_array($deviceModels)) {
                $deviceModels = [$deviceModels];
            }
            foreach ($deviceModels as $key => $deviceModel) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_model', ':deviceModel'.$key)
                )
                    ->setParameter('deviceModel'.$key, $deviceModel);
            }
        }

        if ($deviceOss !== null) {
            if (!is_array($deviceOss)) {
                $deviceOss = [$deviceOss];
            }
            foreach ($deviceOss as $key => $deviceOs) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_os_name', ':deviceOs'.$key)
                )
                    ->setParameter('deviceOs'.$key, $deviceOs);
            }
        }

        if ($deviceId !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.id', $deviceId)
            );
        } elseif ($lead !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.lead_id', $lead->getId())
            );
        }

        //get totals
        $device = $sq->execute()->fetchAll();

        return (!empty($device)) ? $device[0] : [];
    }

    /**
     * @param string $fingerprint
     *
     * @return LeadDevice
     */
    public function getDeviceByFingerprint($fingerprint)
    {
        if (!$fingerprint) {
            return null;
        }

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('es.id as id, es.lead_id as lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es');

        $sq->where(
            $sq->expr()->eq('es.device_fingerprint', ':fingerprint')
        )
            ->setParameter('fingerprint', $fingerprint);

        //get the first match
        $device = $sq->execute()->fetch();

        return $device ? $device : null;
    }

    /**
     * @param string $trackingId
     *
     * @return LeadDevice|null
     */
    public function getByTrackingId($trackingId)
    {
        /** @var LeadDevice $leadDevice */
        $leadDevice = $this->findOneBy([
            'trackingId' => $trackingId,
        ]);

        return $leadDevice;
    }

    /**
     * Check if there is at least one device with filled tracking code assigned to Lead.
     *
     * @param Lead $lead
     *
     * @return bool
     */
    public function isAnyLeadDeviceTracked(Lead $lead)
    {
        $alias = $this->getTableAlias();
        $qb    = $this->createQueryBuilder($alias);
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq($alias.'.lead', ':lead'),
                $qb->expr()->isNotNull($alias.'.trackingId')
            )
        )
            ->setParameter('lead', $lead);

        $devices = $qb->getQuery()->getResult();

        return !empty($devices);
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getLeadDevices(Lead $lead)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        return $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es')
            ->where('lead_id = :leadId')
            ->setParameter('leadId', (int) $lead->getId())
            ->orderBy('date_added', 'desc')
            ->execute()
            ->fetchAll();
    }
}

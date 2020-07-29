<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Stat;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CacheStorageHelper;

class SegmentCampaignShare
{
    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var CacheStorageHelper
     */
    private $cacheStorageHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * SegmentCampaignShare constructor.
     *
     * @param CampaignModel      $campaignModel
     * @param CacheStorageHelper $cacheStorageHelper
     * @param EntityManager      $entityManager
     */
    public function __construct(CampaignModel $campaignModel, CacheStorageHelper $cacheStorageHelper, EntityManager $entityManager)
    {
        $this->campaignModel      = $campaignModel;
        $this->cacheStorageHelper = $cacheStorageHelper;
        $this->entityManager      = $entityManager;
    }

    /**
     * @param int   $segmentId
     * @param array $campaignIds
     *
     * @return array
     */
    public function getCampaignsSegmentShare($segmentId, $campaignIds = [])
    {
        $campaigns = $this->campaignModel->getRepository()->getCampaignsSegmentShare($segmentId, $campaignIds);
        foreach ($campaigns as $campaign) {
            $this->cacheStorageHelper->set($this->getCachedKey($segmentId, $campaign['id']), $campaign['segmentCampaignShare']);
        }

        return $campaigns;
    }

    /**
     * @param int $segmentId
     *
     * @return array
     */
    public function getCampaignList($segmentId)
    {
        $q = $this->entityManager->getConnection()->createQueryBuilder();
        $q->select('c.id, c.name, null as share')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->where($this->campaignModel->getRepository()->getPublishedByDateExpression($q))
            ->orderBy('c.id', 'DESC');

        $campaigns = $q->execute()->fetchAll();
        foreach ($campaigns as &$campaign) {
            // just load from cache If exists
            if ($share  = $this->cacheStorageHelper->get($this->getCachedKey($segmentId, $campaign['id']))) {
                $campaign['share'] = $share;
            }
        }

        return $campaigns;
    }

    /**
     * @param int $segmentId
     * @param int $campaignId
     *
     * @return string
     */
    private function getCachedKey($segmentId, $campaignId)
    {
        return sprintf('%s|%s|%s|%s|%s', 'campaign', $campaignId, 'segment', $segmentId, 'share');
    }
}

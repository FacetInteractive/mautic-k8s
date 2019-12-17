<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * MessageQueueRepository.
 */
class MessageQueueRepository extends CommonRepository
{
    /**
     * @param $channel
     * @param $channelId
     * @param $leadId
     */
    public function findMessage($channel, $channelId, $leadId)
    {
        $results = $this->createQueryBuilder('mq')
            ->where('IDENTITY(mq.lead) = :leadId')
            ->andWhere('mq.channel = :channel')
            ->andWhere('mq.channelId = :channelId')
            ->setParameter('leadId', $leadId)
            ->setParameter('channel', $channel)
            ->setParameter('channelId', $channelId)
            ->getQuery()
            ->getResult();

        return ($results) ? $results[0] : null;
    }

    /**
     * @param      $limit
     * @param      $processStarted
     * @param null $channel
     * @param null $channelId
     *
     * @return mixed
     */
    public function getQueuedMessages($limit, $processStarted, $channel = null, $channelId = null)
    {
        $q = $this->createQueryBuilder('mq');

        $q->where($q->expr()->eq('mq.success', ':success'))
            ->andWhere($q->expr()->lt('mq.attempts', 'mq.maxAttempts'))
            ->andWhere('mq.lastAttempt is null or mq.lastAttempt < :processStarted')
            ->andWhere('mq.scheduledDate <= :processStarted')
            ->setParameter('success', false, 'boolean')
            ->setParameter('processStarted', $processStarted)
            ->indexBy('mq', 'mq.id');

        $q->orderBy('mq.priority, mq.scheduledDate', 'ASC');

        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        if ($channel) {
            $q->andWhere($q->expr()->eq('mq.channel', ':channel'))
                ->setParameter('channel', $channel);

            if ($channelId) {
                $q->andWhere($q->expr()->eq('mq.channelId', (int) $channelId));
            }
        }

        $results = $q->getQuery()->getResult();

        return $results;
    }

    /**
     * @param            $channel
     * @param array|null $ids
     *
     * @return bool|string
     */
    public function getQueuedChannelCount($channel, array $ids = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $expr = $q->expr()->andX(
            $q->expr()->eq($this->getTableAlias().'.channel', ':channel'),
            $q->expr()->neq($this->getTableAlias().'.status', ':status')
        );

        if (!empty($ids)) {
            $expr->add(
                $q->expr()->in($this->getTableAlias().'.channel_id', $ids)
            );
        }

        return (int) $q->select('count(*)')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', $this->getTableAlias())
            ->where($expr)
            ->setParameters(
                [
                    'channel' => $channel,
                    'status'  => MessageQueue::STATUS_SENT,
                ]
            )
            ->execute()
            ->fetchColumn();
    }
}

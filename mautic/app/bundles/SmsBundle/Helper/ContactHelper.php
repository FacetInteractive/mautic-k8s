<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\SmsBundle\Exception\NumberNotFoundException;

class ContactHelper
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PhoneNumberHelper
     */
    private $phoneNumberHelper;

    /**
     * ContactHelper constructor.
     *
     * @param LeadRepository    $leadRepository
     * @param Connection        $connection
     * @param PhoneNumberHelper $phoneNumberHelper
     */
    public function __construct(
        LeadRepository $leadRepository,
        Connection $connection,
        PhoneNumberHelper $phoneNumberHelper
    ) {
        $this->leadRepository    = $leadRepository;
        $this->connection        = $connection;
        $this->phoneNumberHelper = $phoneNumberHelper;
    }

    /**
     * @param string $number
     *
     * @return ArrayCollection
     *
     * @throws NumberNotFoundException
     */
    public function findContactsByNumber($number)
    {
        // Who knows what the number was originally formatted as so let's try a few
        $searchForNumbers = $this->phoneNumberHelper->getFormattedNumberList($number);

        $qb = $this->connection->createQueryBuilder();

        $foundContacts = $qb->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $qb->expr()->orX(
                    'l.mobile IN (:numbers)',
                    'l.phone IN (:numbers)'
                )
            )
            ->setParameter('numbers', $searchForNumbers, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll();

        $ids = array_column($foundContacts, 'id');
        if (count($ids) === 0) {
            throw new NumberNotFoundException($number);
        }

        $collection = new ArrayCollection();
        /** @var Lead[] $contacts */
        $contacts = $this->leadRepository->getEntities(['ids' => $ids]);
        foreach ($contacts as $contact) {
            $collection->set($contact->getId(), $contact);
        }

        return $collection;
    }
}

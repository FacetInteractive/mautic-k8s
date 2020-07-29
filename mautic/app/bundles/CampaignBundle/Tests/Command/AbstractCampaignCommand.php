<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class AbstractCampaignCommand extends MauticMysqlTestCase
{
    /**
     * @var array
     */
    protected $defaultClientServer = [];

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var
     */
    protected $prefix;

    /**
     * @var \DateTime
     */
    protected $eventDate;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Everything needs to happen anonymously
        $this->defaultClientServer = $this->clientServer;
        $this->clientServer        = [];

        parent::setUp();

        $this->db     = $this->container->get('doctrine.dbal.default_connection');
        $this->prefix = $this->container->getParameter('mautic.db_table_prefix');

        // Populate contacts
        $this->installDatabaseFixtures([dirname(__DIR__).'/../../LeadBundle/DataFixtures/ORM/LoadLeadData.php']);

        // Campaigns are so complex that we are going to load a SQL file rather than build with entities
        $sql = file_get_contents(__DIR__.'/campaign_schema.sql');

        // Update table prefix
        $sql = str_replace('#__', $this->container->getParameter('mautic.db_table_prefix'), $sql);

        // Schedule event
        date_default_timezone_set('UTC');
        $this->eventDate = new \DateTime();
        $this->eventDate->modify('+15 seconds');
        $sql = str_replace('{SEND_EMAIL_1_TIMESTAMP}', $this->eventDate->format('Y-m-d H:i:s'), $sql);

        $this->eventDate->modify('+15 seconds');
        $sql = str_replace('{CONDITION_TIMESTAMP}', $this->eventDate->format('Y-m-d H:i:s'), $sql);

        // Update the schema
        $tmpFile = $this->container->getParameter('kernel.cache_dir').'/campaign_schema.sql';
        file_put_contents($tmpFile, $sql);
        $this->applySqlFromFile($tmpFile);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->clientServer = $this->defaultClientServer;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function getCampaignEventLogs(array $ids)
    {
        $logs = $this->db->createQueryBuilder()
            ->select('l.email, l.country, event.name, event.event_type, event.type, log.*')
            ->from($this->prefix.'campaign_lead_event_log', 'log')
            ->join('log', $this->prefix.'campaign_events', 'event', 'event.id = log.event_id')
            ->join('log', $this->prefix.'leads', 'l', 'l.id = log.lead_id')
            ->where('log.campaign_id = 1')
            ->andWhere('log.event_id IN ('.implode(',', $ids).')')
            ->execute()
            ->fetchAll();

        $byEvent = [];
        foreach ($ids as $id) {
            $byEvent[$id] = [];
        }

        foreach ($logs as $log) {
            $byEvent[$log['event_id']][] = $log;
        }

        return $byEvent;
    }
}

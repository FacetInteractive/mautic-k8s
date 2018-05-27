<?php

namespace Mautic\CoreBundle\Test;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
    /**
     * @var string
     */
    private $sqlDumpFile = false;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $this->sqlDumpFile = $this->container->getParameter('kernel.cache_dir').'/fresh_db.sql';

        $this->prepareDatabase();
    }

    /**
     * @param $file
     *
     * @throws \Exception
     */
    protected function applySqlFromFile($file)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $password   = ($connection->getPassword()) ? " -p{$connection->getPassword()}" : '';
        $command    = "mysql -h{$connection->getHost()} -P{$connection->getPort()} -u{$connection->getUsername()}$password {$connection->getDatabase()} < {$file} 2>&1 | grep -v \"Using a password\" || true";

        $lastLine = system($command, $status);

        if (0 !== $status) {
            throw new \Exception($command.' failed with status code '.$status.' and last line of "'.$lastLine.'"');
        }
    }

    /**
     * Reset each test using a SQL file if possible to prevent from having to run the fixtures over and over.
     *
     * @throws \Exception
     */
    private function prepareDatabase()
    {
        if (!function_exists('system')) {
            $this->installDatabase();

            return;
        }

        if (!file_exists($this->sqlDumpFile)) {
            $this->installDatabase();
            $this->dumpToFile();

            return;
        }

        $this->applySqlFromFile($this->sqlDumpFile);
    }

    /**
     * @throws \Exception
     */
    private function installDatabase()
    {
        $this->createDatabase();
        $this->applyMigrations();
        $this->installDatabaseFixtures();
    }

    /**
     * @throws \Exception
     */
    private function createDatabase()
    {
        $this->runCommand(
            'doctrine:database:drop',
            [
                '--env'   => 'test',
                '--force' => true,
            ]
        );

        $this->runCommand(
            'doctrine:database:create',
            [
                '--env' => 'test',
            ]
        );

        $this->runCommand(
            'doctrine:schema:create',
            [
                '--env' => 'test',
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private function dumpToFile()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $password   = ($connection->getPassword()) ? " -p{$connection->getPassword()}" : '';
        $command    = "mysqldump --add-drop-table --opt -h{$connection->getHost()} -P{$connection->getPort()} -u{$connection->getUsername()}$password {$connection->getDatabase()} > {$this->sqlDumpFile} 2>&1 | grep -v \"Using a password\" || true";

        $lastLine = system($command, $status);
        if (0 !== $status) {
            throw new \Exception($command.' failed with status code '.$status.' and last line of "'.$lastLine.'"');
        }

        $f         = fopen($this->sqlDumpFile, 'r');
        $firstLine = fgets($f);
        if (strpos($firstLine, 'Using a password') !== false) {
            $file = file($this->sqlDumpFile);
            unset($file[0]);
            file_put_contents($this->sqlDumpFile, $file);
        }
        fclose($f);
    }
}

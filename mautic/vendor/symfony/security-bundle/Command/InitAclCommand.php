<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Command;

use Doctrine\DBAL\Schema\SchemaException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs the tables required by the ACL system.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InitAclCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('security.acl.dbal.connection')) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init:acl')
            ->setDescription('Mounts ACL tables in the database')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command mounts ACL tables in the database.

  <info>php %command.full_name%</info>

The name of the DBAL connection must be configured in your <info>app/config/security.yml</info> configuration file in the <info>security.acl.connection</info> variable.

  <info>security:
      acl:
          connection: default</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $connection = $container->get('security.acl.dbal.connection');
        $schema = $container->get('security.acl.dbal.schema');

        try {
            $schema->addToSchema($connection->getSchemaManager()->createSchema());
        } catch (SchemaException $e) {
            $output->writeln('Aborting: '.$e->getMessage());

            return 1;
        }

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->exec($sql);
        }

        $output->writeln('ACL tables have been initialized successfully.');
    }
}

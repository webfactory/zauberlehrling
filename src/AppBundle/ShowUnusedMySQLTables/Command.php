<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedMySQLTables task.
 */
final class Command extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('show-unused-mysql-tables')
             ->setDescription('Show a list of potentially unused MySQL tables.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $task = new Task(
            $container->get('doctrine.dbal.default_connection'),
            $container->getParameter('db_system_catalog_prefix')
        );
        $task->getUnusedTableNames(new SymfonyStyle($input, $output));
    }
}

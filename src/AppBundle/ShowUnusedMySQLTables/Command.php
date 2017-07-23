<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $output->writeln('Potentially unused MySQL tables (this may take a while):');
        $output->writeln('');

        $task = new Task($this->getContainer()->get('doctrine.dbal.default_connection'));
        foreach ($task->getUnusedTableNames() as $potentiallyUnusedTableName) {
            $output->writeln($potentiallyUnusedTableName);
        }

        $output->writeln('');
    }
}

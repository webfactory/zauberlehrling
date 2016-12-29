<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedMySQLTables task.
 */
final class Command extends BaseCommand
{
    /**
     * @var Task
     */
    private $task;

    /**
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        parent::__construct();
        $this->task = $task;
    }

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

        foreach ($this->task->getUnusedTableNames() as $potentiallyUnusedTableName) {
            $output->writeln($potentiallyUnusedTableName);
        }

        $output->writeln('');
    }
}

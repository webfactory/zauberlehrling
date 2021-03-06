<?php

namespace AppBundle\ConsolidateUsedFiles;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony-Console-Command wrapper for the ConsolidateUsedFiles task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_USED_FILES = 'usedFiles';

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
        $this->setName('consolidate-used-files')
             ->setDescription('Consolidate the list of unused PHP files to improve performance of later commands and readability for human readers.')
             ->addArgument(self::ARGUMENT_USED_FILES, InputArgument::REQUIRED, 'Path to the list of used files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->task->consolidate(
            $input->getArgument(self::ARGUMENT_USED_FILES),
            new SymfonyStyle($input, $output)
        );
    }
}

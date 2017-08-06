<?php

namespace AppBundle\ShowUnusedComposerPackages;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedComposerPackages task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_COMPOSER_JSON = 'composerJson';
    const OPTION_VENDOR_DIRECTORY = 'vendorDir';
    const ARGUMENT_USED_FILES = 'usedFiles';
    const OPTION_PATH_TO_BLACKLIST = 'pathToBlacklist';

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
        $this->setName('show-unused-composer-packages')
             ->setDescription('Show a list of potentially unused composer packages.')
             ->addArgument(self::ARGUMENT_COMPOSER_JSON, InputArgument::REQUIRED, 'Path to the project\'s composer.json.')
             ->addOption(self::OPTION_VENDOR_DIRECTORY, 'l', InputOption::VALUE_REQUIRED, 'Path to the project\'s vendor directory.', null)
             ->addArgument(self::ARGUMENT_USED_FILES, InputArgument::REQUIRED, 'Path to the list of used files.')
             ->addOption(self::OPTION_PATH_TO_BLACKLIST, 'b', InputOption::VALUE_REQUIRED, 'Path to a file containing a blacklist of regular expressions to exclude from the output. One regular expression per line, don\'t forget the delimiters. E.g.: ' . PHP_EOL . '#^/project/keepme.php#' . PHP_EOL . '#^/project/tmp/#' . PHP_EOL . '#.*Test.php#');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->task->getUnusedPackagePaths(
            $input->getArgument(self::ARGUMENT_COMPOSER_JSON),
            $input->getOption(self::OPTION_VENDOR_DIRECTORY),
            $input->getArgument(self::ARGUMENT_USED_FILES),
            $input->getOption(self::OPTION_PATH_TO_BLACKLIST),
            new SymfonyStyle($input, $output)
        );
    }
}

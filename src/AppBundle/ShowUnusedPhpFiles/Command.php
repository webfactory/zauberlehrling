<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedPhpFiles task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_USED_FILES = 'usedFiles';
    const OPTION_PATH_TO_INSPECT = 'pathToInspect';
    const OPTION_PATH_TO_OUTPUT = 'pathToOutput';
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
        $this->setName('show-unused-php-files')
             ->setDescription('Show a list of potentially unused PHP files.')
             ->addArgument(self::ARGUMENT_USED_FILES, InputArgument::REQUIRED, 'Path to the list of used files.')
             ->addOption(self::OPTION_PATH_TO_INSPECT, 'p', InputOption::VALUE_REQUIRED, 'Path to search for PHP files. If not set, it will be determined as the common parent path of the used files.')
             ->addOption(self::OPTION_PATH_TO_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Path to the output file. If not set, it will be "potentially-unused-files.txt" next to the file named in the usedFiles argument.')
             ->addOption(self::OPTION_PATH_TO_BLACKLIST, 'b', InputOption::VALUE_REQUIRED, 'Path to a file containing a blacklist of regular expressions to exclude from the output. One regular expression per line, don\'t forget the delimiters. E.g.: ' . PHP_EOL . '#^/project/keepme.php#' . PHP_EOL . '#^/project/tmp/#' . PHP_EOL . '#.*Test.php#');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->task->getUnusedPhpFiles(
            $input->getArgument(self::ARGUMENT_USED_FILES),
            $input->getOption(self::OPTION_PATH_TO_INSPECT),
            $input->getOption(self::OPTION_PATH_TO_OUTPUT),
            $input->getOption(self::OPTION_PATH_TO_BLACKLIST),
            new SymfonyStyle($input, $output)
        );
    }
}

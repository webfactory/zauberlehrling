<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedPublicAssets task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_PATH_TO_PUBLIC = 'pathToPublic';
    const ARGUMENT_PATH_TO_LOG_FILE = 'pathToLogFile';
    const OPTION_REG_EXP_TO_FIND_FILE = 'regExpToFindFile';
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
        $this->setName('show-unused-public-assets')
             ->setDescription('Show a list of potentially unused public assets.')
             ->addArgument(self::ARGUMENT_PATH_TO_PUBLIC, InputArgument::REQUIRED, 'Path to the public web root of your project.')
             ->addArgument(self::ARGUMENT_PATH_TO_LOG_FILE, InputArgument::REQUIRED, 'Path to the web server\'s access log file.')
             ->addOption(self::OPTION_REG_EXP_TO_FIND_FILE, 'r', InputOption::VALUE_REQUIRED, 'Regular expression for the log file capturing the path of the accessed file as it\'s first capture group.', '#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i')
             ->addOption(self::OPTION_PATH_TO_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Path to the output file. If not set, it will be "potentially-unused-public-assets.txt" in the folder above the public web root.')
             ->addOption(self::OPTION_PATH_TO_BLACKLIST, 'b', InputOption::VALUE_REQUIRED, 'Path to a file containing a blacklist of regular expressions to exclude from the output. One regular expression per line, don\'t forget the delimiters. E.g.: ' . PHP_EOL . '#^/project/keepme.php#' . PHP_EOL . '#^/project/tmp/#' . PHP_EOL . '#.*Test.php#');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->task->getUnusedPublicAssets(
            $input->getArgument(self::ARGUMENT_PATH_TO_PUBLIC),
            $input->getArgument(self::ARGUMENT_PATH_TO_LOG_FILE),
            $input->getOption(self::OPTION_REG_EXP_TO_FIND_FILE),
            $input->getOption(self::OPTION_PATH_TO_OUTPUT),
            $input->getOption(self::OPTION_PATH_TO_BLACKLIST),
            new SymfonyStyle($input, $output)
        );
    }
}

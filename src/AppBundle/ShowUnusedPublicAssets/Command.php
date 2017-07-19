<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
             ->addOption(self::OPTION_PATH_TO_BLACKLIST, 'b', InputOption::VALUE_REQUIRED, 'Path to a file containing a blacklist of regular expressions to exclude from the output.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pathToOutput = $this->getPathToOutput($input);

        $output->writeln('Writing list of potentially unused public assets to ' . $pathToOutput);

        $this->writeUnusedPublicAssetsToOutputFile($input, $pathToOutput);

        $output->writeln('Finished.');
        $output->writeln('');
        $output->writeln('Now you may want to inspect the output file and transfer the lines with assets you want to keep (although they don\'t seem to be accessed) to a blacklist file for further runs.');
        $output->writeln('Finally, you may want to do delete the remaining listed files, e.g. with:');
        $output->writeln('');
        $output->writeln('xargs rm < ' . $pathToOutput);
        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @param string $pathToOutput
     * @throws \InvalidArgumentException
     */
    private function writeUnusedPublicAssetsToOutputFile(InputInterface $input, $pathToOutput)
    {
        $handle = fopen($pathToOutput, 'wb');
        if ($handle === false) {
            throw new \InvalidArgumentException($pathToOutput . ' is not writeable');
        }

        $pathToPublic = $input->getArgument(self::ARGUMENT_PATH_TO_PUBLIC);
        $logEntries = file($input->getArgument(self::ARGUMENT_PATH_TO_LOG_FILE), FILE_IGNORE_NEW_LINES);
        $regExpToFindFile = $input->getOption(self::OPTION_REG_EXP_TO_FIND_FILE);
        $blacklistRegExps = $this->getBlacklistedRegExps($input);
        $unusedAssets = $this->task->getUnusedPublicAssets($pathToPublic, $logEntries, $regExpToFindFile, $blacklistRegExps);

        fwrite($handle, implode(PHP_EOL, $unusedAssets));
        fclose($handle);
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function getPathToOutput(InputInterface $input)
    {
        $pathToOutput = $input->getOption(self::OPTION_PATH_TO_OUTPUT);

        if ($pathToOutput !== null) {
            return realpath(dirname($pathToOutput)) . '/' . basename($pathToOutput);
        }

        return realpath(dirname($input->getArgument(self::ARGUMENT_PATH_TO_PUBLIC))) . '/potentially-unused-public-assets.txt';
    }

    /**
     * @param InputInterface $input
     * @return string[]
     */
    private function getBlacklistedRegExps(InputInterface $input)
    {
        $pathToBlacklist = $input->getOption(self::OPTION_PATH_TO_BLACKLIST);
        if ($pathToBlacklist === null) {
            return [];
        }

        return file($pathToBlacklist, FILE_IGNORE_NEW_LINES);
    }
}

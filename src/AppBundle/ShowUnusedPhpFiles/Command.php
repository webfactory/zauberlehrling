<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedPhpFiles task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_PATH_TO_IGNORE = 'pathToIgnore';
    const ARGUMENT_USED_FILES = 'usedFiles';
    const OPTION_PATH_TO_INSPECT = 'pathToInspect';
    const OPTION_PATH_TO_OUTPUT = 'pathToOutput';

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
             ->addArgument(self::ARGUMENT_PATH_TO_IGNORE, InputArgument::REQUIRED, 'Path to ignore, e.g. temp-directories.')
             ->addArgument(self::ARGUMENT_USED_FILES, InputArgument::REQUIRED, 'Path to the list of used files.')
             ->addOption(self::OPTION_PATH_TO_INSPECT, 'p', InputOption::VALUE_REQUIRED, 'Path to search for PHP files. If not set, it will be determined as the common parent path of the used files.', null)
             ->addOption(self::OPTION_PATH_TO_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Path to the output file. If not set, it will be "potentially-unused-files.txt" next to the file named in the usedFiles argument.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pathToOutput = $this->getPathToOutput($input);

        $output->writeln('Writing list of unused PHP files to ' . $pathToOutput);

        $this->writeUnusedPhpFilesToOutputFile($input, $pathToOutput);

        $output->writeln('Finished.');
        $output->writeln('');
        $output->writeln('Now you may want to inspect the output file and delete the lines with files you want to keep although they don\'t seem to be used.');
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
    private function writeUnusedPhpFilesToOutputFile(InputInterface $input, $pathToOutput)
    {
        $handle = fopen($pathToOutput, 'wb');
        if ($handle === false) {
            throw new \InvalidArgumentException($pathToOutput . ' is not writeable');
        }

        $unusedPhpFiles = $this->task->getUnusedPhpFiles(
            $input->getArgument(self::ARGUMENT_PATH_TO_IGNORE),
            file($input->getArgument(self::ARGUMENT_USED_FILES), FILE_IGNORE_NEW_LINES),
            $input->getOption(self::OPTION_PATH_TO_INSPECT)
        );
        fwrite($handle, implode(PHP_EOL, $unusedPhpFiles));

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

        return realpath(dirname($input->getArgument(self::ARGUMENT_USED_FILES))) . '/potentially-unused-files.txt';
    }
}

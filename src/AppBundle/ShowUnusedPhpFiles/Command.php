<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony-Console-Command wrapper for the ShowUnusedPhpFiles task.
 */
final class Command extends BaseCommand
{
    const ARGUMENT_PATH_TO_INSPECT = 'pathToInspect';
    const ARGUMENT_PATH_TO_IGNORE = 'pathToIgnore';
    const ARGUMENT_USED_FILES = 'usedFiles';
    const ARGUMENT_PATH_TO_OUTPUT = 'pathToOutput';

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
             ->addArgument(self::ARGUMENT_PATH_TO_INSPECT, InputArgument::REQUIRED, 'Path to search for PHP files.')
             ->addArgument(self::ARGUMENT_PATH_TO_IGNORE, InputArgument::REQUIRED, 'Path to ignore, e.g. temp-directories.')
             ->addArgument(self::ARGUMENT_USED_FILES, InputArgument::REQUIRED, 'Path to the list of used files.')
             ->addArgument(self::ARGUMENT_PATH_TO_OUTPUT, InputArgument::REQUIRED, 'Path to the output file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $originalPathToOutput = $input->getArgument(self::ARGUMENT_PATH_TO_OUTPUT);
        $pathToOutput = realpath(dirname($originalPathToOutput)) . '/' . basename($originalPathToOutput);

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
     * @return resource
     */
    private function writeUnusedPhpFilesToOutputFile(InputInterface $input, $pathToOutput)
    {
        $handle = fopen($pathToOutput, 'wb');
        if ($handle === false) {
            throw new \InvalidArgumentException($pathToOutput . ' is not writeable');
        }

        $unusedPhpFiles = $this->task->getUnusedPhpFiles(
            $input->getArgument(self::ARGUMENT_PATH_TO_INSPECT),
            $input->getArgument(self::ARGUMENT_PATH_TO_IGNORE),
            file($input->getArgument(self::ARGUMENT_USED_FILES), FILE_IGNORE_NEW_LINES)
        );
        fwrite($handle, implode(PHP_EOL, $unusedPhpFiles));

        fclose($handle);
    }
}

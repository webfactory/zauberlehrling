<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Helper\FileSystem;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests für die ShowUnusedPhpFiles console command.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $pathToUsedFiles;

    /** @var string */
    private $pathToOutput;

    /** @var string */
    private $pathToInspect;

    /** @var string */
    private $pathToBlacklist;

    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command(new Task()));
        $this->command = $application->find('show-unused-php-files');
        $this->commandTester = new CommandTester($this->command);

        $this->pathToInspect = __DIR__ . '/fixtures';
        $this->pathToUsedFiles = __DIR__ . '/fixtures/used-files.txt';
        $this->pathToOutput = __DIR__ . '/fixtures/potentially-unused-files.txt';
        $this->pathToBlacklist = __DIR__ . '/fixtures/blacklist.txt';

        FileSystem::writeArrayToFile([__DIR__ . '/fixtures/used/file.php'], $this->pathToUsedFiles);
    }

    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        FileSystem::writeArrayToFile([], $this->pathToUsedFiles);
        FileSystem::writeArrayToFile([], $this->pathToOutput);
    }

    /**
     * @test
     */
    public function successOutput()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $this->pathToUsedFiles,
            '--pathToInspect' => $this->pathToInspect,
            '--pathToOutput' => $this->pathToOutput,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[OK]', $output);
    }

    /**
     * @test
     */
    public function unusedFilesGetWritten()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $this->pathToUsedFiles,
            '--pathToInspect' => $this->pathToInspect,
            '--pathToOutput' => $this->pathToOutput,
        ]);

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertEquals([__DIR__ . '/fixtures/file.php', __DIR__ . '/fixtures/ignored/file.php'], $result);
    }
}

<?php

namespace AppBundle\ShowUnusedComposerPackages;

use Helper\FileSystem;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the ShowUnusedComposerPackages console command.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $pathToComposerJson;

    /** @var string */
    private $pathToUsedFiles;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command(new Task()));
        $this->command = $application->find('show-unused-composer-packages');
        $this->commandTester = new CommandTester($this->command);

        $this->pathToComposerJson = __DIR__ . '/fixtures/composer.json';
        $this->pathToUsedFiles = __DIR__ . '/fixtures/used-files.txt';

        FileSystem::writeArrayToFile([__DIR__ . '/fixtures/vendor/author-1/used-package/file.txt'], $this->pathToUsedFiles);
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        FileSystem::writeArrayToFile([], $this->pathToUsedFiles);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function successOutput()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'composerJson' => $this->pathToComposerJson,
            'usedFiles' => $this->pathToUsedFiles,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[OK]', $output);
    }

    /**
     * @test
     */
    public function commandWorks()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'composerJson' => $this->pathToComposerJson,
            'usedFiles' => $this->pathToUsedFiles,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains(__DIR__ . '/fixtures/vendor/author-1/unused-package', $output);
    }
}

<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Helper\FileSystem;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the ShowUnusedPublicAssets console command.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $pathToPublic;

    /** @var string */
    private $pathToLog;

    /** @var string */
    private $pathToOutput;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command(new Task()));
        $this->command = $application->find('show-unused-public-assets');
        $this->commandTester = new CommandTester($this->command);

        $this->pathToPublic = __DIR__ . '/fixtures/';
        $this->pathToLog = __DIR__ . '/log.txt';
        $this->pathToOutput = __DIR__ . '/potentially-unused-assets.txt';
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        unlink($this->pathToOutput);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function successOutput()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'pathToPublic' => $this->pathToPublic,
            'pathToLogFile' => $this->pathToLog,
            '--pathToOutput' => $this->pathToOutput,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[OK]', $output);
    }

    /**
     * @test
     */
    public function fileGetsConsolidated()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'pathToPublic' => $this->pathToPublic,
            'pathToLogFile' => $this->pathToLog,
            '--pathToOutput' => $this->pathToOutput,
        ]);

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertContains(__DIR__ . '/fixtures/asset.css', $result);
    }
}

<?php

namespace AppBundle\ConsolidateUsedFiles;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the ConsolidateUsedFiles console command.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $pathToFixture;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command(new Task()));
        $this->command = $application->find('consolidate-used-files');
        $this->commandTester = new CommandTester($this->command);

        $this->pathToFixture = __DIR__ . '/fixtures/tmp-file-for-testing.txt';
        copy(__DIR__ . '/fixtures/template-to-copy.txt', $this->pathToFixture);
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        unlink($this->pathToFixture);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function successOutput()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $this->pathToFixture,
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
            'usedFiles' => $this->pathToFixture,
        ]);

        $result = file($this->pathToFixture, FILE_IGNORE_NEW_LINES);
        $this->assertEquals(['a', 'b', 'c', 'e', 'g'], $result);
    }

    /**
     * @test
     */
    public function nonExistingFileGivesError()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => 'non-existing-file',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[ERROR]', $output);
        $this->assertContains('file', $output);
    }

    /**
     * @test
     */
    public function nonWritableFileGivesError()
    {
        // make file not readable (it's not checked in that way since git couldn't read it that way).
        $pathToReadableButNotWritableFixture = __DIR__ . '/fixtures/readable-but-not-writable-file.txt';
        $originalRights = fileperms($pathToReadableButNotWritableFixture);
        if (chmod($pathToReadableButNotWritableFixture, 0400) === false) {
            $this->markTestSkipped('Test system does not support chmod\'ing 400.');
        }

        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $pathToReadableButNotWritableFixture,
        ]);

        // restore original rights so git does not recognise a modification
        chmod($pathToReadableButNotWritableFixture, $originalRights);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[ERROR]', $output);
        $this->assertContains('readable', $output);
    }

    /**
     * @test
     */
    public function nonReadableFileGivesError()
    {
        // make file not readable (it's not checked in that way since git couldn't read it that way).
        $pathToWritableButNotReadableFixture = __DIR__ . '/fixtures/writable-but-not-readable-file.txt';
        $originalRights = fileperms($pathToWritableButNotReadableFixture);
        if (chmod($pathToWritableButNotReadableFixture, 0200) === false) {
            $this->markTestSkipped('Test system does not support chmod\'ing 200.');
        }

        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $pathToWritableButNotReadableFixture,
        ]);

        // restore original rights so git does not recognise a modification
        chmod($pathToWritableButNotReadableFixture, $originalRights);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[ERROR]', $output);
        $this->assertContains('writable', $output);
    }
}

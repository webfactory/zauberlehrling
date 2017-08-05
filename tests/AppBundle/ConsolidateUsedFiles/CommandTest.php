<?php

namespace AppBundle\ConsolidateUsedFiles;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the ConsolidateUsedFiles console command class.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $pathToFixture;

    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command(new Task()));
        $this->command = $application->find('consolidate-used-files');

        $this->commandTester = new CommandTester($this->command);
        
        $this->pathToFixture = __DIR__ . '/fixtures/files-to-consolidate.txt';
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
        $this->assertContains('Finished', $output);
    }

    /**
     * @test
     */
    public function fileGetsConsolidated()
    {
        // set up fixture
        file_put_contents($this->pathToFixture, implode(PHP_EOL, ['b', 'a', 'a']));

        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => $this->pathToFixture,
        ]);

        $result = file($this->pathToFixture, FILE_IGNORE_NEW_LINES);
        $this->assertEquals(['a', 'b'], $result);
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
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
            'usedFiles' => __DIR__ . '/fixtures/readable-but-not-writable-file.txt',
        ]);

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

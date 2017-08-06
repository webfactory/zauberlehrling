<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Helper\FileSystem;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tests for the ShowUnusedPhpFiles task.
 */
final class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var Task
     */
    private $task;

    /** @var string */
    private $pathToUsedFiles;

    /** @var string */
    private $pathToOutput;

    /** @var string */
    private $pathToInspect;

    /** @var string */
    private $pathToBlacklist;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->task = new Task();

        $this->pathToInspect = __DIR__ . '/fixtures';
        $this->pathToUsedFiles = __DIR__ . '/fixtures/used-files.txt';
        $this->pathToOutput = __DIR__ . '/fixtures/potentially-unused-files.txt';
        $this->pathToBlacklist = __DIR__ . '/fixtures/blacklist.txt';

        FileSystem::writeArrayToFile([__DIR__ . '/fixtures/used/file.php'], $this->pathToUsedFiles);
        FileSystem::writeArrayToFile(['#' . __DIR__ . '/fixtures/ignored/file.php#'], $this->pathToBlacklist);
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        FileSystem::writeArrayToFile([], $this->pathToUsedFiles);
        FileSystem::writeArrayToFile([], $this->pathToOutput);
        FileSystem::writeArrayToFile([], $this->pathToBlacklist);
    }

    /**
     * @test
     */
    public function emptyUsedFilesGetRejected()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->task->getUnusedPhpFiles(__DIR__ . '/fixtures/empty.txt', null, null, null);
    }

    /**
     * @test
     */
    public function unusedPhpFilesGetReported()
    {
        $this->task->getUnusedPhpFiles($this->pathToUsedFiles, $this->pathToInspect, $this->pathToOutput, null);

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertContains(realpath(__DIR__ . '/fixtures/file.php'), $result);
    }

    /**
     * @test
     */
    public function usedPhpFilesDontGetReported()
    {
        $this->task->getUnusedPhpFiles($this->pathToUsedFiles, $this->pathToInspect, $this->pathToOutput, null);

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertNotContains(realpath(__DIR__ . '/fixtures/used/file.php'), $result);
    }

    /**
     * @test
     */
    public function unusedButBlacklistedPhpFilesDontGetReported()
    {
        $this->task->getUnusedPhpFiles($this->pathToUsedFiles, $this->pathToInspect, $this->pathToOutput, null);
        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertContains(
            realpath(__DIR__ . '/fixtures/ignored/file.php'),
            $result,
            'Precondition not met: ignored file wouldn\'t have been found even if not ignored'
        );

        $this->task->getUnusedPhpFiles($this->pathToUsedFiles, $this->pathToInspect, $this->pathToOutput, $this->pathToBlacklist);

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertNotContains(realpath(__DIR__ . '/fixtures/ignored/file.php'), $result);
    }

    /**
     * @test
     */
    public function pathToInspectCanBeGuessedFromUsedFiles()
    {
        $output = new BufferedOutput();
        $this->task->getUnusedPhpFiles(
            $this->pathToUsedFiles,
            null,
            $this->pathToOutput,
            null,
            new SymfonyStyle(new StringInput(''), $output)
        );

        $this->assertContains(' ' . __DIR__ . '/fixtures/used ', $output->fetch());
    }
}

<?php

namespace AppBundle\ShowUnusedPhpFiles;

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

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->task = new Task();
    }

    /**
     * @test
     */
    public function unusedPhpFilesGetReported()
    {
        $unusedFiles = $this->task->getUnusedPhpFiles(
            '/tmp',
            [__DIR__ . '/fixtures/used/file.php'],
            __DIR__ . '/fixtures'
        );

        $this->assertContains(realpath(__DIR__ . '/fixtures/file.php'), $unusedFiles);
    }

    /**
     * @test
     */
    public function usedPhpFilesDontGetReported()
    {
        $unusedFiles = $this->task->getUnusedPhpFiles(
            '/tmp',
            [__DIR__ . '/fixtures/file.php'],
            __DIR__ . '/fixtures'
        );

        $this->assertNotContains(realpath(__DIR__ . '/fixtures/file.php'), $unusedFiles);
    }

    /**
     * @test
     */
    public function unusedPhpFilesInIgnoredPathDontGetReported()
    {
        $unusedFiles = $this->task->getUnusedPhpFiles(
            __DIR__ . '/fixtures/ignored',
            [__DIR__ . '/fixtures/used/file.php'],
            __DIR__ . '/fixtures/ignored'
        );

        $this->assertEmpty($unusedFiles);
    }

    /**
     * @test
     */
    public function pathToInspectCanBeGuessedFromUsedFiles()
    {
        $unusedFiles = $this->task->getUnusedPhpFiles(
            __DIR__ . '/fixtures/ignored',
            [__DIR__ . '/fixtures/used/file.php', __DIR__ . '/fixtures/virtual-used-file.php'],
            null
        );

        $this->assertCount(1, $unusedFiles);
        $this->assertContains(realpath(__DIR__ . '/fixtures/file.php'), $unusedFiles);
    }
}

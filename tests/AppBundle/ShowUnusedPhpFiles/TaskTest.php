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
        $unusedFiles = $this->task->getUnusedPhpFiles(__DIR__ . '/fixtures', '/tmp', []);

        $this->assertContains(realpath(__DIR__ . '/fixtures/file.php'), $unusedFiles);
    }

    /**
     * @test
     */
    public function usedPhpFilesDontGetReported()
    {
        $unusedFiles = $this->task->getUnusedPhpFiles(
            __DIR__ . '/fixtures',
            '/tmp',
            [__DIR__ . '/fixtures/file.php']
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
            __DIR__ . '/fixtures/ignored',
            []
        );

        $this->assertEmpty($unusedFiles);
    }
}

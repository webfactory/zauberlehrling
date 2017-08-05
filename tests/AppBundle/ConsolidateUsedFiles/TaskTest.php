<?php

namespace AppBundle\ConsolidateUsedFiles;

use Helper\FileSystemTest;

/**
 * Tests for the ConsolidateUsedFiles task.
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
     * @var string
     */
    private $fileForTesting;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->fileForTesting = __DIR__ . '/fixtures/tmp-file-for-testing.txt';
        copy(__DIR__ . '/fixtures/template-to-copy.txt', $this->fileForTesting);

        $this->task = new Task();
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        unlink($this->fileForTesting);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function consolidateRemovesDuplicates()
    {
        $this->task->consolidate($this->fileForTesting);

        $this->assertCount(5, file($this->fileForTesting, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES));
    }

    /**
     * @test
     */
    public function consolidateSorts()
    {
        $this->task->consolidate($this->fileForTesting);

        $this->assertEquals(
            ['a', 'b', 'c', 'e', 'g'],
            array_values(file($this->fileForTesting, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES))
        );
    }

    /**
     * @test
     */
    public function nonExistingFileGetsRejected()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->task->consolidate(__DIR__ . '/non-existing-file');
    }

    /**
     * @test
     */
    public function directoryGetsRejected()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->task->consolidate(__DIR__);
    }

    /**
     * @test
     */
    public function unreadableFileGetsRejected()
    {
        $pathToFile = __DIR__ . '/../../Helper/fixtures/unreadable-yet-writable-file.txt';
        FileSystemTest::ensurePermissionsFor(0200, $pathToFile);

        $this->setExpectedException(\InvalidArgumentException::class);
        try {
            $this->task->consolidate($pathToFile);
        } finally {
            FileSystemTest::restoreOriginalPermissionsFor($pathToFile);
        }
    }

    /**
     * @test
     */
    public function unwritableFileGetsRejected()
    {
        $pathToFile = __DIR__ . '/../../Helper/fixtures/unwritable-yet-readable-file.txt';
        FileSystemTest::ensurePermissionsFor(0400, $pathToFile);

        $this->setExpectedException(\InvalidArgumentException::class);
        try {
            $this->task->consolidate($pathToFile);
        } finally {
            FileSystemTest::restoreOriginalPermissionsFor($pathToFile);
        }
    }
}

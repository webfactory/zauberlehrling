<?php

namespace AppBundle\ConsolidateUsedFiles;

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
}

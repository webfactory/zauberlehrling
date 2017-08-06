<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Helper\FileSystem;

/**
 * Tests for the ShowUnusedPublicAssets task.
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
    private $pathToPublic;

    /** @var string */
    private $pathToLogfile;

    /** @var string */
    private $regExpToFindFile;

    /** @var string */
    private $pathToOutput;

    /** @var string */
    private $pathToBlacklist;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->task = new Task();

        $this->pathToPublic = __DIR__ . '/fixtures/';
        $this->pathToLogfile = __DIR__ .'/log.txt';
        $this->regExpToFindFile = '#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i';
        $this->pathToBlacklist = __DIR__ . '/blacklist.txt';
        $this->pathToOutput = __DIR__ . '/potentially-unused-assets.txt';

        FileSystem::writeArrayToFile(['#' . __DIR__ . '/fixtures/ignored/asset.css#'], $this->pathToBlacklist);
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        FileSystem::writeArrayToFile([], $this->pathToBlacklist);
        unlink($this->pathToOutput);
    }

    /**
     * @test
     */
    public function unusedAssetsGetReported()
    {
        $this->task->getUnusedPublicAssets(
            $this->pathToPublic,
            $this->pathToLogfile,
            $this->regExpToFindFile,
            $this->pathToOutput,
            $this->pathToBlacklist
        );

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertInternalType('array', $result);
        $this->assertContains(realpath(__DIR__ . '/fixtures/asset.css'), $result);
    }

    /**
     * @test
     */
    public function usedAssetsDontGetReported()
    {
        $this->task->getUnusedPublicAssets(
            $this->pathToPublic,
            $this->pathToLogfile,
            $this->regExpToFindFile,
            $this->pathToOutput,
            $this->pathToBlacklist
        );

        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertInternalType('array', $result);
        $this->assertNotContains(realpath(__DIR__ . '/fixtures/used/asset.css'), $result);
    }

    /**
     * @test
     */
    public function unusedAssetsInIgnoredPathDontGetReported()
    {
        $this->task->getUnusedPublicAssets(
            $this->pathToPublic,
            $this->pathToLogfile,
            $this->regExpToFindFile,
            $this->pathToOutput,
            null
        );
        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertInternalType('array', $result);
        $this->assertContains(
            realpath(__DIR__ . '/fixtures/ignored/asset.css'),
            $result,
            'Precondition not met: ignored file wouldn\'t have been found even if not ignored'
        );

        $this->task->getUnusedPublicAssets(
            $this->pathToPublic,
            $this->pathToLogfile,
            $this->regExpToFindFile,
            $this->pathToOutput,
            $this->pathToBlacklist
        );
        $result = FileSystem::readFileIntoArray($this->pathToOutput);
        $this->assertInternalType('array', $result);
        $this->assertNotContains(realpath(__DIR__ . '/fixtures/ignored/asset.css'), $result);
    }
}

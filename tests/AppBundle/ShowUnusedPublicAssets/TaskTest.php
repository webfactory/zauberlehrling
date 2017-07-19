<?php

namespace AppBundle\ShowUnusedPublicAssets;

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
    public function unusedAssetsGetReported()
    {
        $unusedAssets = $this->task->getUnusedPublicAssets(
            __DIR__ . '/fixtures',
            [
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /used/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /ignored/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
            ],
            '#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i',
            []
        );

        $this->assertContains(realpath(__DIR__ . '/fixtures/asset.css'), $unusedAssets);
    }

    /**
     * @test
     */
    public function usedAssetsDontGetReported()
    {
        $unusedAssets = $this->task->getUnusedPublicAssets(
            __DIR__ . '/fixtures',
            [
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /used/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /ignored/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
            ],
            '#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i',
            []
        );

        $this->assertNotContains(realpath(__DIR__ . '/fixtures/used/asset.css'), $unusedAssets);
    }

    /**
     * @test
     */
    public function unusedAssetsInIgnoredPathDontGetReported()
    {
        $unusedAssets = $this->task->getUnusedPublicAssets(
            __DIR__ . '/fixtures/ignored',
            [
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /used/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
                '10.20.30.1 localhost - [01/Jan/2000:00:00:00 +0200] "GET /ignored/asset.css HTTP/1.1" 200 115323 "http://localhost/referrer/" "User Agent" WWYs0n8AAQEAAFi5VK0AAAAA proc_time=2370248 port=80 https=-',
            ],
            '#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i',
            ['#/tmp/.*#i', '#' . __DIR__ . '/fixtures/ignored/.*#']
        );

        $this->assertNotContains(realpath(__DIR__ . '/fixtures/ignored/asset.css'), $unusedAssets);
    }
}

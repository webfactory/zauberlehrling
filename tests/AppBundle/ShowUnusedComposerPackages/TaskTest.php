<?php

namespace AppBundle\ShowUnusedComposerPackages;

/**
 * Tests for the ShowUnusedComposerPackages Task.
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
    private $pathToComposerJson;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->pathToComposerJson = __DIR__ . '/fixtures/composer.json';
        $this->task = new Task();
    }

    /**
     * @test
     */
    public function defaultPathToVendorIsGuessed()
    {
        $this->setExpectedException(null);

        $this->task->getUnusedComposerPackages($this->pathToComposerJson, null, [], []);
    }

    /**
     * @test
     */
    public function invalidPathToVendorGetsRejected()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->task->getUnusedComposerPackages($this->pathToComposerJson, 'invalid path', [], []);
    }

    /**
     * @test
     */
    public function potentiallyUnusedPackagesGetReported()
    {
        $unusedPackages = $this->task->getUnusedComposerPackages($this->pathToComposerJson, null, [], []);

        $this->assertCount(1, $unusedPackages);
        $this->assertEquals('author-1/package-1', $unusedPackages[0]->getName());
    }

    /**
     * @test
     */
    public function usedPackagesDontGetReported()
    {
        $unusedPackages = $this->task->getUnusedComposerPackages(
            $this->pathToComposerJson,
            null,
            [__DIR__ . '/fixtures/vendor/author-1/package-1/file.txt'],
            []
        );

        $this->assertCount(0, $unusedPackages);
    }

    /**
     * If a bundle is only registered in the app kernel (which marks the Bundle file as being used) and not used
     * otherwhise, it should be reported. Yes, it could provide services, config, assets an such, but these are not
     * cared for systematically, so why start here.
     *
     * @test
     */
    public function packageIsReportedIfOnlyItsSymfonyBundlePhpIsUsed()
    {
        $unusedPackages = $this->task->getUnusedComposerPackages(
            $this->pathToComposerJson,
            null,
            [__DIR__ . '/fixtures/vendor/author-1/package-1/Author1Package1Bundle.php'],
            []
        );

        $this->assertCount(1, $unusedPackages);
        $this->assertEquals('author-1/package-1', $unusedPackages[0]->getName());
    }

    /**
     * @test
     */
    public function packageIsReportedIfItsNotBlacklisted()
    {
        $unusedPackages = $this->task->getUnusedComposerPackages(
            $this->pathToComposerJson,
            null,
            [__DIR__ . '/fixtures/vendor/author-1/package-1/Author1Package1Bundle.php'],
            ['#' . __DIR__ . '/fixtures/vendor/author-1/.*#']
        );

        $this->assertEmpty($unusedPackages);
    }
}

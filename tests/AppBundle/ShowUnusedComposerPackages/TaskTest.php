<?php

namespace AppBundle\ShowUnusedComposerPackages;
use Helper\FileSystem;
use Helper\FileSystemTest;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    /** @var string */
    private $pathToComposerJson;

    /** @var string */
    private $pathToVendor;

    /** @var string */
    private $pathToUsedFiles;

    /** @var string */
    private $pathToBlacklist;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->pathToComposerJson = __DIR__ . '/fixtures/composer.json';
        $this->task = new Task();

        $this->pathToComposerJson = __DIR__ . '/fixtures/composer.json';
        $this->pathToVendor = __DIR__ . '/fixtures/vendor';
        $this->pathToUsedFiles = __DIR__ . '/fixtures/used-files.txt';
        $this->pathToBlacklist = __DIR__ . '/fixtures/blacklist.txt';

        FileSystem::writeArrayToFile(
            [
                __DIR__ . '/fixtures/vendor/author-1/used-package/file.txt',
                __DIR__ . '/fixtures/vendor/author-1/fairly-unused-package/AuthorPackageBundle.php',
            ],
            $this->pathToUsedFiles
        );
        FileSystem::writeArrayToFile(['#' . __DIR__ . '/fixtures/vendor/author-1/ignored-package#'], $this->pathToBlacklist);
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        // revert files so git doesn't recognise a change
        FileSystem::writeArrayToFile([], $this->pathToUsedFiles);
        FileSystem::writeArrayToFile([], $this->pathToBlacklist);
    }

    /**
     * @test
     */
    public function defaultPathToVendorIsGuessed()
    {
        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, null, $ioStyle);

        $this->assertContains(' ' . __DIR__ . '/fixtures/vendor/ ', $output->fetch());
    }

    /**
     * @test
     */
    public function invalidPathToVendorGetsRejected()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->task->getUnusedPackagePaths($this->pathToComposerJson, 'invalid path', $this->pathToUsedFiles, null);
    }

    /**
     * @test
     */
    public function unreadablePathToVendorGetsRejected()
    {
        FileSystemTest::ensurePermissionsFor(0200, $this->pathToVendor);

        try {
            $this->setExpectedException(\InvalidArgumentException::class);
            $this->task->getUnusedPackagePaths($this->pathToComposerJson, $this->pathToVendor, $this->pathToUsedFiles, null);
        } finally {
            FileSystemTest::restoreOriginalPermissionsFor($this->pathToVendor);
        }
    }

    /**
     * @test
     */
    public function potentiallyUnusedPackagesGetReported()
    {
        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, null, $ioStyle);

        $outputAsString = $output->fetch();

        $this->assertContains(__DIR__ . '/fixtures/vendor/author-1/unused-package', $outputAsString);
    }

    /**
     * @test
     */
    public function usedPackagesDontGetReported()
    {
        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, null, $ioStyle);

        $outputAsString = $output->fetch();

        $this->assertNotContains(__DIR__ . '/fixtures/vendor/author-1/used-package', $outputAsString);
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
        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, null, $ioStyle);

        $outputAsString = $output->fetch();

        $this->assertContains(__DIR__ . '/fixtures/vendor/author-1/fairly-unused-package', $outputAsString);
    }

    /**
     * @test
     */
    public function blacklistedPackagesDontGetReported()
    {
        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, null, $ioStyle);

        $outputAsString = $output->fetch();
        $this->assertContains(
            __DIR__ . '/fixtures/vendor/author-1/ignored-package',
            $outputAsString,
            'Precondition not met: ignored package wouldn\'t have been found even if not ignored'
        );

        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedPackagePaths($this->pathToComposerJson, null, $this->pathToUsedFiles, $this->pathToBlacklist, $ioStyle);

        $outputAsString = $output->fetch();
        $this->assertNotContains(__DIR__ . '/fixtures/vendor/author-1/ignored-package', $outputAsString);
    }
}

<?php

namespace AppBundle\ShowUnusedComposerPackages;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\PackageInterface;

/**
 * Get unused composer packages.
 *
 * The idea is that packages are required either directly from the root package or indirectly. We call these packages
 * n-th degree requirements, where n is the number of links between the package in question and the root package.
 * E.g. a 2nd degree requirement is a package that is required by a package that in turn is directly required by the
 * root package.
 *
 * Deleting a requirement of 2nd or higher degree alone makes no sense, as it will still be required by a first degree
 * requirement and therefore be installed. Hence we concentrate on the first level requirements only.
 *
 * If the only logged use of a package is a *Bundle.php, it's probably only registered in the AppKernel and not really
 * used, i.e. can be deleted.
 */
final class Task
{
    /**
     * @var string
     */
    private $pathToVendor;

    /**
     * @param string $pathToComposerJson
     * @param string|null $pathToVendor
     * @param string[] $usedFiles
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    public function getUnusedPackagePaths($pathToComposerJson, $pathToVendor, array $usedFiles, array $blacklistRegExps)
    {
        $unusedPackagePaths = [];
        $usedFiles = $this->getRelevantUsedFiles($usedFiles);

        $pathToVendor = $pathToVendor ?: $this->getDefaultPathToVendor($pathToComposerJson);
        $this->pathToVendor = $this->assertPathToVendorIsValid($pathToVendor);

        foreach ($this->getRelevantPackagePaths($pathToComposerJson, $blacklistRegExps) as $packagePath) {
            if (!$this->atLeastOneFileIsInPath($usedFiles, $packagePath)) {
                $unusedPackagePaths[] = $packagePath;
            }
        }

        return $unusedPackagePaths;
    }

    /**
     * @param string[] $usedFiles
     * @return string[]
     */
    private function getRelevantUsedFiles(array $usedFiles)
    {
        return array_filter($usedFiles, function ($usedFile) { return strpos($usedFile, 'Bundle.php') === false; });
    }

    /**
     * @param string $pathToComposerJson
     * @return string
     */
    private function getDefaultPathToVendor($pathToComposerJson)
    {
        $projectRoot = realpath(dirname($pathToComposerJson));
        return $projectRoot . '/vendor';
    }

    /**
     * @param string $path
     * @return string path to a readable directory with a trailing slash
     */
    private function assertPathToVendorIsValid($path)
    {
        if (is_dir($path) === false) {
            $message = 'The path "' . $path . '" is no valid directory.';
        } elseif (is_readable($path) === false) {
            $message ='The directory "' . $path . '" is not readable.';
        }

        if (isset($message)) {
            $message .= ' Please specify a readable directory with the ' . Command::OPTION_VENDOR_DIRECTORY . ' '
                      . 'option.';
            throw new \InvalidArgumentException($message);
        }

        return rtrim($path, '/') . '/';
    }

    /**
     * @param string $pathToComposerJson
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    private function getRelevantPackagePaths($pathToComposerJson, array $blacklistRegExps)
    {
        $packagePaths = [];
        $composer = Factory::create(new BufferIO(), $pathToComposerJson);

        foreach ($composer->getPackage()->getRequires() as $link) {
            $package = $composer->getLocker()->getLockedRepository()->findPackage($link->getTarget(), $link->getConstraint());
            if ($package === null) {
                continue;
            }

            $packagePath = realpath($this->getInstallPath($composer, $package));
            if ($this->packagePathIsBlacklisted($packagePath, $blacklistRegExps)) {
                continue;
            }

            $packagePaths[] = $packagePath;
        }

        return $packagePaths;
    }

    /**
     * @param string[] $files
     * @param string $path
     * @return bool
     */
    private function atLeastOneFileIsInPath(array $files, $path)
    {
        foreach ($files as $file) {
            if (strpos($file, $path) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Composer $composer
     * @param PackageInterface $package
     * @return string
     */
    private function getInstallPath(Composer $composer, PackageInterface $package)
    {
        $pathToVendorInZauberlehrling = $composer->getConfig()->get('vendor-dir');

        $pathToPackageInstallationInZauberlehrling = $composer->getInstallationManager()->getInstallPath($package);
        $pathToPackageInstallationInProject = str_replace($pathToVendorInZauberlehrling, $this->pathToVendor, $pathToPackageInstallationInZauberlehrling);
        return realpath($pathToPackageInstallationInProject);
    }

    /**
     * @param string $path
     * @param string[] $blacklistRegExps
     * @return bool
     */
    private function packagePathIsBlacklisted($path, array $blacklistRegExps)
    {
        foreach ($blacklistRegExps as $blacklistRegExp) {
            if (preg_match($blacklistRegExp, $path) === 1 || preg_match($blacklistRegExp, $path . '/') === 1) {
                return true;
            }
        }

        return false;
    }
}

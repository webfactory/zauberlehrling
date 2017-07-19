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
     * @return PackageInterface[]
     */
    public function getUnusedComposerPackages($pathToComposerJson, $pathToVendor, array $usedFiles, array $blacklistRegExps)
    {
        $unusedPackages = [];
        $usedFiles = $this->cutDownToRelevantUsedFiles($usedFiles);

        $composer = Factory::create(new BufferIO(), $pathToComposerJson);

        $pathToVendor = $pathToVendor ?: $this->getDefaultPathToVendor($pathToComposerJson);
        $this->pathToVendor = $this->assertPathToVendorIsValid($pathToVendor);

        foreach ($composer->getPackage()->getRequires() as $link) {
            $package = $composer->getLocker()->getLockedRepository()->findPackage($link->getTarget(), $link->getConstraint());
            if ($package === null) {
                continue;
            }

            $pathToPackageInstallation = realpath($this->getInstallPath($composer, $package));

            foreach ($blacklistRegExps as $blacklistRegExp) {
                if (preg_match($blacklistRegExp, $pathToPackageInstallation) === 1) {
                    continue 2;
                }
            }

            $packageInstallationIsInUsedFiles = false;
            foreach ($usedFiles as $usedFile) {
                if (strpos($usedFile, $pathToPackageInstallation) !== false) {
                    $packageInstallationIsInUsedFiles = true;
                    break;
                }
            }

            if ($packageInstallationIsInUsedFiles === false) {
                $unusedPackages[] = $package;
            }
        }

        return $unusedPackages;
    }

    /**
     * @param string[] $usedFiles
     * @return string[]
     */
    private function cutDownToRelevantUsedFiles(array $usedFiles)
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
}

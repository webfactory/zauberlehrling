<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Finder\Finder;

/**
 * Get all PHP files in a given directory, minus the used and temporary files, and offers the rest for deletion.
 */
final class Task
{
    /**
     * @param string $pathToInspect
     * @param string $pathToIgnore
     * @param string[] $usedFiles
     * @return string[]
     */
    public function getUnusedPhpFiles($pathToInspect, $pathToIgnore, $usedFiles)
    {
        $existingPhpFiles = $this->getExistingPhpFiles($pathToInspect, $pathToIgnore);
        $unusedPhpFiles = array_diff($existingPhpFiles, $usedFiles);
        sort($unusedPhpFiles);

        return $unusedPhpFiles;
    }

    /**
     * @param string $pathToInspect
     * @param string $pathToIgnore
     * @return string[]
     */
    private function getExistingPhpFiles($pathToInspect, $pathToIgnore)
    {
        $existingPhpFiles = [];

        $finder = new Finder();
        foreach ($finder->in($pathToInspect)->files()->name('*.php') as $foundFileInfo) {
            /** @var $foundFileInfo \Symfony\Component\Finder\SplFileInfo */
            if (strpos($foundFileInfo->getRealPath(), $pathToIgnore) === 0) {
                continue;
            }

            $existingPhpFiles[] = $foundFileInfo->getRealPath();
        }

        return $existingPhpFiles;
    }
}




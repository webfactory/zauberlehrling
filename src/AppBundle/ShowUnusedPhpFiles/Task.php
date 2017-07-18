<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Finder\Finder;

/**
 * Get all PHP files in a given directory, minus the used and temporary files, and offers the rest for deletion.
 */
final class Task
{
    /**
     * @param string $pathToIgnore
     * @param string[] $usedFiles
     * @param string|null $pathToInspect
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function getUnusedPhpFiles($pathToIgnore, $usedFiles, $pathToInspect)
    {
        if (count($usedFiles) === 0) {
            throw new \InvalidArgumentException('Empty list for used files');
        }

        if ($pathToInspect === null) {
            $pathToInspect = $this->guessPathToInspect($usedFiles);
        }

        $existingPhpFiles = $this->getExistingPhpFiles($pathToInspect, $pathToIgnore);
        $unusedPhpFiles = array_diff($existingPhpFiles, $usedFiles);
        sort($unusedPhpFiles);

        return $unusedPhpFiles;
    }

    /**
     * @param string[] $usedFiles
     * @return string
     */
    private function guessPathToInspect(array $usedFiles)
    {
        return (new CommonPathDeterminator())->determineCommonPath($usedFiles);
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

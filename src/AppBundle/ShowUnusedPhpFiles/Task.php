<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Symfony\Component\Finder\Finder;

/**
 * Get all PHP files in a given directory, minus the used and temporary files, and offers the rest for deletion.
 */
final class Task
{
    /**
     * @param string[] $usedFiles
     * @param string|null $pathToInspect
     * @param string[] $blacklistRegExps
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function getUnusedPhpFiles(array $usedFiles, $pathToInspect, array $blacklistRegExps)
    {
        if (count($usedFiles) === 0) {
            throw new \InvalidArgumentException('Empty list for used files');
        }

        if ($pathToInspect === null) {
            $pathToInspect = $this->guessPathToInspect($usedFiles);
        }

        $existingRelevantPhpFiles = $this->getExistingRelevantPhpFiles($pathToInspect, $blacklistRegExps);
        $unusedPhpFiles = array_diff($existingRelevantPhpFiles, $usedFiles);
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
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    private function getExistingRelevantPhpFiles($pathToInspect, array $blacklistRegExps)
    {
        $existingPhpFiles = [];

        foreach ((new Finder())->in($pathToInspect)->files()->name('*.php')->getIterator() as $foundFileInfo) {
            /** @var $foundFileInfo \Symfony\Component\Finder\SplFileInfo */
            foreach ($blacklistRegExps as $blacklistRegExp) {
                if (preg_match($blacklistRegExp, $foundFileInfo->getRealPath()) === 1) {
                    continue 2;
                }
            }

            $existingPhpFiles[] = $foundFileInfo->getRealPath();
        }

        return $existingPhpFiles;
    }
}

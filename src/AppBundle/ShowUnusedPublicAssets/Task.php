<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Symfony\Component\Finder\Finder;

/**
 * Get all asset files in the public web directory and it's subdirecories that were not accessed according to webserver
 * logs.
 */
final class Task
{
    /**
     * @param string $pathToPublic
     * @param string[] $logEntries
     * @param string $regExpToFindFile
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    public function getUnusedPublicAssets($pathToPublic, array $logEntries, $regExpToFindFile, array $blacklistRegExps)
    {
        $relevantPublicAssets = $this->getPublicAssets($pathToPublic, $blacklistRegExps);
        $usedAssets = $this->getUsedAssets($pathToPublic, $logEntries, $regExpToFindFile);

        return array_diff($relevantPublicAssets, $usedAssets);
    }

    /**
     * @param string $pathToPublic
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    private function getPublicAssets($pathToPublic, array $blacklistRegExps)
    {
        $existingAssets = [];

        foreach ((new Finder())->in($pathToPublic)->files()->getIterator() as $foundFileInfo) {
            /** @var $foundFileInfo \Symfony\Component\Finder\SplFileInfo */
            foreach ($blacklistRegExps as $blacklistRegExp) {
                if (preg_match($blacklistRegExp, $foundFileInfo->getRealPath()) === 1) {
                    continue 2;
                }
            }

            $existingAssets[] = $foundFileInfo->getRealPath();
        }

        return $existingAssets;
    }

    /**
     * @param string $pathToPublic
     * @param string[] $logEntries
     * @param string $regExpToFindFile
     * @return string[]
     */
    private function getUsedAssets($pathToPublic, array $logEntries, $regExpToFindFile)
    {
        $usedAssets = [];
        $regExpMatches = [];

        foreach ($logEntries as $logEntry) {
            if (preg_match($regExpToFindFile, $logEntry, $regExpMatches) === 1) {
                $usedAssets[] = realpath($pathToPublic . $regExpMatches[1]);
            }
        }

        $usedAssets = array_unique($usedAssets);
        sort($usedAssets);

        return $usedAssets;
    }
}

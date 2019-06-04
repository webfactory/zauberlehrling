<?php

namespace AppBundle\ShowUnusedPublicAssets;

use Helper\FileSystem;
use Helper\NullStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Finder\Finder;

/**
 * Get all asset files in the public web directory and it's subdirecories that were not accessed according to webserver
 * logs.
 */
final class Task
{
    /**
     * @param string $pathToPublic
     * @param string $pathToLogfile
     * @param string $regExpToFindFile
     * @param string|null $pathToOutput
     * @param string|null $pathToBlacklist
     * @param StyleInterface|null $ioStyle
     */
    public function getUnusedPublicAssets($pathToPublic, $pathToLogfile, $regExpToFindFile, $pathToOutput, $pathToBlacklist, StyleInterface $ioStyle = null)
    {
        $ioStyle = $ioStyle ?: new NullStyle();
        $ioStyle->text('Started.');

        $accessedUrls = $this->getAccessedUrls($pathToPublic, $pathToLogfile, $regExpToFindFile);
        $ioStyle->text('Found ' . count($accessedUrls) . ' distinct accessed URLs.');

        $blacklistingRegExps = FileSystem::getBlacklistingRegExps($pathToBlacklist);
        $foundFilesInfos = iterator_to_array((new Finder())->in($pathToPublic)->files()->getIterator());
        $relevantPublicAssets = FileSystem::filterFilesIn($foundFilesInfos, $blacklistingRegExps);

        $message = 'Found ' . count($relevantPublicAssets) . ' public assets';
        $numberOfBlacklistingRegExps = count($blacklistingRegExps);
        if ($numberOfBlacklistingRegExps > 0) {
            $message .= ' not matched by the ' . $numberOfBlacklistingRegExps . ' blacklisting regular expressions';
        }
        $ioStyle->text($message . ' in ' . $pathToPublic . '.');

        $unusedAssets = array_diff($relevantPublicAssets, $accessedUrls);
        sort($unusedAssets);

        $pathToOutput = FileSystem::getPathToOutput($pathToOutput, $pathToPublic, 'potentially-unused-public-assets.txt');
        FileSystem::writeArrayToFile($unusedAssets, $pathToOutput);
        $ioStyle->success([
            'Finished writing list of ' . count($unusedAssets) . ' potentially unused public assets. Please inspect the '
                . 'output file ' . $pathToOutput,
            'For files you want to keep (even if they are not used according to the webserver access logs), you '
                . 'can maintain a blacklist. With it, you can exclude these files from the output of further runs of '
                . 'this command. See --help or the readme for details.',
            'Once you are sure you can restore the rest of the files (ideally from your version control system), try '
                . 'deleting them, e.g. with "xargs -0 -d \'\n\' rm < ' . $pathToOutput . '", rerun your tests and check your logs '
                . 'for 404s to see if that broke anything.',
        ]);
    }

    /**
     * @param string $pathToPublic
     * @param string $pathToLogfile
     * @param string $regExpToFindFile
     * @return string[]
     */
    private function getAccessedUrls($pathToPublic, $pathToLogfile, $regExpToFindFile)
    {
        $logEntries = FileSystem::readFileIntoArray($pathToLogfile);
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

<?php

namespace AppBundle\ShowUnusedPhpFiles;

use Helper\FileSystem;
use Helper\NullStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Finder\Finder;

/**
 * Get all PHP files in a given directory, minus the used and temporary files, and offers the rest for deletion.
 */
final class Task
{
    /**
     * @param string $userProvidedPathToUsedFiles
     * @param string|null $pathToInspect
     * @param string|null $userProvidedPathToOutput
     * @param string|null $userProvidedPathToBlacklist
     * @param StyleInterface|null $ioStyle
     * @throws \InvalidArgumentException
     */
    public function getUnusedPhpFiles($userProvidedPathToUsedFiles, $pathToInspect, $userProvidedPathToOutput, $userProvidedPathToBlacklist, StyleInterface $ioStyle = null)
    {
        $ioStyle = $ioStyle ?: new NullStyle();
        $ioStyle->text('Started.');

        $usedFiles = FileSystem::readFileIntoArray($userProvidedPathToUsedFiles);
        if (count($usedFiles) === 0) {
            throw new \InvalidArgumentException('Empty list for used files');
        }
        $ioStyle->text('Found ' . count($usedFiles) . ' used files.');

        if ($pathToInspect === null) {
            $pathToInspect = $this->guessPathToInspect($usedFiles);
            $ioStyle->text('Determined ' . $pathToInspect . ' as root for inspection (see --help to set it manually).');
        }

        $foundFilesInfos = iterator_to_array((new Finder())->in($pathToInspect)->files()->name('*.php')->getIterator());
        $blacklistingRegExps = FileSystem::getBlacklistingRegExps($userProvidedPathToBlacklist);
        $foundFiles = FileSystem::filterFilesIn($foundFilesInfos, $blacklistingRegExps);

        $message = 'Found ' . count($foundFiles) . ' used PHP files';
        $numberOfBlacklistingRegExps = count($blacklistingRegExps);
        if ($numberOfBlacklistingRegExps > 0) {
            $message .= ' not matched by the ' . $numberOfBlacklistingRegExps . ' blacklisting regular expressions';
        }
        $ioStyle->text($message . ' in ' . $pathToInspect . '.');

        $unusedPhpFiles = array_diff($foundFiles, $usedFiles);
        sort($unusedPhpFiles);

        $pathToOutput = FileSystem::getPathToOutput($userProvidedPathToOutput, $userProvidedPathToUsedFiles, 'potentially-unused-files.txt');
        FileSystem::writeArrayToFile($unusedPhpFiles, $pathToOutput);

        $successMessages = count($unusedPhpFiles) === 0
            ? [
                'No potentially unused PHP files found.'
            ]
            : [
                'Finished writing list of ' . count($unusedPhpFiles) . ' potentially unused PHP files. Please inspect the '
                    . 'output file ' . $pathToOutput,
                'For files you want to keep (even if they are not used according to the code coverage of your tests), you '
                    . 'can maintain a blacklist. With it, you can exclude these files from the output of further runs of '
                    . 'this command. See --help or the readme for details.',
                'Once you are sure you can restore the rest of the files (ideally from your version control system), try '
                    . 'deleting them, e.g. with "xargs -0 -d \'\n\' rm < ' . $pathToOutput . '" and rerun your tests to see if that '
                    . 'broke anything.',
            ];
        $ioStyle->success($successMessages);
    }

    /**
     * @param string[] $usedFiles
     * @return string
     */
    private function guessPathToInspect(array $usedFiles)
    {
        return (new CommonPathDeterminator())->determineCommonPath($usedFiles);
    }
}

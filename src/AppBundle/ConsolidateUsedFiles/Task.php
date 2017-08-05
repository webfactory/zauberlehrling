<?php

namespace AppBundle\ConsolidateUsedFiles;

use Helper\FileSystem;
use Helper\NullStyle;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * Consolidate the list of used files by removing duplicates and sorting them. Improves performance for later tasks.
 */
final class Task
{
    /**
     * @param string $userProvidedPathToConsolidate
     * @param StyleInterface|null $ioStyle
     */
    public function consolidate($userProvidedPathToConsolidate, StyleInterface $ioStyle = null)
    {
        $ioStyle = $ioStyle ?: new NullStyle();
        $ioStyle->progressStart(4);

        $pathToConsolidate = FileSystem::getRealPathToReadableAndWritableFile($userProvidedPathToConsolidate);
        if ($pathToConsolidate === null) {
            $message = $userProvidedPathToConsolidate . ' has to be a file both readable and writable.';
            $ioStyle->error($message);
            throw new \InvalidArgumentException($message);
        }
        $ioStyle->progressAdvance();

        $usedFiles = FileSystem::readFileIntoArray($pathToConsolidate);
        $ioStyle->progressAdvance();

        $usedFiles = array_unique($usedFiles);
        sort($usedFiles);
        $ioStyle->progressAdvance();

        FileSystem::writeArrayToFile($usedFiles, $pathToConsolidate);
        $ioStyle->progressFinish();

        $ioStyle->success('Finished consolidating ' . $pathToConsolidate);
    }
}

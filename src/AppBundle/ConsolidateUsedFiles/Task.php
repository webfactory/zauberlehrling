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

        $pathToConsolidate = FileSystem::getRealPathToReadableAndWritableFile($userProvidedPathToConsolidate);
        if ($pathToConsolidate === null) {
            $message = $userProvidedPathToConsolidate . ' has to be a file both readable and writable.';
            $ioStyle->error($message);
            throw new \InvalidArgumentException($message);
        }

        $usedFiles = FileSystem::readFileIntoArray($pathToConsolidate);
        $usedFiles = array_unique($usedFiles);
        sort($usedFiles);
        FileSystem::writeArrayToFile($usedFiles, $pathToConsolidate);

        $ioStyle->success('Finished consolidating ' . $pathToConsolidate);
    }
}

<?php

namespace AppBundle\ConsolidateUsedFiles;

/**
 * Consolidate the list of used files by removing duplicates and sorting them. Improves performance for later tasks.
 */
final class Task
{
    /**
     * @param string $pathToUsedFiles
     */
    public function consolidate($pathToUsedFiles)
    {
        $usedFiles = file($pathToUsedFiles, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $usedFiles = array_unique($usedFiles);
        sort($usedFiles);

        $handle = fopen($pathToUsedFiles, 'wb');
        if ($handle === false) {
            throw new \InvalidArgumentException($pathToUsedFiles . ' is not writeable');
        }

        fwrite($handle, implode(PHP_EOL, $usedFiles));
        fclose($handle);
    }
}

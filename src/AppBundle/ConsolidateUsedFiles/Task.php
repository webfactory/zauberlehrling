<?php

namespace AppBundle\ConsolidateUsedFiles;

use Helper\NullStyle;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * Consolidate the list of used files by removing duplicates and sorting them. Improves performance for later tasks.
 */
final class Task
{
    /**
     * @param string $pathToUsedFiles
     * @param StyleInterface|null $ioStyle
     */
    public function consolidate($pathToUsedFiles, StyleInterface $ioStyle = null)
    {
        if ($ioStyle === null) {
            $ioStyle = new NullStyle();
        }
        $ioStyle->progressStart(4);

        $usedFiles = file($pathToUsedFiles, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $ioStyle->progressAdvance();

        $usedFiles = array_unique($usedFiles);
        $ioStyle->progressAdvance();

        sort($usedFiles);
        $ioStyle->progressAdvance();

        $handle = fopen($pathToUsedFiles, 'wb');
        fwrite($handle, implode(PHP_EOL, $usedFiles));
        fclose($handle);
        $ioStyle->progressFinish();
    }
}

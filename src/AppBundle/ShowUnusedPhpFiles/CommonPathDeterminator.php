<?php

namespace AppBundle\ShowUnusedPhpFiles;

/**
 * Determines the (parent) path common to an array of paths.
 */
final class CommonPathDeterminator
{
    /**
     * @param string[] $paths
     * @return string
     */
    public function determineCommonPath(array $paths)
    {
        if (count($paths) === 0) {
            return '';
        }

        $commonCharacters = $this->determineCommonCharacters($paths);
        $commonPath = $this->getPathFromCommonCharacters($commonCharacters);

        return $commonPath;
    }

    /**
     * @param string[] $paths
     * @return string
     */
    private function determineCommonCharacters(array $paths)
    {
        $commonCharacters = $paths[0];

        foreach ($paths as $path) {
            if ($this->stringBeginsWith($path, $commonCharacters)) {
                continue;
            }

            do {
                $commonCharacters = substr($commonCharacters, 0, -1);
            } while (!$this->stringBeginsWith($path, $commonCharacters) && $commonCharacters !== '');
        }

        return $commonCharacters;
    }

    /**
     * @param string $hay
     * @param string $needle
     * @return bool
     */
    private function stringBeginsWith($hay, $needle)
    {
        if ($needle === '') {
            return '';
        }

        return strpos($hay, $needle) === 0;
    }

    /**
     * @param string $commonCharacters
     * @return string
     */
    private function getPathFromCommonCharacters($commonCharacters)
    {
        if ($commonCharacters === '') {
            return '';
        }

        $path = (substr($commonCharacters, -1) === '/') ? $commonCharacters : dirname($commonCharacters);
        return realpath($path);
    }
}

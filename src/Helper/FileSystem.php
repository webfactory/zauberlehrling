<?php

namespace Helper;

/**
 * Helper for common file system functions.
 */
final class FileSystem
{
    /**
     * @param string $userProvidedPath
     * @return string|null
     */
    public static function getRealPathToReadableAndWritableFile($userProvidedPath)
    {
        $realPath = realpath(dirname($userProvidedPath)) . '/' . basename($userProvidedPath);
        return is_file($realPath) && is_readable($realPath) && is_writable($realPath) ? $realPath : null;
    }

    /**
     * @param string $path
     * @return string[]
     */
    public static function readFileIntoArray($path)
    {
        $array = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($array === false) {
            throw new \RuntimeException($path . ' could not be read.');
        }

        return $array;
    }

    /**
     * @param string[] $lines
     * @param string $path
     */
    public static function writeArrayToFile(array $lines, $path)
    {
        $handle = fopen($path, 'wb');
        fwrite($handle, implode(PHP_EOL, $lines));
        fclose($handle);
    }

    /**
     * @param string|null $userProvidedPathToBlacklist
     * @return string[]
     */
    public static function getBlacklistingRegExps($userProvidedPathToBlacklist = null)
    {
        if ($userProvidedPathToBlacklist === null) {
            return [];
        }

        if (!is_file($userProvidedPathToBlacklist) || !is_readable($userProvidedPathToBlacklist)) {
            throw new \InvalidArgumentException($userProvidedPathToBlacklist . ' is no readable file');
        }

        return self::readFileIntoArray($userProvidedPathToBlacklist);
    }

    /**
     * @param \SplFileInfo[] $foundFilesInfos
     * @param string[] $blacklistRegExps
     * @return string[]
     */
    public static function filterFilesIn(array $foundFilesInfos, array $blacklistRegExps)
    {
        $filteredFiles = [];

        /** @var \Iterator $foundFilesInfos */
        foreach ($foundFilesInfos as $foundFileInfo) {
            /** @var $foundFileInfo \SplFileInfo */
            foreach ($blacklistRegExps as $blacklistRegExp) {
                if (preg_match($blacklistRegExp, $foundFileInfo->getRealPath()) === 1) {
                    continue 2;
                }
            }

            $filteredFiles[] = $foundFileInfo->getRealPath();
        }

        return $filteredFiles;
    }

    /**
     * @param string|null $userProvidedPathToOutput
     * @param string $fallbackPath
     * @param string $fallbackFileName
     * @return string
     */
    public static function getPathToOutput($userProvidedPathToOutput = null, $fallbackPath, $fallbackFileName)
    {
        $pathToOutput = ($userProvidedPathToOutput !== null)
            ? realpath(dirname($userProvidedPathToOutput)) . '/' . basename($userProvidedPathToOutput)
            : realpath(dirname($fallbackPath)) . '/' . $fallbackFileName;

        if (is_file($pathToOutput) && !is_writable($pathToOutput)) {
            throw new \InvalidArgumentException('Output file ' . $pathToOutput . ' is not writable');
        }

        if (!is_writable(dirname($pathToOutput))) {
            throw new \InvalidArgumentException('Output path ' . dirname($pathToOutput) . ' is not writable');
        }

        return $pathToOutput;
    }
}

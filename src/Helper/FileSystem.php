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
     * @return string[]|bool
     */
    public static function readFileIntoArray($path)
    {
        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
}

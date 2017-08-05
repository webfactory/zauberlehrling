<?php

namespace Helper;

/**
 * Test for the file system helper.
 */
final class FileSystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Original file permissions before chmod'ing them for a test.
     *
     * @var int
     */
    public static $originalPermissions;

    /**
     * Set the permissions on a file needed for a test, however it was git cloned.
     * Works only for a single file at a time.
     *
     * @param int $permissions
     * @param string $path
     */
    public static function ensurePermissionsFor($permissions, $path)
    {
        static::$originalPermissions = fileperms($path);
        if (chmod($path, $permissions) === false) {
            static::markTestSkipped('Test system does not support chmod\'ing.');
        }
    }

    /**
     * Restore original permissions so git does not recognise a modification.
     * Works only for a single file at a time.
     *
     * @param string $path
     */
    public static function restoreOriginalPermissionsFor($path)
    {
        chmod($path, static::$originalPermissions);
    }

    /**
     * @test
     */
    public function getRealPathToReadableAndWritableFileReturnsRealPath()
    {
        $this->assertEquals(
            __DIR__ . '/fixtures/regular-file.txt',
            FileSystem::getRealPathToReadableAndWritableFile(__DIR__ . '/../Helper/fixtures/regular-file.txt')
        );
    }

    /**
     * @test
     */
    public function getRealPathToReadableAndWritableFileReturnsNullIfFileNotExists()
    {
        $this->assertNull(FileSystem::getRealPathToReadableAndWritableFile(__DIR__ . '/non-existing-file'));
    }

    /**
     * @test
     */
    public function getRealPathToReadableAndWritableFileReturnsNullIfFileIsDirectory()
    {
        $this->assertNull(FileSystem::getRealPathToReadableAndWritableFile(__DIR__));
    }

    /**
     * @test
     */
    public function getRealPathToReadableAndWritableFileReturnsNullIfFileIsUnreadable()
    {
        $pathToUnreadableYetWritableFile = __DIR__ . '/fixtures/unreadable-yet-writable-file.txt';
        static::ensurePermissionsFor(0200, $pathToUnreadableYetWritableFile);

        $this->assertNull(FileSystem::getRealPathToReadableAndWritableFile($pathToUnreadableYetWritableFile));

        static::restoreOriginalPermissionsFor($pathToUnreadableYetWritableFile);
    }

    /**
     * @test
     */
    public function getRealPathToReadableAndWritableFileReturnsNullIfFileIsUnwritable()
    {
        $pathToUnwritableYetReadableFile = __DIR__ . '/fixtures/unwritable-yet-readable-file.txt';
        static::ensurePermissionsFor(0400, $pathToUnwritableYetReadableFile);

        $this->assertNull(FileSystem::getRealPathToReadableAndWritableFile($pathToUnwritableYetReadableFile));

        static::restoreOriginalPermissionsFor($pathToUnwritableYetReadableFile);
    }
}

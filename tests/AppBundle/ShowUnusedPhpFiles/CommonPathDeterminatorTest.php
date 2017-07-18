<?php

namespace AppBundle\ShowUnusedPhpFiles;

/**
 * Tests for the CommonPathDeterminator.
 */
final class CommonPathDeterminatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function returnsEmptyStringForEmptyInput()
    {
        $result = (new CommonPathDeterminator())->determineCommonPath([]);
        $this->assertInternalType('string', $result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function returnsEmptyStringIfNoCommonPath()
    {
        $result = (new CommonPathDeterminator())->determineCommonPath(['abc', 'def']);
        $this->assertInternalType('string', $result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function returnsCommonPath()
    {
        $result = (new CommonPathDeterminator())->determineCommonPath(['/var/a', '/var/b']);
        $this->assertInternalType('string', $result);
        $this->assertEquals('/var', $result);
    }

    /**
     * @test
     */
    public function returnsCommonPathAndIsNotTrickedByCommonFileName()
    {
        $result = (new CommonPathDeterminator())->determineCommonPath(['/var/common-1', '/var/common-2']);
        $this->assertInternalType('string', $result);
        $this->assertEquals('/var', $result);
    }
}

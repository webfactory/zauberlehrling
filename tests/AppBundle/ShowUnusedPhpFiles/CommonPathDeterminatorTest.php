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
        $result = (new CommonPathDeterminator())->determineCommonPath([__DIR__ . '/fixtures/ignored', __DIR__ . '/fixtures/used']);
        $this->assertInternalType('string', $result);
        $this->assertEquals(__DIR__ . '/fixtures', $result);
    }

    /**
     * @test
     */
    public function returnsCommonPathAndIsNotTrickedByCommonFileName()
    {
        $result = (new CommonPathDeterminator())->determineCommonPath([__DIR__ . '/fixtures/common-1', __DIR__ . '/fixtures/common-2']);
        $this->assertInternalType('string', $result);
        $this->assertEquals(__DIR__ . '/fixtures', $result);
    }
}

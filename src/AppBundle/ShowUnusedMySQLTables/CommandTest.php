<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the ShowUnusedMySQLTables console command.
 */
final class CommandTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        // set up command tester
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new Command());
        $this->command = $application->find('show-unused-mysql-tables');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @test
     */
    public function successOutput()
    {
        $this->commandTester->execute([
            'command'  => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[OK]', $output);
    }
}

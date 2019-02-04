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
        // set up an MySQLish environment
        $connection = self::$kernel->getContainer()->get('doctrine.dbal.default_connection');
        $connection->exec('
          CREATE TABLE general_log (
              `event_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `user_host` mediumtext NOT NULL,
              `thread_id` int(11) NOT NULL,
              `server_id` int(10) NOT NULL,
              `command_type` varchar(64) NOT NULL,
              `argument` mediumtext NOT NULL
          )'
        );

        $this->commandTester->execute([
            'command'  => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains('[OK]', $output);
    }
}

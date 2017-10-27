<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tests for the ShowUnusedMySQLTables task.
 */
final class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var Task
     */
    private $task;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->connection->expects($this->any())
                         ->method('getDatabasePlatform')
                         ->willReturn($this->getMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class));

        $this->task = new Task($this->connection, '');
    }

    /**
     * @test
     */
    public function unusedTableNamesGetReported()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo', 'bar']);
        $this->setUpSelectStatementToReturn([]);

        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedTableNames($ioStyle);

        $outputAsString = $output->fetch();
        $this->assertContains('Calculated 2 potentially unused tables', $outputAsString);
        $this->assertContains('foo', $outputAsString);
        $this->assertContains('bar', $outputAsString);
    }

    /**
     * @test
     */
    public function usedTableNamesDontGetReported()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo', 'bar', 'baz']);
        $this->setUpSelectStatementToReturn(['SELECT * FROM foo', 'SELECT bar.*, baz.* FROM bar JOIN baz']);

        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedTableNames($ioStyle);

        $outputAsString = $output->fetch();
        $this->assertContains('Calculated 0 potentially unused tables', $outputAsString);
        $this->assertNotContains('foo', $outputAsString);
        $this->assertNotContains('bar', $outputAsString);
        $this->assertNotContains('baz', $outputAsString);
    }

    /**
     * @test
     */
    public function unusedTableNamesGetReportedWithoutLoggedSelectStatements()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo']);
        $this->setUpSelectStatementToReturn(['DESCRIBE foo']);

        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedTableNames($ioStyle);

        $outputAsString = $output->fetch();
        $this->assertContains('Calculated 1 potentially unused table', $outputAsString);
        $this->assertContains('foo', $outputAsString);
    }

    /**
     * @test
     */
    public function commentQueriesDoNoHarm()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo']);
        $this->setUpSelectStatementToReturn(['-- MySQL dump 10.13  Distrib 5.5.49, for debian-linux-gnu (x86_64)']);

        $output = new BufferedOutput();
        $ioStyle = new SymfonyStyle(new StringInput(''), $output);
        $this->task->getUnusedTableNames($ioStyle);

        $outputAsString = $output->fetch();
        $this->assertContains('Calculated 1 potentially unused table', $outputAsString);
        $this->assertContains('foo', $outputAsString);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function setUpSelectStatementToReturn(array $returnValues)
    {
        $mockedStatement = $this->getMockBuilder(Statement::class)->disableOriginalConstructor()->getMock();

        $numberOfReturnValues = count($returnValues);
        $mockedStatement->expects($this->at(0))
                        ->method('rowCount')
                        ->willReturn($numberOfReturnValues);

        for ($i = 0; $i < $numberOfReturnValues; $i++) {
            $mockedStatement->expects($this->at($i+1))
                            ->method('fetch')
                            ->willReturn($returnValues[$i]);
        }
        $mockedStatement->expects($this->at($i))
                        ->method('fetch')
                        ->willReturn(false);

        $mockedQueryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $mockedQueryBuilder->expects($this->any())
                           ->method('select')
                           ->willReturnSelf();
        $mockedQueryBuilder->expects($this->any())
                           ->method('from')
                           ->willReturnSelf();
        $mockedQueryBuilder->expects($this->any())
                           ->method('where')
                           ->willReturnSelf();
        $mockedQueryBuilder->expects($this->any())
                           ->method('groupBy')
                           ->willReturnSelf();
        $mockedQueryBuilder->expects($this->any())
                           ->method('execute')
                           ->willReturn($mockedStatement);

        $this->connection->expects($this->any())
                         ->method('createQueryBuilder')
                         ->willReturn($mockedQueryBuilder);

        return $mockedStatement;
    }

    /**
     * @param string[] $existingTableNames
     */
    private function setUpConnectionToReturnAsAllTablenames(array $existingTableNames)
    {
        $existingTables = [];
        foreach ($existingTableNames as $existingTableName) {
            $existingTables[] = new Table($existingTableName);
        }

        $mockedSchemaManager = $this->getMockBuilder(AbstractSchemaManager::class)->disableOriginalConstructor()->getMock();
        $mockedSchemaManager->expects($this->any())
                            ->method('listTables')
                            ->willReturn($existingTables);

        $this->connection->expects($this->any())
                         ->method('getSchemaManager')
                         ->willReturn($mockedSchemaManager);
    }
}


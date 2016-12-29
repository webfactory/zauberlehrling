<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;

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

        $this->task = new Task($this->connection);
    }

    /**
     * @test
     */
    public function unusedTableNamesGetReported()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo', 'bar']);
        $this->setUpSelectStatementToReturn([]);

        $unusedTableNames = $this->task->getUnusedTableNames();

        $this->assertCount(2, $unusedTableNames);
        $this->assertContains('foo', $unusedTableNames);
        $this->assertContains('bar', $unusedTableNames);
    }

    /**
     * @test
     */
    public function usedTableNamesDontGetReported()
    {
        $this->setUpConnectionToReturnAsAllTablenames(['foo', 'bar', 'baz']);
        $this->setUpSelectStatementToReturn(['SELECT * FROM foo', 'SELECT bar.*, baz.* FROM bar JOIN baz']);

        $unusedTableNames = $this->task->getUnusedTableNames();

        $this->assertEmpty($unusedTableNames);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function setUpSelectStatementToReturn(array $returnValues)
    {
        $mockedStatement = $this->getMockBuilder(Statement::class)->disableOriginalConstructor()->getMock();

        for ($i = 0; $i < count($returnValues); $i++) {
            $mockedStatement->expects($this->at($i))
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


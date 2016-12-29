<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Table;
use PHPSQLParser\PHPSQLParser;

/**
 * Get the names of unused MySQL tables.
 *
 * The idea is analogous to the code coverage. First, enable logging in MySQL, e.g. with
 *
 * SET global general_log = 1;
 * SET global log_output = 'table';
 *
 * You might want to delete old log data:
 *
 * TRUNCATE mysql.general_log;
 *
 * Then execute all use cases of your application, e.g. with behat tests. After that, you can disable MySQl logging with
 *
 * SET global general_log = 0;
 *
 * Then, parse the logged queries and extract the names of the queried tables. Finally, intersect this set with the set
 * of all table names (retrieved via the default Doctrine connection) and you have the names of the unused tables.
 */
final class Task
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * @return string[]
     */
    public function getUnusedTableNames()
    {
        $unusedTableNames = [];
        $usedTableNames = $this->getUsedTableNames();

        foreach ($this->getAllTables() as $table) {
            if (!in_array($table->getName(), $usedTableNames)) {
                $unusedTableNames[] = $table->getName();
            }
        }

        return $unusedTableNames;
    }

    /**
     * @return Table[]
     */
    private function getAllTables()
    {
        return $this->connection->getSchemaManager()->listTables();
    }

    /**
     * @return string[]
     */
    private function getUsedTableNames()
    {
        $usedTableNames = [];

        foreach ($this->extractTableNamesFromLoggedQueries() as $usedTableName) {
            if (!in_array($usedTableName, $usedTableNames)) {
                $usedTableNames[] = $usedTableName;
            }
        }

        return $usedTableNames;
    }

    /**
     * @return string[]
     */
    private function extractTableNamesFromLoggedQueries()
    {
        $usedTableNames = [];
        $stmt = $this->getLoggedQueriesStatement();
        while ($loggedQuery = $stmt->fetch(\PDO::FETCH_COLUMN)) {
            foreach ($this->extractTableNamesFromLoggedQuery($loggedQuery) as $usedTableName) {
                if (!in_array($usedTableName, $usedTableNames)) {
                    $usedTableNames[] = $usedTableName;
                }
            }
        }

        return $usedTableNames;
    }

    /**
     * @return Statement
     */
    private function getLoggedQueriesStatement()
    {
        return $this->connection->createQueryBuilder()
                                ->select('argument')
                                ->from('mysql.general_log')
                                ->where("command_type = 'Query'")
                                ->execute();
    }

    /**
     * @param string $loggedQuery
     * @return string[]
     */
    private function extractTableNamesFromLoggedQuery($loggedQuery)
    {
        $usedTableNames = [];

        $parser = new PHPSQLParser();
        $parsedQuery = $parser->parse($loggedQuery);

        if ($parsedQuery === false) {
            return [];
        }

        if (!array_key_exists('FROM', $parsedQuery)) {
            return [];
        }

        foreach ($parsedQuery['FROM'] as $fromDescription) {
            if ($fromDescription['expr_type'] === 'table') {
                $usedTableNames[] = $fromDescription['table'];
            }
        }

        return $usedTableNames;
    }
}

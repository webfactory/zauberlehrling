<?php

namespace AppBundle\ShowUnusedMySQLTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Table;
use Helper\NullStyle;
use PHPSQLParser\PHPSQLParser;
use Symfony\Component\Console\Style\StyleInterface;

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

    /** @var StyleInterface */
    private $ioStyle;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * @param StyleInterface|null $ioStyle
     */
    public function getUnusedTableNames(StyleInterface $ioStyle = null)
    {
        $this->ioStyle = $ioStyle ?: new NullStyle();
        $this->ioStyle->text('Started.');

        $unusedTableNames = array_diff($this->getAllTablesNames(), $this->getUsedTableNames());

        $this->ioStyle->newLine();
        $this->ioStyle->text('Calculated ' . count($unusedTableNames) . ' potentially unused tables:');
        $this->ioStyle->listing($unusedTableNames);
        $this->ioStyle->success('Finished listing potentially unused tables.');
    }

    /**
     * @return string[]
     */
    private function getAllTablesNames()
    {
        $tables = $this->connection->getSchemaManager()->listTables();
        $tableNames = array_map(
            function (Table $table) {
                return $table->getName();
            },
            $tables
        );

        $this->ioStyle->text('Found ' . count($tableNames) . ' tables in the database "' . $this->connection->getDatabase() . '".');

        return $tableNames;
    }

    /**
     * @return string[]
     */
    private function getUsedTableNames()
    {
        $stmt = $this->getLoggedQueriesStatement();
        $numberOfLoggedQueries = $stmt->rowCount();
        $this->ioStyle->text('Analyzing ' . $numberOfLoggedQueries . ' logged queries (among all databases):');

        $this->ioStyle->progressStart($numberOfLoggedQueries);

        $usedTableNames = [];
        while ($loggedQuery = $stmt->fetch(\PDO::FETCH_COLUMN)) {
            $usedTableNames = array_merge($usedTableNames, $this->extractTableNamesFromLoggedQuery($loggedQuery));
            $this->ioStyle->progressAdvance();
        }

        $usedTableNames = array_unique($usedTableNames);

        $this->ioStyle->newLine();
        $this->ioStyle->text('Found ' . count($usedTableNames) . ' used tables (among all databases).');

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

<?php

namespace SqlAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QueryExecutor
{
    public function __construct(
        protected ?string $connection,
        protected int $maxRows,
        protected array $allowedStatements,
        protected bool $onlyRetriveDataCommand,
        protected array $retrieveDataCommands
    ) {}

    /**
     * Execute an SQL query and return the results with timing information.
     *
     * @param  string  $sql
     * @return array{data: array, responseTime: float}
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $sql): array
    {
        $sql = trim($sql);

        if (empty($sql)) {
            throw new InvalidArgumentException('SQL query cannot be empty.');
        }

        $this->validateStatement($sql);

        $db = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        $start = microtime(true);
        $results = $db->select($sql);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $data = array_slice(
            array_map(fn ($row) => (array) $row, $results),
            0,
            $this->maxRows
        );

        return [
            'data' => $data,
            'responseTime' => $elapsed,
        ];
    }

    /**
     * Retrieve all table names from the configured database only.
     */
    public function getTables(): array
    {
        $db = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        $database = $db->getDatabaseName();
        $driver = $db->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => array_map(
                fn ($row) => $row->{'Tables_in_' . $database},
                $db->select('SHOW TABLES')
            ),
            'pgsql' => array_column(
                array_map(fn ($r) => (array) $r, $db->select(
                    "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
                )),
                'tablename'
            ),
            'sqlite' => array_column(
                array_map(fn ($r) => (array) $r, $db->select(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
                )),
                'name'
            ),
            default => $db->getSchemaBuilder()->getTableListing(),
        };
    }

    /**
     * Retrieve column names for a given table.
     */
    public function getColumns(string $table): array
    {
        $db = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        $driver = $db->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => array_column(
                array_map(fn ($r) => (array) $r, $db->select("SHOW COLUMNS FROM `{$table}`")),
                'Field'
            ),
            'pgsql' => array_column(
                array_map(fn ($r) => (array) $r, $db->select(
                    "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?",
                    [$table]
                )),
                'column_name'
            ),
            'sqlite' => array_column(
                array_map(fn ($r) => (array) $r, $db->select("PRAGMA table_info(`{$table}`)")),
                'name'
            ),
            default => $db->getSchemaBuilder()->getColumnListing($table),
        };
    }

    /**
     * Validate the SQL statement against allowed types.
     */
    protected function validateStatement(string $sql): void
    {
        $firstWord = strtolower(strtok($sql, " \t\n\r"));

        if ($this->onlyRetriveDataCommand) {
            if (! in_array($firstWord, $this->retrieveDataCommands, true)) {
                throw new InvalidArgumentException(
                    "Only retrieve-data commands are allowed. Allowed: " . implode(', ', $this->retrieveDataCommands)
                );
            }

            return;
        }

        if (in_array('*', $this->allowedStatements, true)) {
            return;
        }

        if (! in_array($firstWord, $this->allowedStatements, true)) {
            throw new InvalidArgumentException(
                "Statement type \"{$firstWord}\" is not allowed. Allowed: " . implode(', ', $this->allowedStatements)
            );
        }
    }
}

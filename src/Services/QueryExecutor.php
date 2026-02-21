<?php

namespace SqlAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QueryExecutor
{
    public function __construct(
        protected ?string $connection,
        protected int $maxRows,
        protected array $allowedStatements
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
     * Retrieve all table names from the current database connection.
     */
    public function getTables(): array
    {
        $db = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        $schemaBuilder = $db->getSchemaBuilder();

        return $schemaBuilder->getTableListing();
    }

    /**
     * Retrieve column names for a given table.
     */
    public function getColumns(string $table): array
    {
        $db = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        $schemaBuilder = $db->getSchemaBuilder();

        return $schemaBuilder->getColumnListing($table);
    }

    /**
     * Validate the SQL statement against allowed types.
     */
    protected function validateStatement(string $sql): void
    {
        if (in_array('*', $this->allowedStatements, true)) {
            return;
        }

        $firstWord = strtolower(strtok($sql, " \t\n\r"));

        if (! in_array($firstWord, $this->allowedStatements, true)) {
            throw new InvalidArgumentException(
                "Statement type \"{$firstWord}\" is not allowed. Allowed: " . implode(', ', $this->allowedStatements)
            );
        }
    }
}

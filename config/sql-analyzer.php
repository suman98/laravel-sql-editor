<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for all SQL Analyzer routes.
    |
    */
    'prefix' => 'sql-analyzer',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the SQL Analyzer routes. You should protect
    | this with authentication/authorization in production.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use when executing queries.
    | Set to null to use the default connection.
    |
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Max Rows
    |--------------------------------------------------------------------------
    |
    | Maximum number of rows to return from a query.
    |
    */
    'max_rows' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Only Retrieve Data Command
    |--------------------------------------------------------------------------
    |
    | When true, only retrieve-data commands are allowed. Any non-retrieve
    | command will be blocked before execution.
    |
    */
    'only_retrive_data_command' => true,

    /*
    |--------------------------------------------------------------------------
    | Retrieve Data Commands
    |--------------------------------------------------------------------------
    |
    | Statement types treated as read-only data retrieval commands.
    |
    */
    'retrieve_data_commands' => ['select', 'show', 'describe', 'desc', 'with', 'explain'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Statements
    |--------------------------------------------------------------------------
    |
    | SQL statement types that are allowed to be executed.
    | Set to ['*'] to allow all statements (dangerous in production).
    |
    */
    'allowed_statements' => ['select'],

];

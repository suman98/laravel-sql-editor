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
    | Allowed Statements
    |--------------------------------------------------------------------------
    |
    | SQL statement types that are allowed to be executed.
    | Set to ['*'] to allow all statements (dangerous in production).
    |
    */
    'allowed_statements' => ['select'],

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root URL
    |--------------------------------------------------------------------------
    |
    | Force a root URL for all generated package URLs.
    | Leave null to use the application's default URL.
    |
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Optional override for the OpenAI API key used by the Python agent.
    | Falls back to OPENAI_API_KEY when not set.
    |
    */
    'openai_api_key' => env('SQL_ANALYZER_OPENAI_API_KEY', env('OPENAI_API_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for all SQL Analyzer routes.
    |
    */
    'prefix' => env('SQL_ANALYZER_ROOT_URL', null),,

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

Create a Laravel package for an SQL editor and visualizer.

Create a UI that allows users to enter SQL queries, with autocomplete support for SQL syntax and table names.

When a user runs a query, the response should be returned as JSON in the following format:

{
    "data": [
        {
            "col1": "value",
            "col2": "value",
            "col3": "value",
            // ...
            "coln": "value"
        },
        // ... more rows
    ],
    "responseTime": <numeric>
}

Display the returned data in a paginated data table.

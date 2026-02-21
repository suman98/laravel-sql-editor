# SQL Analyzer Package

A Laravel package that provides a SQL editor UI with query execution, saved queries, and optional AI-assisted SQL generation.

## Requirements

- PHP 8.2+
- Laravel 12+
- Python 3.8+ (for AI-assisted query generation)

## Installation

1) Require the package (local path or VCS):

```bash
composer require suman/laravel-sql-editor
```

2) Publish package assets:

```bash
php artisan vendor:publish --tag=sql-analyzer-config
php artisan vendor:publish --tag=sql-analyzer-views
php artisan vendor:publish --tag=sql-analyzer-migrations
```

3) Run migrations:

```bash
php artisan migrate
```

4) (Optional) Install the Python environment for AI generation:

```bash
php artisan sql-analyze:install
```

## Configuration

The config file is published to `config/sql-analyzer.php`.

- `prefix`: Route prefix for the UI (default: `sql-analyzer`)
- `middleware`: Middleware for routes (default: `web`)
- `connection`: Database connection name (default: null)
- `max_rows`: Max rows returned for queries (default: 1000)
- `allowed_statements`: Allowed SQL statements (default: `select`)
- `root_url`: Optional root URL override for the package
- `openai_api_key`: Optional OpenAI API key override

Environment options:

```env
SQL_ANALYZER_ROOT_URL=
SQL_ANALYZER_OPENAI_API_KEY=
OPENAI_API_KEY=
```

For Python database access, set your DB settings in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

## Usage

Open the UI in your browser:

```
/{prefix}
```

Example (default prefix):

```
/sql-analyzer
```

## AI Query Generation

If you enable prompted query in the UI, the package will use the Python agent to generate SQL. Make sure:

- Python venv is installed via `php artisan sql-analyze:install`
- `OPENAI_API_KEY` or `SQL_ANALYZER_OPENAI_API_KEY` is set
- The database credentials are configured in `.env`

## Troubleshooting

- If the Python script cannot be found, verify the package is installed correctly and rerun `php artisan sql-analyze:install`.
- If you see context length errors, reduce the number of selected tables.

## License

MIT

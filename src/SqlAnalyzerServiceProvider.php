<?php

namespace SqlAnalyzer;

use Illuminate\Support\ServiceProvider;

class SqlAnalyzerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sql-analyzer');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/sql-analyzer.php' => config_path('sql-analyzer.php'),
            ], 'sql-analyzer-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/sql-analyzer'),
            ], 'sql-analyzer-views');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sql-analyzer.php',
            'sql-analyzer'
        );

        $this->app->singleton(Services\QueryExecutor::class, function ($app) {
            return new Services\QueryExecutor(
                config('sql-analyzer.connection'),
                config('sql-analyzer.max_rows', 1000),
                config('sql-analyzer.allowed_statements', ['select'])
            );
        });
    }
}

<?php

use Illuminate\Support\Facades\Route;
use SqlAnalyzer\Http\Controllers\SqlAnalyzerController;

Route::group([
    'prefix' => config('sql-analyzer.prefix', 'sql-analyzer'),
    'middleware' => config('sql-analyzer.middleware', ['web']),
], function () {
    Route::get('/', [SqlAnalyzerController::class, 'index'])->name('sql-analyzer.index');
    Route::post('/execute', [SqlAnalyzerController::class, 'execute'])->name('sql-analyzer.execute');
    Route::post('/test-connection', [SqlAnalyzerController::class, 'testConnection'])->name('sql-analyzer.test-connection');
    Route::match(['get', 'post'], '/schema', [SqlAnalyzerController::class, 'schema'])->name('sql-analyzer.schema');
    Route::get('/tables', [SqlAnalyzerController::class, 'getAvailableTables'])->name('sql-analyzer.tables');
    Route::post('/generate-query', [SqlAnalyzerController::class, 'generateQueryFromTitle'])->name('sql-analyzer.generate-query');
    Route::post('/save-active-connection', [SqlAnalyzerController::class, 'saveActiveConnection'])->name('sql-analyzer.save-active-connection');
    Route::get('/get-active-connection', [SqlAnalyzerController::class, 'getActiveConnection'])->name('sql-analyzer.get-active-connection');
    Route::get('/saved-queries', [SqlAnalyzerController::class, 'savedQueries'])->name('sql-analyzer.saved-queries.index');
    Route::post('/saved-queries', [SqlAnalyzerController::class, 'saveQuery'])->name('sql-analyzer.saved-queries.store');
    Route::get('/saved-queries/{id}', [SqlAnalyzerController::class, 'getSavedQuery'])->name('sql-analyzer.saved-queries.show');
    Route::delete('/saved-queries/{id}', [SqlAnalyzerController::class, 'deleteSavedQuery'])->name('sql-analyzer.saved-queries.destroy');
});

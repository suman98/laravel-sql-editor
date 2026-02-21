<?php

use Illuminate\Support\Facades\Route;
use SqlAnalyzer\Http\Controllers\SqlAnalyzerController;

Route::group([
    'prefix' => config('sql-analyzer.prefix', 'sql-analyzer'),
    'middleware' => config('sql-analyzer.middleware', ['web']),
], function () {
    Route::get('/', [SqlAnalyzerController::class, 'index'])->name('sql-analyzer.index');
    Route::post('/execute', [SqlAnalyzerController::class, 'execute'])->name('sql-analyzer.execute');
    Route::get('/schema', [SqlAnalyzerController::class, 'schema'])->name('sql-analyzer.schema');
});

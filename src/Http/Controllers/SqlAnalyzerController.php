<?php

namespace SqlAnalyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SqlAnalyzer\Services\QueryExecutor;

class SqlAnalyzerController extends Controller
{
    public function __construct(
        protected QueryExecutor $executor
    ) {}

    /**
     * Show the SQL editor UI.
     */
    public function index()
    {
        return view('sql-analyzer::editor');
    }

    /**
     * Execute an SQL query and return JSON results.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string|max:10000',
        ]);

        try {
            $result = $this->executor->execute($request->input('sql'));

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return schema metadata for autocomplete (tables and their columns).
     */
    public function schema(): JsonResponse
    {
        try {
            $tables = $this->executor->getTables();
            $schema = [];

            foreach ($tables as $table) {
                $schema[$table] = $this->executor->getColumns($table);
            }

            return response()->json(['schema' => $schema]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}

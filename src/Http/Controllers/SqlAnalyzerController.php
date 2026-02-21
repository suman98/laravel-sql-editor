<?php

namespace SqlAnalyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SqlAnalyzer\Models\SavedQuery;
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

    /**
     * List all saved queries (id + name) ordered by latest update.
     */
    public function savedQueries(): JsonResponse
    {
        $queries = SavedQuery::query()
            ->select(['id', 'name', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => $queries,
        ]);
    }

    /**
     * Save or update a query by name.
     */
    public function saveQuery(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'sql' => 'required|string|max:50000',
        ]);

        $query = SavedQuery::query()->updateOrCreate(
            ['name' => $payload['name']],
            ['sql' => $payload['sql']]
        );

        return response()->json([
            'data' => [
                'id' => $query->id,
                'name' => $query->name,
                'sql' => $query->sql,
            ],
        ]);
    }

    /**
     * Load one saved query by id.
     */
    public function getSavedQuery(int $id): JsonResponse
    {
        $query = SavedQuery::query()->findOrFail($id, ['id', 'name', 'sql']);

        return response()->json([
            'data' => $query,
        ]);
    }
}

<?php

namespace SqlAnalyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SqlAnalyzer\Models\SavedQuery;
use SqlAnalyzer\Services\PythonEnvironment;
use SqlAnalyzer\Services\QueryExecutor;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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

    /**
     * Generate SQL query from natural language title using LangChain AI.
     */
    public function generateQueryFromTitle(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:1000',
        ]);

        try {
            $title = $request->input('title');
            
            // Resolve package path (not app path)
            // __FILE__ = src/Http/Controllers/SqlAnalyzerController.php
            // dirname(__FILE__, 3) = package root
            $packageRoot = dirname(__FILE__, levels: 4);
            $pythonScriptPath = $packageRoot . '/python/get_sql_response.py';

            if (!file_exists($pythonScriptPath)) {
                return response()->json([
                    'error' => 'Python script not found.',
                ], 422);
            }

            $pythonExecutable = PythonEnvironment::getPythonExecutable();
        
            $payload = json_encode([
                'user_question' => $title,
                'prompt_template' => null,
            ]);

            $process = new Process([
                $pythonExecutable,
                $pythonScriptPath,
                $payload,
            ]);

            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());

            return response()->json([
                'data' => [
                    'sql' => $output,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to generate query: ' . $e->getMessage(),
            ], 422);
        }
    }
}

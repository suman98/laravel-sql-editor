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
            $autocompleteSchema = [];

            foreach ($tables as $table) {
                $columns = $this->executor->getColumns($table);
                $schema[$table] = $columns;
                
                // For autocomplete, we need just column names
                $autocompleteSchema[$table] = array_column($columns, 'name');
            }

            return response()->json(['schema' => $schema, 'autocomplete' => $autocompleteSchema]);
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
     * Delete a saved query by id.
     */
    public function deleteSavedQuery(int $id): JsonResponse
    {
        $query = SavedQuery::query()->findOrFail($id);
        $query->delete();

        return response()->json([
            'message' => 'Query deleted successfully',
        ]);
    }

    /**
     * Get list of all available tables in the database.
     * Used to populate multi-select dropdown for table selection.
     */
    public function getAvailableTables(): JsonResponse
    {
        try {
            // Resolve package path (not app path)
            $packageRoot = dirname(__FILE__, levels: 4);
            $pythonScriptPath = $packageRoot . '/python/get_sql_response.py';

            if (!file_exists($pythonScriptPath)) {
                return response()->json([
                    'error' => 'Python script not found.',
                ], 422);
            }

            $pythonExecutable = PythonEnvironment::getPythonExecutable();
            $processEnv = $this->buildPythonEnv();
        
            $payload = json_encode([
                'action' => 'get_tables',
            ]);

            $process = new Process(
                [$pythonExecutable, $pythonScriptPath, $payload],
                null,
                $processEnv
            );

            $process->setTimeout(15);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());
            $result = json_decode($output, true);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'] ?? 'Failed to retrieve tables',
                ], 422);
            }

            return response()->json([
                'data' => [
                    'tables' => $result['tables'],
                    'count' => $result['count'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve tables: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate SQL query from natural language title using LangChain AI.
     * Supports optional table selection for restricted queries.
     */
    public function generateQueryFromTitle(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:1000',
            'selected_tables' => 'nullable|array',
            'selected_tables.*' => 'string',
        ]);

        try {
            $title = $request->input('title');
            $selectedTables = $request->input('selected_tables', null);
            
            // Resolve package path (not app path)
            // __FILE__ = src/Http/Controllers/SqlAnalyzerController.php
            // dirname(__FILE__, 4) = package root
            $packageRoot = dirname(__FILE__, levels: 4);
            $pythonScriptPath = $packageRoot . '/python/get_sql_response.py';

            if (!file_exists($pythonScriptPath)) {
                return response()->json([
                    'error' => 'Python script not found.',
                ], 422);
            }

            $pythonExecutable = PythonEnvironment::getPythonExecutable();
            $processEnv = $this->buildPythonEnv();
        
            $payload = [
                'user_question' => $title,
                'prompt_template' => null,
            ];

            // Add selected tables to payload if provided
            if (!empty($selectedTables)) {
                $payload['selected_tables'] = $selectedTables;
            }

            $processPayload = json_encode($payload);

            $process = new Process(
                [$pythonExecutable, $pythonScriptPath, $processPayload],
                null,
                $processEnv
            );

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

    private function buildPythonEnv(): array
    {
        $env = [];
        $openAiApiKey = config('sql-analyzer.openai_api_key');
        if (!empty($openAiApiKey)) {
            $env['OPENAI_API_KEY'] = $openAiApiKey;
        }

        return $env;
    }
}

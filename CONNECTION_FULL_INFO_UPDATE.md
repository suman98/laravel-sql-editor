# Connection Full Information Update

## Issue Fixed
Previously, when executing queries with a custom database connection, only the **database type** was sent to the backend (`?database=pgsql`). This meant the backend used default Laravel database configuration instead of the full connection details the user created (host, port, username, password, etc.).

**New behavior**: The **entire connection object** (with all credentials and parameters) is now sent to the backend and used for actual database connections.

---

## Changes Made

### 1. Frontend Changes (`resources/views/editor.blade.php`)

#### Added Helper Function
```javascript
function getConnectionForApi() {
    if (!selectedDatabaseConnection) return null;
    try {
        const conn = typeof selectedDatabaseConnection === 'string' 
            ? JSON.parse(selectedDatabaseConnection) 
            : selectedDatabaseConnection;
        return conn;
    } catch (e) {
        return null;
    }
}
```
This function extracts the full connection object (instead of just the type) for API calls.

#### Updated Query Execution
**Before:**
```javascript
const requestBody = { sql };
if (customMode && selectedDatabaseConnection) {
    const dbType = getDatabaseTypeForApi();
    if (dbType) {
        requestBody.database = dbType;  // Only type sent
    }
}
```

**After:**
```javascript
const requestBody = { sql };
if (customMode) {
    const connection = getConnectionForApi();
    if (connection) {
        requestBody.connection = connection;  // Full connection object sent
    }
}
```

#### Updated Schema Loading
**Before:**
```javascript
let url = "{{ route('sql-analyzer.schema') }}";
if (customMode && selectedDatabaseConnection) {
    const dbType = getDatabaseTypeForApi();
    if (dbType) {
        url += '?database=' + encodeURIComponent(dbType);  // Query param
    }
}
const response = await fetch(url, {
    headers: { 'Accept': 'application/json' }
});
```

**After:**
```javascript
if (customMode && selectedDatabaseConnection) {
    const connection = getConnectionForApi();
    if (connection) {
        const response = await fetch("{{ route('sql-analyzer.schema') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ connection })  // Full connection in body
        });
        // ...
    }
}
```

---

### 2. Backend Changes (`src/Http/Controllers/SqlAnalyzerController.php`)

#### Updated execute() Method Validation
```php
public function execute(Request $request): JsonResponse
{
    $request->validate([
        'sql' => 'required|string|max:10000',
        'connection' => 'nullable|array',
        'connection.name' => 'string|max:255',
        'connection.type' => 'string|in:mysql,pgsql,sqlite,sqlsrv,mariadb',
        'connection.host' => 'nullable|string|max:255',
        'connection.port' => 'nullable|integer|min:1|max:65535',
        'connection.database' => 'nullable|string',
        'connection.username' => 'nullable|string',
        'connection.password' => 'nullable|string',
    ]);

    try {
        $connection = $request->input('connection');
        $executor = $this->getExecutorForConnection($connection);  // NEW
        $result = $executor->execute($request->input('sql'));

        return response()->json($result);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

#### New Method: getExecutorForConnection()
```php
private function getExecutorForConnection(?array $connection = null): QueryExecutor
{
    if (!$connection || !config('sql-analyzer.custom_mode')) {
        return $this->executor;  // Use default if no connection provided
    }

    // Build database config from connection array
    $type = $connection['type'] ?? null;
    
    // For each database type, construct proper config...
    if ($type === 'mysql') {
        $dbConfig = [
            'driver' => $type,
            'host' => $connection['host'] ?? 'localhost',
            'port' => $connection['port'] ?? 3306,
            'database' => $connection['database'] ?? '',
            'username' => $connection['username'] ?? '',
            'password' => $connection['password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => 'InnoDB',
        ];
    }
    // ... other database types ...

    // Register connection in database config at runtime
    config(['database.connections.custom_dynamic' => $dbConfig]);

    // Create executor with the registered connection
    return new QueryExecutor(
        'custom_dynamic',
        config('sql-analyzer.max_rows', 1000),
        config('sql-analyzer.allowed_statements', ['select']),
        config('sql-analyzer.only_retrive_data_command', config('sql-analyzer.only_retrieve_data_command', true)),
        config('sql-analyzer.retrieve_data_commands', ['select', 'show', 'describe', 'desc', 'with', 'explain'])
    );
}
```

#### Updated schema() Method
```php
public function schema(Request $request): JsonResponse
{
    $request->validate([
        'database' => 'nullable|string|in:mysql,pgsql,sqlite,sqlsrv,mariadb',
        'connection' => 'nullable|array',
        'connection.name' => 'string|max:255',
        'connection.type' => 'string|in:mysql,pgsql,sqlite,sqlsrv,mariadb',
        'connection.host' => 'nullable|string|max:255',
        'connection.port' => 'nullable|integer|min:1|max:65535',
        'connection.database' => 'nullable|string',
        'connection.username' => 'nullable|string',
        'connection.password' => 'nullable|string',
    ]);

    try {
        $connection = $request->input('connection');
        $database = $request->query('database');
        
        // Use connection object if provided, otherwise fall back to database string
        if ($connection) {
            $executor = $this->getExecutorForConnection($connection);
        } else {
            $executor = $this->getExecutorForDatabase($database);
        }
        
        // ... rest of method ...
    }
}
```

---

### 3. Route Changes (`routes/web.php`)

#### Updated Schema Route
```php
// Before
Route::get('/schema', [SqlAnalyzerController::class, 'schema'])->name('sql-analyzer.schema');

// After  
Route::match(['get', 'post'], '/schema', [SqlAnalyzerController::class, 'schema'])->name('sql-analyzer.schema');
```

Now accepts both GET (for backward compatibility) and POST (for sending full connection info).

---

## What Data is Sent Now

### Query Execution Request
```json
{
  "sql": "SELECT * FROM users;",
  "connection": {
    "name": "Production DB",
    "type": "pgsql",
    "host": "db.example.com",
    "port": 5432,
    "database": "myapp",
    "username": "admin",
    "password": "secret123"
  }
}
```

### Schema Loading Request
```json
{
  "connection": {
    "name": "Production DB",
    "type": "pgsql",
    "host": "db.example.com",
    "port": 5432,
    "database": "myapp",
    "username": "admin",
    "password": "secret123"
  }
}
```

---

## How It Works

1. **User creates connection** in browser
   - Connection stored in localStorage with all details
   - User selects connection from dropdown

2. **User executes query**
   - `getConnectionForApi()` retrieves full connection object
   - Send entire connection object in POST request body

3. **Backend receives connection**
   - Validates connection array structure
   - `getExecutorForConnection()` builds database config
   - Registers dynamic connection in `database.connections.custom_dynamic`
   - QueryExecutor uses this dynamic connection

4. **Query executes on correct database**
   - Uses host, port, username, password from connection
   - Returns results from actual selected database

---

## Benefits

✅ **Correct database connection** - Uses actual credentials, not defaults  
✅ **Multiple database support** - Can query different databases with different connections  
✅ **Full credentials support** - Host, port, username, password all used  
✅ **Schema hints work** - Autocomplete loads from correct database  
✅ **Backward compatible** - Still works with default connection if no custom connection selected  

---

## Database Types Supported

All supported database types now receive full connection details:

- **MySQL/MariaDB**: host, port, username, password, database, charset
- **PostgreSQL**: host, port, username, password, database, schema, ssl mode
- **SQLite**: file path only (no host/credentials needed)
- **SQL Server**: host, port, username, password, database, encryption settings

---

## Testing

### Manual Testing Steps
1. Open SQL Analyzer
2. Click "Manage Connections"
3. Create connection with full details
4. Select connection from dropdown
5. Execute query → Should work with custom credentials
6. Check schema/autocomplete → Should load from custom database
7. Create another connection to different database
8. Execute same query → Results should be from NEW database

### Expected Behavior Changes
- Queries now execute on the selected custom connection database
- Schema hints now reflect correct database tables/columns
- No longer uses default Laravel connection configuration
- Results are specific to selected connection's database

---

## Troubleshooting

### Connection Details Not Being Used
- Verify connection is selected in dropdown (should show connection name)
- Check browser console for errors
- Verify custom_mode is enabled in config

### Query Runs but Wrong Results
- Verify you selected the correct connection
- Check connection credentials are correct
- Try "Test Connection" button to validate credentials

### Schema Hints Not Loading
- Similar to above - check connection selection
- Verify database name in connection is correct
- Check schema route accepts POST (updated in routes/web.php)

---

## Security Note

Passwords are sent in the request body. Ensure:
- HTTPS is used in production
- CSRF tokens are validated (✅ already in place)
- Server connection is secure
- Database credentials are not logged

---

## Summary

The system now properly uses **all connection information** when executing queries with custom database connections, instead of just the database type. This allows users to connect to multiple databases with different credentials and execute queries on the correct database.

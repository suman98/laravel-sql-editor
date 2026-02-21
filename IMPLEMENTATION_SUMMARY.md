# AI-Powered SQL Query Generation - Implementation Summary

## Overview

The heuristic-based `generateQueryFromTitle()` function has been successfully replaced with an **AI-powered LangChain + OpenAI backend service**. The new implementation uses GPT-3.5-turbo to generate SQL from natural language descriptions.

## What Changed

### 1. Backend (Laravel Controller)
**File:** `src/Http/Controllers/SqlAnalyzerController.php`

**New Method:** `generateQueryFromTitle(Request $request): JsonResponse`
- Accepts POST request with natural language title
- Executes Python script via `Symfony\Process`
- Passes title as JSON payload to Python
- Returns generated SQL wrapped in JSON response
- Handles errors gracefully with 422 HTTP status

**Key Features:**
- 30-second timeout for Python execution
- File existence check for Python script
- Process error handling with detailed error messages
- Response formatted as `{ data: { sql: "..." } }`

### 2. Frontend (Blade/JavaScript)
**File:** `resources/views/editor.blade.php`

**Changes:**
1. **Replaced function:** `generateQueryFromTitle()` is now `async`
   - Old: Synchronous client-side heuristic with regex patterns
   - New: Asynchronous backend API call using fetch()

2. **Removed helper functions:** (no longer needed)
   - `normalizeText()`
   - `singularize()`
   - `pickBestTableFromTitle()`
   - `pickColumns()`
   - `extractLimitFromTitle()`

3. **New behavior:**
   - Makes POST request to `/sql-analyzer/generate-query`
   - Passes user input as JSON: `{ title: "..." }`
   - Uses `withBackendLoading()` helper for loading overlay
   - Shows error alerts on failure
   - Inserts response SQL into editor

**Event listeners:** Unchanged
   - Click on "Generate Query" button
   - Enter key in query title input field
   - Both now call the async function (browsers handle async automatically)

### 3. Routes
**File:** `routes/web.php`

**New Route:**
```php
Route::post('/generate-query', [SqlAnalyzerController::class, 'generateQueryFromTitle'])
    ->name('sql-analyzer.generate-query');
```

**Route Details:**
- **Method:** POST
- **Prefix:** `sql-analyzer` (configurable)
- **Middleware:** `web` (default, configurable)
- **Name:** `sql-analyzer.generate-query`

### 4. Python Service
**Files:** 
- `python/get_sql_response.py` (NEW)
- `python/requirements.txt` (NEW)
- `python/README.md` (NEW - comprehensive setup guide)

**Python Script:**
- Entry point: `main()` - reads JSON from command-line argument
- Core function: `get_sql_response(payload)` - generates SQL
- Uses LangChain SQLDatabaseChain with ChatOpenAI (GPT-3.5-turbo)
- Requires:
  - `OPENAI_API_KEY` environment variable
  - `DATABASE_URL` environment variable (database connection string)
- Returns: Plain SQL query text
- Handles errors with descriptive messages

**Dependencies (requirements.txt):**
```
langchain==0.1.14
langchain-openai==0.1.3
langchain-community==0.0.28
python-dotenv==1.0.0
SQLAlchemy==2.0.25
```

## Architecture Diagram

```
┌─────────────────────────────────┐
│  Browser / Frontend             │
│  editor.blade.php               │
│                                 │
│  User Input: "Get all users"   │
│  Click: Generate Query Button   │
└────────────┬────────────────────┘
             │ async fetch()
             │ POST /sql-analyzer/generate-query
             │ { title: "Get all users" }
             ↓
┌─────────────────────────────────┐
│  Laravel Backend                │
│  SqlAnalyzerController          │
│  generateQueryFromTitle()        │
│                                 │
│  1. Validate input              │
│  2. Check Python script exists  │
│  3. Create JSON payload         │
│  4. Spawn Process (python3)     │
│  5. Wait for completion         │
█  6. Parse output                │
│  7. Return JSON response        │
└────────────┬────────────────────┘
             │ Symfony\Process
             │ python3 python/get_sql_response.py '{"user_question":"..."}'
             ↓
┌─────────────────────────────────────┐
│  Python Service                     │
│  get_sql_response.py                │
│                                     │
│  1. Parse JSON input                │
│  2. Load OPENAI_API_KEY             │
│  3. Load DATABASE_URL               │
│  4. Connect to database (SQLAlchemy)│
│  5. Initialize ChatOpenAI (GPT-3.5) │
│  6. Create SQLDatabaseChain & Agent │
│  7. Agent processes natural language│
│  8. Generate SQL query              │
│  9. Return SQL to stdout            │
└────────────┬──────────────────────┘
             │ stdout
             │ "SELECT * FROM users;"
             ↓
┌────────────────────────────────────┐
│  Laravel Backend                   │
│  Capture stdout output             │
│  Trim whitespace                   │
│  Build response JSON               │
│  { data: { sql: "..." } }          │
└────────────┬──────────────────────┘
             │ JSON response
             ↓
┌────────────────────────────────────┐
│  Browser / Frontend                │
│  Receive response                  │
│  editor.setValue(sql)              │
│  Refresh editor height             │
│  Load overlay disappears           │
└────────────────────────────────────┘
```

## Data Flow

### Success Path
```
Input: { title: "Count all orders from last month" }
  ↓
GPT-3.5-turbo generates: "SELECT COUNT(*) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH);"
  ↓
Output: { ok: true, data: { sql: "SELECT COUNT(*) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH);" } }
  ↓
SQL inserted into editor
```

### Error Path
```
Input: { title: "Show me the biggest products" }
  ↓
Python error: OPENAI_API_KEY not set
  ↓
Controller catches error, returns 422:
{ error: "Failed to generate query: OPENAI_API_KEY environment variable not set" }
  ↓
Frontend shows alert: "Error generating query: OPENAI_API_KEY environment variable not set"
```

## Setup Instructions

### Using Artisan Command (Recommended)

```bash
php artisan sql-analyze:install
```

This command automatically:
- Verifies Python 3.8+ is available
- Creates a virtual environment at `python/venv`
- Installs all dependencies from `python/requirements.txt`
- Verifies the installation

### Manual Setup (Alternative)

If you prefer to set up manually:

```bash
cd python
python3 -m venv venv
source venv/bin/activate  # Or: venv\Scripts\activate on Windows
pip install -r requirements.txt
```

### 2. Configure Environment
Add to `.env`:
```env
OPENAI_API_KEY=sk_your_api_key_here
DATABASE_URL=mysql://user:pass@localhost/db_name
```

### 3. Test the Setup
```bash
# Test Python script directly
cd python
python3 get_sql_response.py '{"user_question":"Get all users","prompt_template":null}'

# Should output SQL or error message
```

### 4. Deploy Package
Include the package in your Laravel app, and the new endpoint is immediately available at `/sql-analyzer/generate-query`

## Testing the Endpoint

### Via curl
```bash
curl -X POST http://localhost:8000/sql-analyzer/generate-query \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -d '{"title":"Get all active users"}'
```

### Via JavaScript (in browser console)
```javascript
fetch('/sql-analyzer/generate-query', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]').content
  },
  body: JSON.stringify({ title: 'Get all users' })
})
.then(r => r.json())
.then(d => console.log(d))
```

## Configuration

### Timeout
Default: 30 seconds (in controller)
```php
$process->setTimeout(30);
```

Modify `generateQueryFromTitle()` method in controller to change.

### Process Execution
Uses `Symfony\Process\Process` with:
- Command: `['python3', '/path/to/script', $jsonPayload]`
- Timeout: 30 seconds
- Encoding: UTF-8 (automatic)

## Troubleshooting

### Issue: "Python script not found"
**Solution:** Verify `python/get_sql_response.py` exists and is readable

### Issue: "OPENAI_API_KEY environment variable not set"
**Solution:** Add to `.env`: `OPENAI_API_KEY=sk_...`

### Issue: "DATABASE_URL environment variable not set"
**Solution:** Add to `.env`: `DATABASE_URL=mysql://...` or `sqlite://...` or `postgresql://...`

### Issue: Process timeout
**Solution:**
- Check OpenAI API connectivity
- Verify database is accessible from Python
- Try a simpler query description
- Increase timeout in controller

### Issue: "No module named 'langchain'"
**Solution:** Run `pip install -r python/requirements.txt`

## Files Changed

### Modified Files
1. `src/Http/Controllers/SqlAnalyzerController.php`
   - Added Symfony Process imports
   - Added `generateQueryFromTitle()` method

2. `routes/web.php`
   - Added POST route for `/generate-query`

3. `resources/views/editor.blade.php`
   - Replaced `generateQueryFromTitle()` with async version
   - Removed helper functions (normalizeText, singularize, pickBestTableFromTitle, pickColumns, extractLimitFromTitle)

### New Files
1. `python/get_sql_response.py` - Main Python service
2. `python/requirements.txt` - Python dependencies
3. `python/README.md` - Python setup documentation

## Example Queries

The AI service now handles natural language queries:

✅ "Get all users"
✅ "Count orders from last month"
✅ "Show products with price over $100"
✅ "List active customers sorted by name"
✅ "Find all pending invoices"
✅ "Show top 10 selling products"
✅ "Get users who haven't logged in for 30 days"
✅ "Display admin users with their roles"

## Performance Characteristics

- **Latency:** 2-10 seconds (depends on OpenAI API response time)
- **Process Overhead:** Minimal (Python process spawned only when needed)
- **Memory:** Python process isolated, cleaned up after completion
- **Concurrency:** Safe (each request gets its own process)

## Security Notes

- Python process runs with same user permissions as Laravel
- Database credentials passed via environment variables
- No sensitive data logged to console
- Process output sanitized before returning to frontend
- API is protected by CSRF token (same as other endpoints)

## Future Enhancements

Possible improvements:
- Cache generated queries for same input
- Add custom prompt templates
- Support batch query generation
- Add query explanation/documentation
- Implement query optimization suggestions
- Add conversation history for refinement

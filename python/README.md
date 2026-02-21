# AI-Powered SQL Query Generation Setup

This guide explains how to set up and configure the AI-powered SQL query generation feature using LangChain and OpenAI.

## Prerequisites

- Laravel 12 with the SQL Analyzer package
- Python 3.8+ installed
- OpenAI API key
- Database configured (MySQL, PostgreSQL, or SQLite)

## Installation

### 1. Install Python Dependencies

```bash
cd python
pip install -r requirements.txt
```

### 2. Configure Environment Variables

Add these to your `.env` file:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk_your_api_key_here

# Database Configuration (used by Python script)
DATABASE_URL=mysql://root:password@localhost/database_name
# or for SQLite:
DATABASE_URL=sqlite:////absolute/path/to/database.sqlite
# or for PostgreSQL:
DATABASE_URL=postgresql://user:password@localhost/database_name
```

## How It Works

### Architecture

1. **Frontend (Blade/JavaScript)**
   - User enters a natural language description in the "Query Title" input field
   - Clicks "Generate Query" button
   - Makes an API call to the backend `/generate-query` endpoint

2. **Backend (Laravel Controller)**
   - Receives the natural language input
   - Invokes the Python script via `Symfony\Process`
   - Passes the input as a JSON payload
   - Returns the generated SQL to the frontend

3. **Python Service**
   - Uses LangChain with ChatOpenAI (GPT-3.5-turbo)
   - SQLDatabaseChain for schema-aware SQL generation
   - Connects to the database specified in `DATABASE_URL`
   - Returns plain SQL query text

### Data Flow

```
User Input (Title)
    ↓
JavaScript fetch() to /sql-analyzer/generate-query (POST)
    ↓
SqlAnalyzerController::generateQueryFromTitle()
    ↓
Symfony\Process executes python/get_sql_response.py
    ↓
LangChain + GPT-3.5-turbo generates SQL
    ↓
Python script returns SQL
    ↓
Controller returns JSON response: { data: { sql: "SELECT..." } }
    ↓
JavaScript inserts SQL into CodeMirror editor
```

## API Endpoint

**Endpoint:** `POST /sql-analyzer/generate-query`

**Request Body:**
```json
{
  "title": "Get all active users"
}
```

**Success Response (200 OK):**
```json
{
  "data": {
    "sql": "SELECT * FROM users WHERE status = 'active';"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "error": "Failed to generate query: [error message]"
}
```

## Troubleshooting

### Python Script Not Found

**Error:** "Python script not found."

**Solution:** Ensure the `python/get_sql_response.py` file exists at the root of the SQL Analyzer package.

### OpenAI API Key Error

**Error:** "OPENAI_API_KEY environment variable not set"

**Solution:** Add your OpenAI API key to the `.env` file:
```env
OPENAI_API_KEY=sk_your_key_here
```

### Database Connection Error

**Error:** "DATABASE_URL environment variable not set"

**Solution:** Add your database configuration to the `.env` file. Examples:
```env
# MySQL
DATABASE_URL=mysql://root:password@localhost/database_name

# SQLite
DATABASE_URL=sqlite:////path/to/database.sqlite

# PostgreSQL
DATABASE_URL=postgresql://user:password@localhost/database_name
```

### Process Timeout

**Error:** "Failed to generate query: ..." (Process timeout)

**Cause:** The Python script is taking too long to execute (timeout is 30 seconds)

**Solutions:**
- Check your OpenAI API connectivity
- Verify the database is accessible from Python
- Try a simpler query description
- Increase the timeout in the controller (edit `$process->setTimeout(30)`)

### LangChain Agent Errors

**Cause:** LangChain may fail to understand the query or generate SQL

**Solutions:**
- Use clearer, more direct query descriptions
- Include specific table/column names when possible
- Examples of good descriptions:
  - "Get all users"
  - "Count orders from last month"
  - "Show products with price over 100"

## Testing Locally

You can test the Python script independently:

```bash
cd python

# Load environment
export OPENAI_API_KEY=sk_your_key_here
export DATABASE_URL=sqlite:////path/to/database.sqlite

# Test the script
python3 get_sql_response.py '{"user_question": "Get all users", "prompt_template": null}'
```

## Frontend Integration

The frontend automatically uses the new AI-powered endpoint when the "Generate Query" button is clicked or when Enter is pressed in the query title input field.

**Event Listeners:**
- Click event on "Generate Query" button
- Enter key in the "Query Title" input field

**Behavior:**
- Shows a loading overlay while generating
- Inserts the generated SQL into the CodeMirror editor
- Shows error alerts if generation fails
- Auto-expands the editor height to fit the generated SQL

## Notes

- The Python script requires Python 3.8+ and pip
- LangChain version should match the requirements.txt version for compatibility
- The generated SQL is inserted as-is; users can still edit it before running
- All database schema information comes from the database itself (LangChain introspects it)

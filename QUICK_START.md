# Quick Start Guide - AI Query Generator

## 🎯 What's New?

The "Generate Query" button now uses **AI-powered query generation** instead of simple keyword matching. It uses OpenAI's GPT-3.5-turbo to understand natural language and generate SQL.

**Before:** "Get all users" → Simple regex-based heuristic
**Now:** "Get all users" → GPT-3.5-turbo → Smart SQL generation

## 🚀 5-Minute Setup

### Step 1: Install Dependencies (1 minute)

```bash
php artisan sql-analyze:install
```

This command will:
- Check for Python 3.8+ on your system
- Create a virtual environment at `python/venv`
- Install all Python dependencies from `python/requirements.txt`

**Note:** If you see an error about Python not being found, install Python 3.8+ from https://www.python.org/

### Step 2: Configure Environment (2 minutes)

Add to your `.env` file:

```env
OPENAI_API_KEY=sk_your_openai_api_key_here
DATABASE_URL=mysql://username:password@localhost/database_name
```

**Getting an OpenAI API Key:**
1. Go to https://platform.openai.com/api-keys
2. Create a new API key
3. Copy it to your `.env` file

**Database URL Format:**
- MySQL: `mysql://user:password@localhost/dbname`
- SQLite: `sqlite:////absolute/path/to/db.sqlite`
- PostgreSQL: `postgresql://user:password@localhost/dbname`

### Step 3: Verify It Works (2 minutes)

Test the Python script directly:

```bash
cd python

# Set environment variables
export OPENAI_API_KEY=sk_...
export DATABASE_URL=mysql://...

# Run a test
python3 get_sql_response.py '{"user_question":"Get all users","prompt_template":null}'

# You should see SQL output like:
# SELECT * FROM users;
```

## ✅ Testing in Browser

1. Open http://localhost:8000/sql-analyzer
2. Enter a query title: "Get all active users"
3. Click "Generate Query" button
4. Watch the loading overlay
5. See the generated SQL appear in the editor

## 💡 Example Queries to Try

- "Get all users"
- "Count orders from last month"
- "Show active products with price over 100"
- "List customers sorted by name"
- "Find pending invoices"
- "Get top 10 selling items"
- "Show users who haven't logged in for 30 days"
- "Display admin users with their roles"

## 🔧 Troubleshooting

### Error: "Python script not found"
→ Verify `python/get_sql_response.py` exists

### Error: "OPENAI_API_KEY environment variable not set"
→ Add `OPENAI_API_KEY=sk_...` to `.env`

### Error: "DATABASE_URL environment variable not set"
→ Add `DATABASE_URL=mysql://... or similar` to `.env`

### Error: "No module named 'langchain'"
→ Run: `pip install -r python/requirements.txt`

### Timeout Error
→ OpenAI is taking too long. Check your network and try again.

## 📁 What Changed?

**3 files modified:**
- `src/Http/Controllers/SqlAnalyzerController.php` (added new method)
- `routes/web.php` (added new route)
- `resources/views/editor.blade.php` (replaced function)

**3 new files:**
- `python/get_sql_response.py` (AI service)
- `python/requirements.txt` (dependencies)
- `python/README.md` (detailed setup)

## 🏗️ How It Works

```
You type: "Get all active users"
         ↓
Click: "Generate Query"
         ↓
Frontend sends to: POST /sql-analyzer/generate-query
         ↓
Backend runs: python3 python/get_sql_response.py
         ↓
Python uses: OpenAI GPT-3.5-turbo
         ↓
GPT generates: SELECT * FROM users WHERE status = 'active';
         ↓
Result inserted into editor
```

## 📊 Performance

- **Generation Time:** 2-10 seconds (depends on OpenAI)
- **Process:** One Python process spawned per request
- **Memory:** Minimal, cleaned up after completion
- **Concurrent:** Safe, each request is isolated

## 🔐 Security

- Database credentials in `.env` (not in code)
- OpenAI API key in `.env` (not in code)
- CSRF protected (like all package endpoints)
- Process runs with Laravel user permissions

## 📚 More Info

- **Full docs:** See `IMPLEMENTATION_SUMMARY.md`
- **Python guide:** See `python/README.md`
- **Verification:** See `VERIFICATION_CHECKLIST.md`

## 🆘 Still Having Issues?

1. Check that `.env` has both variables set
2. Test Python script directly: `python3 python/get_sql_response.py '{"user_question":"test"}'`
3. Check that Python 3.8+ is installed: `python3 --version`
4. Check that OpenAI API key is valid by testing in Python:
   ```python
   from langchain_openai import ChatOpenAI
   llm = ChatOpenAI(api_key="your_key", model="gpt-3.5-turbo")
   print(llm.invoke("Hello"))
   ```

## 🎉 Done!

Your SQL Analyzer now has AI-powered query generation. Enjoy! 

→ Next: Open http://localhost:8000/sql-analyzer and try it out!

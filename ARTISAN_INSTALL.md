# SQL Analyzer Install Command Documentation

## Overview

The `sql-analyze:install` Artisan command automates the setup of the Python environment required for AI-powered query generation.

## Command

```bash
php artisan sql-analyze:install
```

## What It Does

The install command performs the following steps:

1. **Locates Python** - Finds a compatible Python 3.8+ interpreter on your system
2. **Creates Virtual Environment** - Sets up an isolated Python environment at `python/venv`
3. **Installs Dependencies** - Installs all required packages from `python/requirements.txt`
4. **Verifies Installation** - Checks that all packages are correctly installed
5. **Displays Configuration Guide** - Shows required environment variables

## Output

You'll see output similar to:

```
Setting up SQL Analyzer Python environment...
✓ Found requirements.txt
Creating Python virtual environment...
✓ Virtual environment created at /path/to/python/venv
✓ Dependencies installed successfully
✓ Verified: All required Python packages available

✅ SQL Analyzer Python environment setup complete!

Environment ready for AI-powered query generation.

Make sure your .env file contains:
  OPENAI_API_KEY=your_api_key_here
  DATABASE_URL=your_database_url_here
```

## Exit Codes

- **0** - Setup succeeded
- **1** - Setup failed (see error message)

## Troubleshooting

### Error: "Python 3.8+ not found"

**Cause:** Python 3 is not in your system PATH

**Solution:** 
1. Install Python from https://www.python.org/downloads/
2. Make sure to check "Add Python to PATH" during installation
3. Restart your terminal/IDE
4. Run the command again

### Error: "Failed to create virtual environment"

**Cause:** Permission denied or invalid location

**Solution:**
1. Check that you have write permissions in the project directory
2. Ensure the `python/` directory exists
3. Try removing `python/venv` if it exists: `rm -rf python/venv`
4. Run the command again

### Error: "Failed to install dependencies"

**Cause:** Network issue or incompatible packages

**Solution:**
1. Check your internet connection
2. Try updating pip: `python -m pip install --upgrade pip`
3. Try installing manually:
   ```bash
   cd python/venv/bin  # or Scripts on Windows
   ./pip install -r ../../requirements.txt
   ```

## Manual Setup (Alternative)

If the automated command doesn't work, you can set up manually:

```bash
cd python

# Create virtual environment
python3 -m venv venv

# Activate it
source venv/bin/activate     # On Linux/macOS
# OR
venv\Scripts\activate        # On Windows

# Install dependencies
pip install -r requirements.txt

# Verify
python -c "import langchain; import langchain_openai; print('✓ OK')"
```

## Virtual Environment Files

After running the install command, you'll have:

```
python/
├── venv/                      # Virtual environment (local, not committed)
│   ├── bin/                   # Executables (python3, pip, etc.)
│   │   ├── python3
│   │   ├── pip
│   │   ├── activate
│   │   └── ...
│   ├── lib/                   # Installed packages
│   ├── include/               # Header files
│   └── pyvenv.cfg
├── get_sql_response.py        # AI service script
├── requirements.txt           # Dependencies list
└── README.md                  # Python guide
```

## Using the Virtual Environment

The controller automatically uses the venv Python interpreter via the `PythonEnvironment` utility class. No manual activation is needed.

When the Laravel app needs to run the AI query generation:
1. Controller calls `generateQueryFromTitle()` endpoint
2. Controller uses `PythonEnvironment::getPythonExecutable()` to get the venv Python path
3. Controller spawns Python process with the venv interpreter
4. Python service runs in isolation with all dependencies available

## Re-running the Install

You can run the install command multiple times:
- If venv exists, it will skip creation and just ensure dependencies are up to date
- Useful for updating dependencies when `requirements.txt` changes

```bash
php artisan sql-analyze:install
```

## Skipping Setup

If you prefer manual setup or have your own Python setup:

1. Create a virtual environment manually
2. Install dependencies: `pip install -r python/requirements.txt`
3. The controller will automatically use `python/venv/bin/python3` (or Windows equivalent)

## Environment Variables

After setup, configure your `.env` file:

```env
OPENAI_API_KEY=sk_your_openai_api_key
DATABASE_URL=mysql://user:password@localhost:3306/database_name
```

The install command will remind you of these requirements.

## Support

If you encounter issues:

1. Check your Python version: `python3 --version` (should be 3.8+)
2. Check your internet connection (needed for pip)
3. Check disk space (pip needs ~500MB)
4. Review error message carefully
5. Try manual setup as alternative

For detailed troubleshooting, see `python/README.md`.

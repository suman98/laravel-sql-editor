# Setup Automation Upgrade - Installation Command

## Overview

The Python environment setup has been automated with a new **Artisan command** that eliminates manual setup steps and handles cross-platform compatibility.

## What's New

### ✨ New Features

1. **Automated Setup Command**
   - One-liner installation: `php artisan sql-analyze:install`
   - Handles all setup steps automatically
   - Cross-platform compatibility (Linux, macOS, Windows)

2. **Python Environment Utility**
   - `PythonEnvironment` service class for getting venv Python path
   - Automatic detection of virtual environment location
   - Error handling with helpful messages

3. **Smart Python Detection**
   - Automatically finds Python 3.8+ on system
   - Tests multiple Python interpreters
   - Validates version requirements

4. **Dependency Management**
   - Automated virtual environment creation
   - Automatic dependency installation via pip
   - Post-installation verification

5. **Better Error Messages**
   - Clear, actionable error messages
   - Helpful troubleshooting guidance
   - Setup progress indicators

## Installation Process

### Before (Manual)
```bash
cd python
pip install -r requirements.txt
# Manually manage venv
# Handle cross-platform differences
```

### After (Automated)
```bash
php artisan sql-analyze:install
# Automatic venv creation
# Automatic dependency installation
# Cross-platform compatibility
# Verification included
```

## New Files Created

### 1. `src/Console/Commands/InstallCommand.php` (165 lines)

**Purpose:** Artisan command for automated setup

**Functionality:**
- Validates Python 3.8+ availability
- Creates virtual environment if needed
- Installs dependencies from requirements.txt
- Verifies installation success
- Provides setup completion confirmation

**Usage:**
```bash
php artisan sql-analyze:install
```

**Exit Codes:**
- 0: Success
- 1: Setup failed

### 2. `src/Services/PythonEnvironment.php` (56 lines)

**Purpose:** Utility service for managing Python environment

**Methods:**
- `getPythonExecutable(): string` - Returns path to venv Python
- `isSetup(): bool` - Checks if venv is properly configured
- `getVenvDir(): string` - Returns venv directory path

**Usage:**
```php
use SqlAnalyzer\Services\PythonEnvironment;

$pythonPath = PythonEnvironment::getPythonExecutable();
// Returns: /path/to/python/venv/bin/python3

$isSetup = PythonEnvironment::isSetup();
// Returns: true/false
```

## Modified Files

### 1. `src/Http/Controllers/SqlAnalyzerController.php`

**Changes:**
- Added import: `use SqlAnalyzer\Services\PythonEnvironment;`
- Updated `generateQueryFromTitle()` method to use `PythonEnvironment::getPythonExecutable()` instead of hardcoded `'python3'`

**Before:**
```php
$process = new Process([
    'python3',  // Hardcoded interpreter
    $pythonScriptPath,
    $payload,
]);
```

**After:**
```php
$pythonExecutable = PythonEnvironment::getPythonExecutable();
$process = new Process([
    $pythonExecutable,  // Dynamic venv path
    $pythonScriptPath,
    $payload,
]);
```

### 2. `src/SqlAnalyzerServiceProvider.php`

**Changes:**
- Registered `InstallCommand` in the `boot()` method using `$this->commands()`

**Code:**
```php
if ($this->app->runningInConsole()) {
    $this->commands([
        Console\Commands\InstallCommand::class,
    ]);
}
```

### 3. `QUICK_START.md`

**Changes:**
- Updated Step 1 to use the new Artisan command
- Added explanation of what the command does
- Maintained manual setup option as fallback

### 4. `IMPLEMENTATION_SUMMARY.md`

**Changes:**
- Added new "Using Artisan Command (Recommended)" section
- Kept manual setup as alternative option
- Updated setup workflow

## How It Works

### Installation Flow

```
User runs: php artisan sql-analyze:install
    ↓
Command starts
    ↓
Check for Python 3.8+ → not found? → Error with instructions
    ↓
Virtual environment exists? → skip creation → create new venv
    ↓
Check requirements.txt exists → not found? → Error
    ↓
Install dependencies via pip
    ↓
Verify packages installed (langchain, openai, etc.)
    ↓
Success! Show configuration reminders
```

### Runtime Flow

```
User clicks "Generate Query"
    ↓
Frontend calls POST /sql-analyzer/generate-query
    ↓
Controller.generateQueryFromTitle() called
    ↓
PythonEnvironment::getPythonExecutable() → /path/to/python/venv/bin/python3
    ↓
Spawn process with venv Python
    ↓
Python service uses dependencies from venv
    ↓
Return SQL to frontend
```

## Benefits

### ✅ For Users
- **Simpler Setup:** One command instead of multiple manual steps
- **Better Error Messages:** Clear guidance when issues occur
- **Cross-Platform:** Works on Linux, macOS, and Windows
- **Automatic Verification:** Confirms setup success

### ✅ For Developers
- **Maintainability:** Centralized Python path management
- **Testability:** Easy to test venv setup
- **Consistency:** Same approach across different machines
- **Extensibility:** Can add more setup steps in future

### ✅ For Deployment
- **Automation:** CI/CD friendly
- **Reliability:** Same setup every time
- **Tracking:** See exactly what was installed
- **Rollback:** Can re-run setup anytime

## Environment Setup

After running the install command, your project structure includes:

```
python/
├── venv/                           # Virtual environment (25-30 MB)
│   ├── bin/ (or Scripts/ on Windows)
│   │   ├── python3
│   │   ├── python
│   │   ├── pip
│   │   ├── pip3
│   │   └── activate
│   ├── lib/                        # Installed packages
│   ├── include/                    # Header files
│   └── pyvenv.cfg                  # Configuration
├── get_sql_response.py             # AI service script
├── requirements.txt                # Package list
└── README.md                       # Documentation
```

## Troubleshooting

### "Python 3.8+ not found"

**Cause:** Python not in system PATH

**Solution:**
1. Install Python from https://www.python.org/
2. On Windows, check "Add Python to PATH"
3. Restart terminal/IDE
4. Run command again

### "Failed to create virtual environment"

**Cause:** Permission or directory issue

**Solution:**
```bash
# Remove existing venv if corrupted
rm -rf python/venv
# Run command again
php artisan sql-analyze:install
```

### "Failed to install dependencies"

**Cause:** Network issue or pip problem

**Solution:**
```bash
# Update pip first
python3 -m pip install --upgrade pip
# Run command again
php artisan sql-analyze:install
```

## Configuration After Setup

Once setup is complete, add to your `.env`:

```env
# Required for AI query generation
OPENAI_API_KEY=sk_your_openai_api_key_here
DATABASE_URL=mysql://user:password@localhost/database_name
```

## Manual Alternative

If the automated command doesn't work:

```bash
cd python
python3 -m venv venv
source venv/bin/activate     # Linux/macOS
# OR
venv\Scripts\activate        # Windows
pip install -r requirements.txt
```

## Verification

To verify the installation:

```bash
# Option 1: Run setup again (will verify)
php artisan sql-analyze:install

# Option 2: Check manually
python/venv/bin/python3 -c "import langchain; print('✓ OK')"
```

## What Gets Installed?

The `requirements.txt` includes:

```
langchain==0.1.14
langchain-openai==0.1.3
langchain-community==0.0.28
python-dotenv==1.0.0
SQLAlchemy==2.0.25
```

Plus their dependencies (~50MB total)

## Next Steps

1. **Run setup:** `php artisan sql-analyze:install`
2. **Configure .env:** Add `OPENAI_API_KEY` and `DATABASE_URL`
3. **Test:** Try "Generate Query" in the editor
4. **Deploy:** Copy the package to your application

## Backward Compatibility

✅ **Full backward compatibility**
- Existing code continues to work
- Manual setup still available
- No breaking changes
- Purely additive feature

## Future Enhancements

Possible improvements:
- Dependency version pinning per Python version
- Automated updates: `php artisan sql-analyze:install --update`
- Environment variable validation in install command
- Setup status command: `php artisan sql-analyze:status`
- Uninstall command: `php artisan sql-analyze:clean`

## Support

- **Documentation:** See `ARTISAN_INSTALL.md`
- **Quick Start:** See `QUICK_START.md`
- **Troubleshooting:** See `python/README.md`
- **Implementation Details:** See `IMPLEMENTATION_SUMMARY.md`

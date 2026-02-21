<?php

namespace SqlAnalyzer\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallCommand extends Command
{
    protected $signature = 'sql-analyze:install';

    protected $description = 'Install and set up the Python environment for SQL Analyzer AI query generation';

    public function handle(): int
    {
        $this->info('Setting up SQL Analyzer Python environment...');

        try {
            // Resolve the package path (not the app path)
            // __DIR__ = src/Console/Commands/
            // We need to go up to sql-analyzer root: ../../
            $packageRoot = dirname(__DIR__, 3);
            $pythonDir = $packageRoot . '/python';
            $venvDir = $pythonDir . '/venv';
            $requirementsFile = $pythonDir . '/requirements.txt';

            // Step 1: Check if requirements.txt exists
            if (!file_exists($requirementsFile)) {
                $this->error("❌ requirements.txt not found at {$requirementsFile}");
                return 1;
            }
            $this->line('✓ Found requirements.txt');

            // Step 2: Create virtual environment if it doesn't exist
            if (!file_exists($venvDir)) {
                $this->info('Creating Python virtual environment...');

                $pythonExecutable = $this->getPythonExecutable();
                if (!$pythonExecutable) {
                    $this->error('❌ Python 3.8+ not found. Please install Python 3.8 or higher.');
                    return 1;
                }

                $process = new Process([
                    $pythonExecutable,
                    '-m',
                    'venv',
                    $venvDir,
                ]);

                $process->setTimeout(120);
                $process->run();

                if (!$process->isSuccessful()) {
                    $this->error('❌ Failed to create virtual environment:');
                    $this->error($process->getErrorOutput());
                    return 1;
                }

                $this->line('✓ Virtual environment created at ' . $venvDir);
            } else {
                $this->line('✓ Virtual environment already exists at ' . $venvDir);
            }

            // Step 3: Install dependencies
            $this->info('Installing Python dependencies...');

            $pipExecutable = $this->getPipExecutable($venvDir);
            if (!$pipExecutable) {
                $this->error('❌ Could not find pip in virtual environment');
                return 1;
            }

            $process = new Process([
                $pipExecutable,
                'install',
                '-r',
                $requirementsFile,
            ]);

            $process->setTimeout(300); // 5 minutes for pip install
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('❌ Failed to install dependencies:');
                $this->error($process->getErrorOutput());
                return 1;
            }

            $this->info('✓ Dependencies installed successfully');

            // Step 4: Verify installation
            $pythonVenvExecutable = $this->getPythonVenvExecutable($venvDir);
            $verifyProcess = new Process([
                $pythonVenvExecutable,
                '-c',
                'import langchain; import langchain_openai; import sqlalchemy; print("OK")',
            ]);

            $verifyProcess->setTimeout(30);
            $verifyProcess->run();

            if ($verifyProcess->isSuccessful() && trim($verifyProcess->getOutput()) === 'OK') {
                $this->line('✓ Verified: All required Python packages available');
            } else {
                $this->warn('⚠ Could not verify Python packages. They may still be installed.');
            }

            $this->newLine();
            $this->info('✅ SQL Analyzer Python environment setup complete!');
            $this->line('');
            $this->info('Environment ready for AI-powered query generation.');
            $this->line('');
            $this->info('Make sure your .env file contains:');
            $this->line('  OPENAI_API_KEY=your_api_key_here');
            $this->line('  DATABASE_URL=your_database_url_here');

            return 0;

        } catch (\Throwable $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Find the Python 3.8+ executable.
     */
    private function getPythonExecutable(): ?string
    {
        $candidates = ['python3', 'python', 'python3.11', 'python3.10', 'python3.9', 'python3.8'];

        foreach ($candidates as $executable) {
            $process = new Process(['which', $executable]);
            $process->run();

            if ($process->isSuccessful()) {
                // Verify it's Python 3.8+
                $versionProcess = new Process([$executable, '--version']);
                $versionProcess->run();

                if ($versionProcess->isSuccessful()) {
                    return $executable;
                }
            }
        }

        return null;
    }

    /**
     * Get the pip executable from the virtual environment.
     */
    private function getPipExecutable(string $venvDir): ?string
    {
        $isWindows = str_contains(PHP_OS, 'WIN');
        $pipPath = $isWindows
            ? $venvDir . '\\Scripts\\pip.exe'
            : $venvDir . '/bin/pip';

        if (file_exists($pipPath)) {
            return $pipPath;
        }

        return null;
    }

    /**
     * Get the Python executable from the virtual environment.
     */
    private function getPythonVenvExecutable(string $venvDir): ?string
    {
        $isWindows = str_contains(PHP_OS, 'WIN');
        $pythonPath = $isWindows
            ? $venvDir . '\\Scripts\\python.exe'
            : $venvDir . '/bin/python3';

        if (file_exists($pythonPath)) {
            return $pythonPath;
        }

        // Fallback
        return $isWindows ? $venvDir . '\\Scripts\\python.exe' : $venvDir . '/bin/python';
    }
}

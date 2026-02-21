<?php

namespace SqlAnalyzer\Services;

use RuntimeException;

class PythonEnvironment
{
    /**
     * Get the Python executable path from the virtual environment.
     *
     * @return string The path to the Python executable
     * @throws RuntimeException If Python executable cannot be found
     */
    public static function getPythonExecutable(): string
    {
        // Resolve the package path (not the app path)
        // __FILE__ = src/Services/PythonEnvironment.php
        // dirname(__FILE__, 3) goes up to the package root
        $packageRoot = dirname(__FILE__, 3);
        $venvDir = $packageRoot . '/python/venv';

        // Check if venv exists
        if (!is_dir($venvDir)) {
            throw new RuntimeException(
                'Python virtual environment not found. ' .
                'Run "php artisan sql-analyze:install" to set up the environment.'
            );
        }

        // Determine OS and construct path
        $isWindows = str_contains(PHP_OS, 'WIN');
        $pythonPath = $isWindows
            ? $venvDir . '\\Scripts\\python.exe'
            : $venvDir . '/bin/python3';

        // Check if the executable exists
        if (!file_exists($pythonPath)) {
            // Fallback for Windows without .exe extension
            if ($isWindows) {
                $pythonPath = $venvDir . '\\Scripts\\python';
            }

            if (!file_exists($pythonPath)) {
                throw new RuntimeException(
                    "Python executable not found at {$pythonPath}. " .
                    'Run "php artisan sql-analyze:install" to set up the environment.'
                );
            }
        }

        return $pythonPath;
    }

    /**
     * Check if the Python environment is properly set up.
     *
     * @return bool True if setup is complete, false otherwise
     */
    public static function isSetup(): bool
    {
        try {
            $pythonPath = self::getPythonExecutable();
            return file_exists($pythonPath);
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Get the directory path of the Python virtual environment.
     *
     * @return string The venv directory path
     */
    public static function getVenvDir(): string
    {
        // Resolve the package path (not the app path)
        $packageRoot = dirname(__FILE__, 3);
        return $packageRoot . '/python/venv';
    }
}

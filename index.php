<?php

/**
 * Workbench Proxy with Full Debug Configuration
 */

use Illuminate\Http\Request;

$packageRoot = __DIR__;

// ============================================
// SET DEBUG MODE
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set environment variables
putenv('APP_DEBUG=true');
putenv('APP_ENV=local');
putenv('APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
putenv('LOG_CHANNEL=single');
putenv('LOG_LEVEL=debug');

// ============================================
// BOOTSTRAP APPLICATION
// ============================================

require_once $packageRoot . '/vendor/autoload.php';

$app = require_once $packageRoot . '/vendor/orchestra/testbench-core/laravel/bootstrap/app.php';

// ============================================
// CONFIGURE DEBUG
// ============================================
$app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
$app['config']->set('app.debug', true);
$app['config']->set('app.env', 'local');
$app['config']->set('log.channel', 'single');
$app['config']->set('log.level', 'debug');

// Enable query logging (shows all DB queries)
$app['config']->set('database.queries', true);

// ============================================
// REGISTER YOUR PACKAGE
// ============================================

$app->register(\SqlAnalyzer\SqlAnalyzerServiceProvider::class);


// ============================================
// HANDLE REQUEST
// ============================================

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request = Request::capture());
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    // Show detailed error page
    echo '<html><head><style>
        body { font-family: monospace; background: #f5f5f5; margin: 20px; }
        h1 { color: #d32f2f; }
        pre { background: white; padding: 15px; overflow: auto; border: 1px solid #ddd; }
    </style></head><body>';
    
    echo '<h1>⚠️ ' . get_class($e) . '</h1>';
    echo '<h2>' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '<hr>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
    
    echo '</body></html>';
    exit(1);
}

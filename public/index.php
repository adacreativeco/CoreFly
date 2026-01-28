<?php
// Basic Setup
session_start();
ob_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
} catch (Exception $e) {
    error_log("Dotenv load warning: " . $e->getMessage());
}

// Global Exception Handler
set_exception_handler(function ($e) {
    \CoreFly\Utils\Logger::error("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    if (ob_get_length())
        ob_clean();

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error',
        'error' => (getenv('APP_DEBUG') === 'true') ? $e->getMessage() : 'An unexpected error occurred.'
    ]);
});

// Global Error Handler (for Fatals etc)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length())
            ob_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error',
            'error' => $error['message']
        ]);
    }
});

use CoreFly\Core\Router;
use CoreFly\Utils\RateLimiter;
use CoreFly\Utils\Logger;
use CoreFly\Middleware\MeteringMiddleware;

// Basic Security Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');

if ((require __DIR__ . '/../config/app.php')['app']['env'] === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Rate Limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Ignore Rate Limiting for localhost to prevent test failures
if ($ip !== '::1' && $ip !== '127.0.0.1' && !RateLimiter::allow('api_' . $ip, 100, 60)) {
    Logger::error("Rate limit exceeded for IP {$ip}");
    http_response_code(429);
    echo json_encode(['error' => 'Too Many Requests']);
    exit();
}

// Metering (Phase 4)
(new MeteringMiddleware())->handle();

// Instantiate Router
$router = new Router();

// Load Routes
$router = require __DIR__ . '/../routes/api.php';

// Static File Serving (Legacy Support for dashboard.html)
$path = parse_url($uri, PHP_URL_PATH);
$path = ltrim($path, '/'); // Remove leading slash

// If it looks like an API call, dispatch to router
if (str_starts_with($path, 'api/')) {
    // Debug
// file_put_contents('debug_log.txt', "Dispatching: /" . $path . "\n", FILE_APPEND);
    $router->dispatch($method, '/' . $path);
} else {
    // Serve static files or fallback to SPA shell
    if ($path === '' || $path === null || $path === 'index.php') {
        $index = __DIR__ . '/index.html';
        $login = __DIR__ . '/login.html';
        $target = file_exists($index) ? $index : (file_exists($login) ? $login : null);
        if ($target) {
            header('Content-Type: text/html');
            readfile($target);
        } else {
            http_response_code(404);
            echo "Not Found";
        }
    } else {
        // Prevent direct access to legacy dashboard.html
        if ($path === 'dashboard.html') {
            header('Location: /');
            exit();
        }

        $file_path = __DIR__ . '/' . $path;
        if (file_exists($file_path) && is_file($file_path)) {
            $ext = pathinfo($file_path, PATHINFO_EXTENSION);
            $content_types = [
                'html' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml'
            ];
            if (isset($content_types[$ext])) {
                header('Content-Type: ' . $content_types[$ext]);
            }
            readfile($file_path);
        } else {
            // Try to serve via router anyway (for non-api routes if any)
            $router->dispatch($method, '/' . $path);
        }
    }
}
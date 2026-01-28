<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenvClass = 'Dotenv\\Dotenv';
    if (class_exists($dotenvClass)) {
        $dotenv = $dotenvClass::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
} catch (Exception $e) {
    // Ignore
}

use CoreFly\Services\JwtService;

function req(string $path, string $method = 'GET', array $headers = []): array {
    $url = 'http://localhost:8001' . $path;
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", array_map(fn($k,$v) => "$k: $v", array_keys($headers), $headers)),
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    // global $http_response_header; // NOT needed, it's local
    if (isset($http_response_header[0]) && preg_match('#HTTP/\d\.\d\s+(\d+)#', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    return ['status' => $status, 'body' => $body, 'headers' => $http_response_header ?? []];
}

$jwt = new JwtService();
$token = $jwt->generateToken([
    'user_id' => '1a332a5440fd81ab68fb943c9af6e458', // Admin user
    'role' => 'tenant-admin-role',
    'tenant_id' => 'default-tenant-uuid'
])['token'];
$auth = ['Authorization' => 'Bearer ' . $token];

$results = [];
$results['dashboard'] = req('/api/dashboard', 'GET', $auth);
$results['announcements'] = req('/api/announcements', 'GET', $auth);
$results['documents'] = req('/api/documents', 'GET', $auth);
$results['widgets'] = req('/api/dashboard/widgets', 'GET', $auth);

// New Modules
$results['projects'] = req('/api/projects', 'GET', $auth);
$results['inventory'] = req('/api/inventory', 'GET', $auth);
$results['donations'] = req('/api/donations', 'GET', $auth);
$results['fields'] = req('/api/field/stats', 'GET', $auth);

foreach ($results as $name => $res) {
    echo sprintf("[%s] status=%d length=%d\n", $name, $res['status'], is_string($res['body']) ? strlen($res['body']) : -1);
    if ($res['status'] !== 200) {
        echo "Headers: " . print_r($res['headers'], true) . "\n";
        echo "Body: " . substr((string)$res['body'], 0, 500) . "\n";
    }
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';

// Dotenv check
$dotenvData = [];
try {
    $dotenvClass = 'Dotenv\\Dotenv';
    if (class_exists($dotenvClass)) {
        $dotenv = $dotenvClass::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
        $dotenvData['loaded'] = true;
    }
} catch (Exception $e) {
    $dotenvData['error'] = $e->getMessage();
}

$diag = [
    'php_version' => PHP_VERSION,
    'env_app_name' => $_ENV['APP_NAME'] ?? 'NOT SET in $_ENV',
    'getenv_app_name' => getenv('APP_NAME') ?? 'NOT SET in getenv',
    'dotenv' => $dotenvData,
    'db_check' => [],
];

try {
    $db = \CoreFly\Utils\Database::getInstance();
    $conn = $db->getConnection();
    $diag['db_check']['connected'] = true;
    $diag['db_check']['driver'] = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
} catch (Exception $e) {
    $diag['db_check']['error'] = $e->getMessage();
}

echo json_encode($diag, JSON_PRETTY_PRINT);

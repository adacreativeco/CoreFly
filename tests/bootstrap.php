<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

// Ensure storage paths
$dbFile = __DIR__ . '/../storage/test.sqlite';
$_ENV['DB_NAME'] = $dbFile;
$dir = dirname($dbFile);
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
// Create/clean test DB
if (file_exists($dbFile)) {
    unlink($dbFile);
}

// Apply SQLite schema
$schema = file_get_contents(__DIR__ . '/../database/migrations/sqlite_schema.sql');
$db = Database::getInstance();
$db->connect('default');
$db->exec($schema);

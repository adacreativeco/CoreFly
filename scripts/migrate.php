<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Migrator.php';

// Load Env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

use App\Core\Migrator;

try {
    $migrator = new Migrator();
    $migrator->run();
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

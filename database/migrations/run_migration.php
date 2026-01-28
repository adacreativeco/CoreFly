<?php

require_once __DIR__ . '/../../tests/bootstrap.php'; // Reuse bootstrap for autoloader and config

use CoreFly\Utils\Database;

$sqlFile = __DIR__ . '/add_announcement_fields.sql';
if (!file_exists($sqlFile)) {
    die("Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
$db = Database::getInstance();

echo "Running migration...\n";

// Split by semicolon to run individually, as PDO exec sometimes handles only one statement
$statements = explode(';', $sql);

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    try {
        $db->exec($stmt);
        echo "Executed: " . substr($stmt, 0, 50) . "...\n";
    } catch (Exception $e) {
        // Ignore "duplicate column name" error if re-running
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "Skipped (already exists): " . substr($stmt, 0, 50) . "...\n";
        } else {
            echo "Error executing: $stmt\n" . $e->getMessage() . "\n";
        }
    }
}

echo "Migration completed.\n";

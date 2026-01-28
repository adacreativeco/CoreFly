<?php
// database/migrations/2024_12_08_000009_fix_events_schema.php

require_once __DIR__ . '/../../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();
$db->connect();

echo "Running migration: 009_fix_events_schema\n";

// Add department_id to events
try {
    $db->exec("ALTER TABLE events ADD COLUMN department_id VARCHAR(36) DEFAULT NULL");
    echo "Added column: department_id\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Column department_id already exists.\n";
    } else {
        echo "Error adding department_id: " . $e->getMessage() . "\n";
    }
}

echo "Migration 009 completed.\n";

<?php
// fix_projects_schema.php

require_once __DIR__ . '/../../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();
$db->connect();

echo "Running migration: 008_fix_projects_schema\n";

// We need to add: project_type, department_id, budget, priority
$columnsToAdd = [
    'project_type' => "VARCHAR(50) DEFAULT 'general'",
    'department_id' => "VARCHAR(36)",
    'budget' => "DECIMAL(15, 2)",
    'priority' => "VARCHAR(50) DEFAULT 'medium'"
];

foreach ($columnsToAdd as $col => $def) {
    try {
        $db->exec("ALTER TABLE projects ADD COLUMN $col $def");
        echo "Added column: $col\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "Column $col already exists.\n";
        } else {
            echo "Error adding $col: " . $e->getMessage() . "\n";
        }
    }
}

echo "Migration 008 completed.\n";

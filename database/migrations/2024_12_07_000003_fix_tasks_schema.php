<?php

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Running migration: 003_fix_tasks_schema\n";

// 1. Create subtasks table
$db->exec("CREATE TABLE IF NOT EXISTS subtasks (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    task_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
)");

// 2. Add missing columns to tasks table
$columnsToAdd = [
    "department_id" => "VARCHAR(36)",
    "progress" => "INT DEFAULT 0",
    "estimated_hours" => "DECIMAL(10, 2)",
    "tags" => "TEXT",
    "position" => "INT DEFAULT 0",
    "start_date" => "DATETIME"
];

foreach ($columnsToAdd as $col => $def) {
    try {
        $db->exec("ALTER TABLE tasks ADD COLUMN $col $def");
        echo "Added column $col to tasks\n";
    } catch (\Exception $e) {
        // Column likely exists
        // echo "Column $col likely exists or error: " . $e->getMessage() . "\n";
    }
}

echo "Migration 003 completed.\n";

<?php

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Running migration: 005_fix_tasks_schema_nullability\n";

// 1. Make tasks.assigned_to nullable
// SQLite does not support ALTER COLUMN directly for constraints.
// We must recreate the table.

$db->exec("CREATE TABLE IF NOT EXISTS tasks_new (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    project_id VARCHAR(36),
    department_id VARCHAR(36),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'todo',
    priority VARCHAR(50) DEFAULT 'medium',
    assigned_to VARCHAR(36), -- Nullable
    created_by VARCHAR(36),
    due_date DATETIME,
    progress INT DEFAULT 0,
    estimated_hours DECIMAL(10, 2),
    tags TEXT,
    position INT DEFAULT 0,
    start_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Copy data (excluding created_by if not exists, but schema says it was there? Wait, schema 001 had it)
// Let's check columns first to be safe
$columns = $db->query("PRAGMA table_info(tasks)")->fetchAll(PDO::FETCH_COLUMN, 1);
$commonColumns = array_intersect($columns, [
    'id', 'tenant_id', 'project_id', 'department_id', 'title', 'description', 
    'status', 'priority', 'assigned_to', 'created_by', 'due_date', 
    'progress', 'estimated_hours', 'tags', 'position', 'start_date', 
    'created_at', 'updated_at'
]);
$colStr = implode(', ', $commonColumns);

$db->exec("INSERT INTO tasks_new ($colStr) SELECT $colStr FROM tasks");

// Drop old table
$db->exec("DROP TABLE tasks");

// Rename new table
$db->exec("ALTER TABLE tasks_new RENAME TO tasks");

echo "Migration 005 completed.\n";

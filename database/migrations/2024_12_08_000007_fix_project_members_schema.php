<?php

require_once __DIR__ . '/../../tests/bootstrap.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Running migration: 007_fix_project_members_schema\n";

// Add tenant_id to project_members
// SQLite ALTER TABLE ADD COLUMN is supported
try {
    $db->exec("ALTER TABLE project_members ADD COLUMN tenant_id VARCHAR(36) DEFAULT 'default'");
    echo "Added tenant_id column to project_members\n";
    
    // Update existing records to have the tenant_id from their project (if any)
    // Complex update with join not always supported directly in all SQLite versions, 
    // but for now default 'default' is safe enough or we can run a subquery update.
    $db->exec("UPDATE project_members SET tenant_id = (SELECT tenant_id FROM projects WHERE projects.id = project_members.project_id) WHERE tenant_id = 'default'");
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Column tenant_id already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        // Fallback: Recreate table if ALTER fails seriously (unlikely for ADD COLUMN)
    }
}

echo "Migration 007 completed.\n";

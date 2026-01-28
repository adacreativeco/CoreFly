<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Checking message_groups table...\n";

// 1. Ensure message_groups exists
$sql = "CREATE TABLE IF NOT EXISTS message_groups (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    type TEXT DEFAULT 'public', -- public, private, dm
    created_by TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
$db->exec($sql);
echo "Table message_groups checked/created.\n";

// 2. Create message_group_members
echo "Creating message_group_members table...\n";
$sql = "CREATE TABLE IF NOT EXISTS message_group_members (
    id TEXT PRIMARY KEY,
    group_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_admin INTEGER DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES message_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$db->exec($sql);
echo "Table message_group_members created.\n";

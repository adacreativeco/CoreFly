<?php

require_once 'vendor/autoload.php';

use CoreFly\Core\Database;

try {
    $db = new PDO('sqlite:storage/corefly.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Message Groups
    $db->exec("CREATE TABLE IF NOT EXISTS message_groups (
        id TEXT PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'public',
        created_by TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Message Group Members
    $db->exec("CREATE TABLE IF NOT EXISTS message_group_members (
        id TEXT PRIMARY KEY,
        group_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_admin INTEGER DEFAULT 0
    )");
    
    // Messages
    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id TEXT PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        sender_id TEXT NOT NULL,
        receiver_id TEXT,
        group_id TEXT,
        message TEXT,
        file_path TEXT,
        file_type TEXT,
        is_read INTEGER DEFAULT 0,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // HR Payrolls
    $db->exec("CREATE TABLE IF NOT EXISTS hr_payrolls (
        id TEXT PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        employee_id TEXT NOT NULL,
        period TEXT NOT NULL,
        base_salary REAL,
        bonus REAL DEFAULT 0,
        deductions REAL DEFAULT 0,
        net_salary REAL,
        status TEXT DEFAULT 'pending',
        payment_date DATETIME,
        notes TEXT,
        created_by TEXT,
        updated_by TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Migration successful: Messages and HR Payrolls tables created.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
require_once __DIR__ . '/../src/Utils/Database.php';

use CoreFly\Utils\Database;

try {
    $db = Database::getInstance();
    
    echo "Migrating call_history table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS call_history (
        id VARCHAR(36) PRIMARY KEY,
        caller_id VARCHAR(36) NOT NULL,
        callee_id VARCHAR(36) NOT NULL,
        group_id VARCHAR(36),
        call_type VARCHAR(10) CHECK (call_type IN ('voice', 'video')),
        status VARCHAR(20) CHECK (status IN ('initiated', 'ringing', 'answered', 'ended', 'missed', 'rejected')),
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ended_at DATETIME,
        duration_seconds INTEGER
    )";
    
    $db->exec($sql);
    
    echo "Table call_history created successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

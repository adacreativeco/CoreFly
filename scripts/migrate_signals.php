<?php
require_once __DIR__ . '/../src/Utils/Database.php';

use CoreFly\Utils\Database;

try {
    $db = Database::getInstance();
    
    echo "Migrating call_signals table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS call_signals (
        id VARCHAR(36) PRIMARY KEY,
        call_id VARCHAR(36) NOT NULL,
        sender_id VARCHAR(36) NOT NULL,
        type VARCHAR(20) NOT NULL, -- 'offer', 'answer', 'candidate'
        payload TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_processed BOOLEAN DEFAULT 0
    )";
    
    $db->exec($sql);
    
    // Index for faster polling
    $db->exec("CREATE INDEX IF NOT EXISTS idx_signals_call_id ON call_signals(call_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_signals_processed ON call_signals(is_processed)");
    
    echo "Table call_signals created successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

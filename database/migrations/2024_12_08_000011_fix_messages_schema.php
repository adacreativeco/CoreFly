<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Fixing messages table schema (making receiver_id nullable)...\n";

try {
    $db->beginTransaction();

    // 1. Create new table with correct schema
    $db->exec("CREATE TABLE messages_new (
        id VARCHAR(36) PRIMARY KEY,
        tenant_id VARCHAR(36) NOT NULL,
        sender_id VARCHAR(36) NOT NULL,
        receiver_id VARCHAR(36) DEFAULT NULL, -- Explicitly nullable
        group_id VARCHAR(36) DEFAULT NULL,
        message TEXT,
        file_path VARCHAR(255),
        file_type VARCHAR(50),
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES message_groups(id) ON DELETE CASCADE
    )");

    // 2. Copy data
    // We assume the old table has compatible columns. 
    // If the old table has data with receiver_id, it copies fine.
    $db->exec("INSERT INTO messages_new SELECT * FROM messages");

    // 3. Drop old table
    $db->exec("DROP TABLE messages");

    // 4. Rename new table
    $db->exec("ALTER TABLE messages_new RENAME TO messages");

    $db->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

<?php

use CoreFly\Utils\Database;

class CreateFeatureFlagsTable
{
    public function up()
    {
        $db = Database::getInstance();
        $sql = "CREATE TABLE IF NOT EXISTS feature_flags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            is_global BOOLEAN DEFAULT FALSE,
            default_value BOOLEAN DEFAULT FALSE,
            rules JSON, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->pdo->exec($sql);
        echo "Table 'feature_flags' created successfully.\n";
    }
}

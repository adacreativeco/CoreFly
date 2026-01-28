<?php

use CoreFly\Utils\Database;

class CreateAnnouncementsTable
{
    public function up()
    {
        $db = Database::getInstance();
        $sql = "CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            type ENUM('info', 'warning', 'critical') DEFAULT 'info',
            target_audience JSON, -- e.g. {'tenants': [1, 2], 'plans': ['pro']}
            is_active BOOLEAN DEFAULT TRUE,
            published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->pdo->exec($sql);
        echo "Table 'announcements' created successfully.\n";
    }
}

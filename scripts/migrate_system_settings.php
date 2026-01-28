<?php

require_once __DIR__ . '/../vendor/autoload.php';
// Fallback if autoloader doesn't pick it up immediately in this context
if (!class_exists('CoreFly\Utils\Database')) {
    require_once __DIR__ . '/../src/Utils/Database.php';
}

use CoreFly\Utils\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Create system_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE,
        setting_value TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default settings if empty
    $count = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        
        $defaults = [
            ['site_name', 'CoreFly NGO Platform', 'Application Name'],
            ['maintenance_mode', 'false', 'System Maintenance Mode'],
            ['allow_registration', 'true', 'Allow new user registrations'],
            ['default_language', 'tr', 'Default System Language'],
            ['contact_email', 'admin@corefly.com', 'Contact Email Address']
        ];

        foreach ($defaults as $setting) {
            $stmt->execute($setting);
        }
        echo "Default system settings seeded.\n";
    } else {
        echo "System settings already seeded.\n";
    }

    echo "System Settings migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance()->getConnection();

echo "Migrating events table...\n";

// Check if 'color' column exists
$cols = $db->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
$hasColor = false;
$hasAllDay = false;

foreach ($cols as $col) {
    if ($col['name'] === 'color') $hasColor = true;
    if ($col['name'] === 'all_day') $hasAllDay = true;
}

if (!$hasColor) {
    echo "Adding 'color' column...\n";
    $db->exec("ALTER TABLE events ADD COLUMN color TEXT DEFAULT '#3b82f6'");
}

if (!$hasAllDay) {
    echo "Adding 'all_day' column...\n";
    $db->exec("ALTER TABLE events ADD COLUMN all_day INTEGER DEFAULT 0");
}

echo "Migration complete.\n";

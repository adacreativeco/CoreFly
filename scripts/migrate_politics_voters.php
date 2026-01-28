<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

echo "Migrating Politics Voters...\n";

$dbPath = __DIR__ . '/../storage/corefly.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS politics_voters (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    full_name TEXT NOT NULL,
    tc_no TEXT,
    phone TEXT,
    address TEXT,
    neighborhood TEXT,
    box_id TEXT,
    status TEXT DEFAULT 'undecided',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

echo "Migration successful.\n";

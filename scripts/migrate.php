<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

function println(string $msg): void { echo $msg . PHP_EOL; }

try {
    $db = Database::getInstance();
    // Ensure connection
    $db->connect();
    println('[INFO] Connected to database');

    $config = require __DIR__ . '/../config/app.php';
    $driver = $config['database']['default']['driver'];
    $migrationFile = $driver === 'sqlite'
        ? __DIR__ . '/../database/migrations/sqlite_schema.sql'
        : __DIR__ . '/../database/migrations/001_corefly_schema.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception('Migration file not found: ' . $migrationFile);
    }

    $sql = file_get_contents($migrationFile);
    if ($driver === 'sqlite') {
        $db->exec($sql);
    } else {
        $db->executeMigration($sql);
    }
    println('[INFO] Migration executed successfully');

    $tables = ['tenants','users','departments','roles','permissions','role_permissions','user_sessions','password_resets','audit_logs','notifications','announcements','tasks','events','documents'];
    foreach ($tables as $t) {
        $exists = $db->tableExists($t) ? 'OK' : 'MISSING';
        println(sprintf('[CHECK] Table %-16s : %s', $t, $exists));
    }

    println('[DONE] Migration and verification complete');
    exit(0);
} catch (Throwable $e) {
    println('[ERROR] ' . $e->getMessage());
    exit(1);
}

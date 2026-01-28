<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

function println(string $msg): void { echo $msg . PHP_EOL; }

try {
    $db = Database::getInstance();
    $db->connect('default');

    $id = 'admin-user';
    $tenant = 'default-tenant-uuid';
    $now = date('Y-m-d H:i:s');
    $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);

    // Remove if exists
    $db->exec("DELETE FROM users WHERE id='{$id}'");

    $db->seedTable('users', [[
        'id' => $id,
        'tenant_id' => $tenant,
        'username' => 'admin',
        'email' => 'admin@corefly.com',
        'password' => $passwordHash,
        'first_name' => 'Admin',
        'last_name' => 'User',
        'phone' => null,
        'avatar' => null,
        'department_id' => null,
        'role_id' => 'tenant-admin-role',
        'status' => 'active',
        'email_verified' => 1,
        'two_factor_enabled' => 0,
        'two_factor_secret' => null,
        'last_login_at' => null,
        'login_attempts' => 0,
        'locked_until' => null,
        'preferences' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]]);

    println('[DONE] Admin user seeded: login="admin" / password="admin123"');
    exit(0);
} catch (Throwable $e) {
    println('[ERROR] ' . $e->getMessage());
    exit(1);
}


<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

function println(string $msg): void { echo $msg . PHP_EOL; }

try {
    $db = Database::getInstance();
    $db->connect();
    println('[INFO] Connected');

    $tenant = 'default-tenant-uuid';
    $now = date('Y-m-d H:i:s');

    $db->seedTable('announcements', [
        [
            'id' => bin2hex(random_bytes(16)),
            'tenant_id' => $tenant,
            'title' => 'Sistem Güncellemesi',
            'content' => '15 Aralık’ta bakım çalışması yapılacaktır.',
            'author_id' => '550e8400-e29b-41d4-a716-446655440000',
            'is_pinned' => 1,
            'priority' => 'high',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    println('[DONE] Demo data seeded');
    exit(0);
} catch (Throwable $e) {
    println('[ERROR] ' . $e->getMessage());
    exit(1);
}


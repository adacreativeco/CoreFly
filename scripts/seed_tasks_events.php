<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();
$db->connect('default');

$now = date('Y-m-d H:i:s');
$tenant = 'default-tenant-uuid';

$tasks = [
    [
        'id' => bin2hex(random_bytes(16)),
        'tenant_id' => $tenant,
        'title' => 'Proje raporunu tamamla',
        'description' => 'Aylık raporu oluştur',
        'assigned_to' => 'admin-user',
        'status' => 'in_progress',
        'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+3 days')),
        'progress' => 50,
        'created_at' => $now,
        'updated_at' => $now,
    ],
];

$events = [
    [
        'id' => bin2hex(random_bytes(16)),
        'tenant_id' => $tenant,
        'user_id' => 'admin-user',
        'title' => 'Takım Toplantısı',
        'description' => 'Haftalık değerlendirme',
        'start_date' => date('Y-m-d H:i:s', strtotime('+2 days 10:00')),
        'end_date' => date('Y-m-d H:i:s', strtotime('+2 days 11:00')),
        'location' => 'Salon A',
        'type' => 'meeting',
        'created_at' => $now,
        'updated_at' => $now,
    ],
];

$db->seedTable('tasks', $tasks);
$db->seedTable('events', $events);

echo "Seeded tasks and events\n";


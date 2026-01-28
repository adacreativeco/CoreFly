<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();
$db->connect('default');

$perms = [
    'announcements:create','announcements:read','announcements:update','announcements:delete',
    'documents:create','documents:read','documents:update','documents:delete',
    'tasks:create','tasks:read','tasks:update','tasks:delete',
    'calendar:create','calendar:read','calendar:update','calendar:delete',
    'reports:read'
];

foreach ($perms as $p) {
    try {
        $db->seedTable('permissions', [[ 'id' => bin2hex(random_bytes(8)), 'name' => $p ]]);
    } catch (Exception $e) {}
}

$role = 'tenant-admin-role';
foreach ($perms as $p) {
    try {
        $db->seedTable('role_permissions', [[ 'role_id' => $role, 'permission' => $p ]]);
    } catch (Exception $e) {}
}

echo "Seeded role permissions for {$role}\n";


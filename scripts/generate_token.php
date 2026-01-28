<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Services\JwtService;

$s = new JwtService();
$result = $s->generateToken([
    'user_id' => 'demo-user',
    'role' => 'tenant-admin-role',
    'tenant_id' => 'default-tenant-uuid'
]);
echo $result['token'];


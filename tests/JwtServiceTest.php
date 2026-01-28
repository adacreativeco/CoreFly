<?php

declare(strict_types=1);

namespace CoreFly\Tests;
use CoreFly\Services\JwtService;

class JwtServiceTest extends BaseTestCase
{
    public function testGenerateAndValidateToken(): void
    {
        $svc = new JwtService();
        $result = $svc->generateToken([
            'user_id' => 'u1',
            'role' => 'tenant-admin-role',
            'tenant_id' => 'default-tenant-uuid'
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('refresh_token', $result);

        $payload = $svc->validateToken($result['token']);
        $this->assertIsArray($payload);
        $this->assertSame('u1', $payload['user_id']);
    }
}

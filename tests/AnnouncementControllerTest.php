<?php

declare(strict_types=1);

namespace CoreFly\Tests;
use CoreFly\Services\JwtService;
use CoreFly\Controllers\AnnouncementController;
use CoreFly\Utils\Database;

class AnnouncementControllerTest extends BaseTestCase
{
    public function setUp(): void
    {
        // Seed one announcement
        $db = Database::getInstance();
        $db->connect('default');
        $db->seedTable('announcements', [[
            'id' => bin2hex(random_bytes(16)),
            'tenant_id' => 'default-tenant-uuid',
            'title' => 'Test Duyuru',
            'content' => 'İçerik',
            'author_id' => 'u1',
            'is_pinned' => 1,
            'priority' => 'high',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]]);
    }

    public function testIndexReturnsSuccessJson(): void
    {
        $svc = new JwtService();
        $token = $svc->generateToken([
            'user_id' => 'u1',
            'role' => 'tenant-admin-role',
            'tenant_id' => 'default-tenant-uuid'
        ])['token'];

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $ctrl = new AnnouncementController();
        $json = $ctrl->index();
        $data = json_decode($json, true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('items', $data['data']);
        $this->assertGreaterThanOrEqual(1, count($data['data']['items']));
    }
}

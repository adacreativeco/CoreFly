<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/BaseTestCase.php';

use CoreFly\Controllers\TaskController;
use CoreFly\Models\User;
use CoreFly\Utils\Database;

class NormalUserTest extends \CoreFly\Tests\BaseTestCase
{
    private $tenantId;
    private $userId;
    private $otherUserId;

    public function setUp(): void
    {
        $db = Database::getInstance();
        
        // 1. Create Tenant
        $this->tenantId = 'test-tenant-user';
        $db->exec("INSERT INTO tenants (id, name, domain, status, settings) VALUES ('{$this->tenantId}', 'User Corp', 'user.test.com', 'active', '{}')");

        // 2. Create Roles
        $db->exec("INSERT OR IGNORE INTO roles (id, tenant_id, name) VALUES ('user-role', '{$this->tenantId}', 'Normal User')");
        // Permissions for Normal User: tasks:read, tasks:update (own), tasks:create (maybe? no, per requirements "Yapamaz: Başkasına görev atayamaz... Proje oluşturamaz... Görev silemez")
        // Wait, "Kendisine verilen görevleri görüntüleme... Görev durumunu değiştirme"
        // So they need tasks:read and tasks:update (limited).
        // But they CANNOT delete.
        $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES ('user-role', 'tasks:read')");
        $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES ('user-role', 'tasks:update')");

        // 3. Create Users
        $this->userId = 'normal-user-1';
        $user = new User([
            'id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'username' => 'user1',
            'email' => 'user1@test.com',
            'first_name' => 'Normal',
            'last_name' => 'User',
            'role_id' => 'user-role',
            'status' => 'active'
        ]);
        $user->save();

        $this->otherUserId = 'other-user-1';
        // ... create other user ...
    }

    private function injectContext($controller, $userId = null)
    {
        $userId = $userId ?? $this->userId;
        
        $reflection = new ReflectionClass($controller);
        
        $userProp = $reflection->getProperty('currentUser');
        $userProp->setValue($controller, [
            'user_id' => $userId,
            'role' => 'user-role',
            'tenant_id' => $this->tenantId
        ]);

        $tenantProp = $reflection->getProperty('currentTenant');
        $tenantProp->setValue($controller, $this->tenantId);
        
        Database::getInstance()->setCurrentTenant($this->tenantId);
    }

    public function testTaskAccess()
    {
        echo "Testing Task Access...\n";
        
        // Create tasks directly in DB
        $db = Database::getInstance();
        $db->exec("INSERT INTO tasks (id, tenant_id, title, assigned_to, status) VALUES ('task-own', '{$this->tenantId}', 'My Task', '{$this->userId}', 'todo')");
        $db->exec("INSERT INTO tasks (id, tenant_id, title, assigned_to, status) VALUES ('task-other', '{$this->tenantId}', 'Other Task', '{$this->otherUserId}', 'todo')");

        $controller = new class extends TaskController {
            protected function authenticate(): bool { return true; }
        };
        $this->injectContext($controller);

        // Test Index - Should only see own tasks?
        // Requirement: "Kendisine verilen görevleri görüntüleme... Departman geneli görev göremez"
        // But "Kendisinin dahil olduğu projelerdeki görevleri görebilir"
        // Let's verify basic list logic. Currently TaskController::index shows ALL tasks unless filtered.
        // We need to update TaskController to filter by assigned_to = current_user_id IF role is normal user.
        
        $response = $controller->index();
        $data = json_decode($response, true);
        
        // Check items
        $items = $data['data']['items'];
        $ids = array_column($items, 'id');
        
        // We expect to see 'task-own'
        // We expect NOT to see 'task-other' (unless they are in same project, but here no project)
        
        $foundOwn = in_array('task-own', $ids);
        $foundOther = in_array('task-other', $ids);
        
        echo "Found Own: " . ($foundOwn ? 'YES' : 'NO') . "\n";
        echo "Found Other: " . ($foundOther ? 'YES' : 'NO') . "\n";
        
        if ($foundOther) {
             echo "FAIL: Normal user sees other people's tasks!\n";
        } else {
             echo "PASS: Normal user sees only own tasks.\n";
        }
    }
    
    public function run() {
        $this->setUp();
        $this->testTaskAccess();
    }
}

$test = new NormalUserTest();
$test->run();

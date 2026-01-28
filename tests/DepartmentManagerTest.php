<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/BaseTestCase.php';

use CoreFly\Controllers\AnnouncementController;
use CoreFly\Models\Announcement;
use CoreFly\Models\User;
use CoreFly\Utils\Database;

/**
 * @property string $department_id
 */
class DepartmentManagerTest extends \CoreFly\Tests\BaseTestCase
{
    private $tenantId;
    private $deptId;
    private $managerUserId;
    private $managerRoleId;

    public function setUp(): void
    {
        $db = Database::getInstance();
        
        // 1. Create Tenant
        $this->tenantId = 'test-tenant-dept';
        $db->exec("INSERT INTO tenants (id, name, domain, status, settings) VALUES ('{$this->tenantId}', 'Dept Corp', 'dept.test.com', 'active', '{}')");

        // 2. Create Department
        $this->deptId = 'dept-it';
        $db->exec("INSERT INTO departments (id, tenant_id, name) VALUES ('{$this->deptId}', '{$this->tenantId}', 'IT')");

        // 3. Create Dept Manager User
        $this->managerUserId = 'user-manager';
        $this->managerRoleId = 'department-manager-role';
        
        // Ensure Role Exists
        $db->exec("INSERT OR IGNORE INTO roles (id, tenant_id, name) VALUES ('{$this->managerRoleId}', '{$this->tenantId}', 'Dept Manager')");
        
        // Assign permissions
        $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES ('{$this->managerRoleId}', 'announcements:read')");
        $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES ('{$this->managerRoleId}', 'announcements:create')");

        $user = new User([
            'id' => $this->managerUserId,
            'tenant_id' => $this->tenantId,
            'department_id' => $this->deptId,
            'username' => 'manager',
            'email' => 'manager@test.com',
            'first_name' => 'Manager',
            'last_name' => 'User',
            'role_id' => $this->managerRoleId,
            'status' => 'active'
        ]);
        $user->save();
    }

    private function injectContext($controller)
    {
        $reflection = new ReflectionClass($controller);
        
        $userProp = $reflection->getProperty('currentUser');
        $userProp->setAccessible(true);
        $userProp->setValue($controller, [
            'user_id' => $this->managerUserId,
            'role' => $this->managerRoleId,
            'department' => $this->deptId,
            'tenant_id' => $this->tenantId
        ]);

        $tenantProp = $reflection->getProperty('currentTenant');
        $tenantProp->setAccessible(true);
        $tenantProp->setValue($controller, $this->tenantId);
        
        Database::getInstance()->setCurrentTenant($this->tenantId);
    }

    public function testAnnouncementScope()
    {
        echo "Testing Announcement Scope...\n";
        
        // 1. Create Announcement via Controller (should have dept_id)
        $controller = new class extends AnnouncementController {
            public $mockInput = [];
            protected function getJsonInput(): array { return $this->mockInput; }
            protected function getRequestData(): array { return $this->mockInput; }
            protected function authenticate(): bool { return true; }
            protected function requireCsrfIfProd(): void {}
        };
        
        $this->injectContext($controller);
        $controller->mockInput = [
            'title' => 'Dept Announcement',
            'content' => 'For IT only',
            'target_type' => 'all'
        ];

        $response = $controller->create();
        $data = json_decode($response, true);
        
        $this->assertTrue($data['success']);
        $announcementId = $data['data']['id'];
        
        // Verify in DB
        $announcement = Announcement::find($announcementId);
        // Fix: Access via dynamic property, IDE might complain without annotation but runtime works
        $this->assertSame($this->deptId, $announcement->department_id, "Department ID should be forced to user's dept");
        echo "Announcement created with correct Department ID.\n";

        // 2. Create another announcement in DIFFERENT dept (simulate existing data)
        $otherDeptId = 'dept-hr';
        $db = Database::getInstance();
        $db->exec("INSERT INTO announcements (id, tenant_id, department_id, title, content, author_id) VALUES ('ann-hr', '{$this->tenantId}', '$otherDeptId', 'HR Ann', 'Content', 'other')");

        // 3. List Announcements
        $listResponse = $controller->index();
        $listData = json_decode($listResponse, true);
        
        $items = $listData['data']['items'];
        $ids = array_column($items, 'id');
        
        $this->assertContains($announcementId, $ids, "Should see own dept announcement");
        $this->assertNotContains('ann-hr', $ids, "Should NOT see other dept announcement");
        
        echo "Announcement listing correctly scoped.\n";
    }

    public function run()
    {
        $this->setUp();
        try {
            $this->testAnnouncementScope();
            echo "All tests passed!\n";
        } catch (Exception $e) {
            echo "Test Failed: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
        }
    }
}

$test = new DepartmentManagerTest();
$test->run();

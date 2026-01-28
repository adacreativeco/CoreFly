<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/BaseTestCase.php';

use CoreFly\Controllers\DepartmentController;
use CoreFly\Controllers\RoleController;
use CoreFly\Models\User;
use CoreFly\Models\Role;
use CoreFly\Utils\Database;

class TenantAdminTest extends \CoreFly\Tests\BaseTestCase
{
    private $tenantId;
    private $adminUserId;
    private $adminRoleId;

    public function setUp(): void
    {
        $db = Database::getInstance();
        
        // 1. Create a Tenant
        $this->tenantId = 'test-tenant-123';
        $db->exec("INSERT INTO tenants (id, name, domain, status, settings) VALUES ('{$this->tenantId}', 'Test Corp', 'test.corfly.com', 'active', '{}')");

        // 2. Create Tenant Admin Role
        $this->adminRoleId = 'tenant-admin-role';
        // Ensure it exists or create it
        $db->exec("INSERT OR IGNORE INTO roles (id, tenant_id, name, permissions) VALUES ('{$this->adminRoleId}', '{$this->tenantId}', 'Tenant Admin', '[\"*\"]')");

        // 3. Create Tenant Admin User
        $this->adminUserId = 'user-admin-123';
        $user = new User([
            'id' => $this->adminUserId,
            'tenant_id' => $this->tenantId,
            'username' => 'admin',
            'email' => 'admin@test.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $this->adminRoleId,
            'status' => 'active'
        ]);
        $user->setPassword('password123');
        $user->save();
    }

    private function injectContext($controller)
    {
        // Use reflection to set protected properties
        $reflection = new ReflectionClass($controller);
        
        $userProp = $reflection->getProperty('currentUser');
        $userProp->setValue($controller, [
            'user_id' => $this->adminUserId,
            'role' => $this->adminRoleId,
            'tenant_id' => $this->tenantId
        ]);

        $tenantProp = $reflection->getProperty('currentTenant');
        $tenantProp->setValue($controller, $this->tenantId);
        
        // Also update Database tenant context which Models use
        Database::getInstance()->setCurrentTenant($this->tenantId);
        
        // Mock permissions service if needed, but the role has "*" permissions so it should pass
    }

    public function testDepartmentManagement()
    {
        echo "Testing Department Management...\n";
        
        $controller = new DepartmentController();
        $this->injectContext($controller);

        // Simulate POST request data
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $input = json_encode([
            'name' => 'IT Department',
            'description' => 'Information Technology',
            'manager_id' => $this->adminUserId
        ]);
        
        // Mocking getJsonInput via php://input is hard in CLI without external libs
        // So we will override getJsonInput using reflection or a mock class if possible
        // Or simpler: We modify BaseController to accept input injection for testing? 
        // No, let's use a partial mock or subclass for testing.
        
        // Let's create a TestableDepartmentController on the fly
        $testController = new class extends DepartmentController {
            public $mockInput = [];
            protected function getJsonInput(): array {
                return $this->mockInput;
            }
            // Bypass Auth for test simplicity as we injected context
            protected function authenticate(): bool { return true; }
        };
        
        $this->injectContext($testController);
        $testController->mockInput = [
            'name' => 'IT Department',
            'description' => 'Information Technology',
            'manager_id' => $this->adminUserId
        ];

        $response = $testController->store();
        $data = json_decode($response, true);
        
        $this->assertTrue($data['success']);
        $this->assertSame('IT Department', $data['data']['name']);
        
        $deptId = $data['data']['id'];
        echo "Department created: $deptId\n";

        // DEBUG: List all departments
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM departments");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "All Departments in DB: " . json_encode($all) . "\n";

        // Test Read
        $readController = new class extends DepartmentController {
            protected function authenticate(): bool { return true; }
        };
        $this->injectContext($readController);
        
        $response = $readController->show($deptId);
        $data = json_decode($response, true);
        if (!isset($data['success'])) {
             echo "Response: " . $response . "\n";
        }
        $this->assertTrue($data['success']);
        $this->assertSame($deptId, $data['data']['id']);
        echo "Department read success.\n";
    }

    public function testRoleManagement()
    {
        echo "Testing Role Management...\n";
        
        $testController = new class extends RoleController {
            public $mockInput = [];
            protected function getJsonInput(): array {
                return $this->mockInput;
            }
            protected function authenticate(): bool { return true; }
        };
        
        $this->injectContext($testController);
        $testController->mockInput = [
            'name' => 'Support Staff',
            'permissions' => ['users:read', 'tickets:read']
        ];

        $response = $testController->store();
        $data = json_decode($response, true);
        
        $this->assertTrue($data['success']);
        $this->assertSame('Support Staff', $data['data']['name']);
        
        $roleId = $data['data']['id'];
        echo "Role created: $roleId\n";
        
        // Verify permissions are stored correctly
        $role = Role::find($roleId);
        $perms = $role->getPermissionList(); // Should return array of strings like "module:action"
        $this->assertTrue(in_array('users:read', $perms));
        echo "Role permissions verified.\n";
    }

    public function run()
    {
        $this->setUp();
        try {
            $this->testDepartmentManagement();
            $this->testRoleManagement();
            echo "All tests passed!\n";
        } catch (Exception $e) {
            echo "Test Failed: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
        }
    }
}

// Run the test
$test = new TenantAdminTest();
$test->run();

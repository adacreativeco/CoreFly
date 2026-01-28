<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenvClass = 'Dotenv\\Dotenv';
    if (class_exists($dotenvClass)) {
        $dotenv = $dotenvClass::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
} catch (Exception $e) {
    // Ignore
}

$baseUrl = 'http://localhost:8001';

function req($method, $path, $token = null, $data = null) {
    global $baseUrl;
    $url = $baseUrl . $path;
    
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n" . 
                        "Accept: application/json\r\n",
            'ignore_errors' => true
        ]
    ];

    if ($token) {
        $opts['http']['header'] .= "Authorization: Bearer $token\r\n";
    }

    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($opts);
    $result = file_get_contents($url, false, $context);
    
    $statusLine = $http_response_header[0] ?? 'HTTP/1.1 000 Fake';
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
    $status = intval($matches[1] ?? 0);

    return ['status' => $status, 'body' => json_decode($result, true), 'raw_body' => $result];
}

function assertStep($name, $condition, $details = '') {
    if ($condition) {
        echo "✅ [PASS] $name\n";
    } else {
        echo "❌ [FAIL] $name. $details\n";
        // exit(1); // Don't exit, try to continue to see all failures
    }
}

echo "Starting MASTER Verification Suite...\n";
echo "------------------------------------\n";

// 1. Login as Super Admin to get Token
echo "1. Authenticating...\n";
$loginRes = req('POST', '/api/auth/login', null, ['username' => 'admin', 'password' => 'password']);
if ($loginRes['status'] !== 200) {
    echo "CRITICAL: Login failed. Cannot proceed.\n";
    exit(1);
}
$token = $loginRes['body']['data']['token'];
echo "   Token acquired.\n";

// --- HR Module Test ---
echo "\n--- Testing HR Module ---\n";
// Create Department
$deptRes = req('POST', '/api/hr/departments', $token, [
    'name' => 'IT Department',
    'description' => 'Tech stuff'
]);
assertStep('Create Department', $deptRes['status'] === 200 || $deptRes['status'] === 201, "Status: " . $deptRes['status']);
$deptId = $deptRes['body']['data']['id'] ?? null;

if ($deptId) {
    // Create Employee
    $empRes = req('POST', '/api/hr/employees', $token, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe.' . time() . '@example.com',
        'department_id' => $deptId,
        'hire_date' => '2025-01-01',
        'status' => 'active',
        'salary' => 50000
    ]);
    assertStep('Create Employee', $empRes['status'] === 200 || $empRes['status'] === 201, "Status: " . $empRes['status']);
    $empId = $empRes['body']['data']['id'] ?? null;

    if ($empId) {
        // Create Leave Request
        // Note: Frontend uses POST /hr/leave-requests
        $leaveRes = req('POST', '/api/hr/leave-requests', $token, [
            'employee_id' => $empId,
            'type' => 'annual',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-05',
            'reason' => 'Vacation'
        ]);
        assertStep('Create Leave Request', $leaveRes['status'] === 200 || $leaveRes['status'] === 201, "Status: " . $leaveRes['status']);

        // Create Payroll
        $payrollRes = req('POST', '/api/hr/payrolls', $token, [
            'employee_id' => $empId,
            'period' => '2025-05',
            'base_salary' => 50000,
            'bonus' => 1000,
            'deductions' => 500
        ]);
        assertStep('Create Payroll', $payrollRes['status'] === 200 || $payrollRes['status'] === 201, "Status: " . $payrollRes['status'] . " Error: " . ($payrollRes['body']['error'] ?? ''));
    }
}

// --- Field Module Test ---
echo "\n--- Testing Field Module ---\n";
// Create Zone
$zoneRes = req('POST', '/api/field/zones', $token, [
    'name' => 'North Sector',
    'manager' => 'Jane Smith',
    'target' => '100 Visits',
    'status' => 'active'
]);
assertStep('Create Field Zone', $zoneRes['status'] === 200 || $zoneRes['status'] === 201, "Status: " . $zoneRes['status'] . " Error: " . ($zoneRes['body']['error'] ?? ''));

// List Teams (Read-only check as there is no create UI)
$teamRes = req('GET', '/api/field/teams', $token);
assertStep('List Field Teams', $teamRes['status'] === 200, "Status: " . $teamRes['status'] . " Error: " . ($teamRes['body']['error'] ?? ''));

// --- Projects Module Test ---
echo "\n--- Testing Projects Module ---\n";
$projRes = req('POST', '/api/projects', $token, [
    'title' => 'New Website Launch',
    'project_type' => 'Web Development', // Required field
    'status' => 'planning',
    'start_date' => '2025-02-01',
    'priority' => 'high'
]);
assertStep('Create Project', $projRes['status'] === 200 || $projRes['status'] === 201, "Status: " . $projRes['status'] . " Error: " . ($projRes['body']['error'] ?? ''));

// --- Messaging Module Test ---
echo "\n--- Testing Messaging Module ---\n";
// Create Group
$groupRes = req('POST', '/api/messages/groups', $token, [
    'name' => 'Dev Team',
    'members' => 'user1@example.com, user2@example.com' // Frontend sends string usually split by comma
]);
assertStep('Create Message Group', $groupRes['status'] === 200 || $groupRes['status'] === 201, "Status: " . $groupRes['status'] . " Error: " . ($groupRes['body']['error'] ?? ''));
// (Note: This might fail if user emails don't exist, but let's see API response)

echo "------------------------------------\n";
echo "✅ MASTER VERIFICATION COMPLETED\n";

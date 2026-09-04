<?php

function request($method, $url, $data = null, $token = null) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => $method,
            'ignore_errors' => true
        ]
    ];
    
    if ($token) {
        $options['http']['header'] .= "Authorization: Bearer $token\r\n";
    }
    
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    $http_response_header = $http_response_header ?? [];
    $code = 0;
    if (!empty($http_response_header)) {
        preg_match('#HTTP/\d\.\d (\d+)#', $http_response_header[0], $matches);
        $code = intval($matches[1]);
    }
    
    $body = json_decode($result, true);
    if ($body === null && $result !== "") {
        echo "DEBUG: Raw response: $result\n";
    }

    return ['code' => $code, 'body' => $body];
}

$baseUrl = 'http://localhost:8001/api';
$rand = rand(1000, 9999);
$email = "hr_test_$rand@example.com";

echo "1. Registering User ($email)...\n";
$res = request('POST', "$baseUrl/auth/register", [
    'email' => $email,
    'password' => 'password123',
    'full_name' => 'HR Admin',
    'tenant_name' => "HR Tenant $rand"
]);

if ($res['code'] !== 200) {
    echo "Register failed\n";
    print_r($res);
    exit;
}

$token = $res['body']['token'];
$tenantId = $res['body']['user']['tenant_id'];
$userId = $res['body']['user']['id'];
echo "Login Successful. Tenant: $tenantId\n";

// 2. Create Department
echo "\n2. Creating Department (Engineering)...\n";
$res = request('POST', "$baseUrl/hr/$tenantId/departments", [
    'name' => 'Engineering',
    'code' => 'ENG',
    'description' => 'Software Engineering Dept',
    'manager_id' => $userId
], $token);

if ($res['code'] !== 201) {
    echo "Create Department failed\n";
    print_r($res);
    exit;
}
$deptId = $res['body']['data']['id'];
echo "Department Created: $deptId\n";

// 3. Create Position
echo "\n3. Creating Position (Senior Dev)...\n";
$res = request('POST', "$baseUrl/hr/$tenantId/positions", [
    'title' => 'Senior Developer',
    'code' => 'SEN-DEV',
    'department_id' => $deptId,
    'min_salary' => 80000,
    'max_salary' => 120000
], $token);

if ($res['code'] !== 201) {
    echo "Create Position failed\n";
    print_r($res);
    exit;
}
$posId = $res['body']['data']['id'];
echo "Position Created: $posId\n";

// 4. Create Employee Profile
echo "\n4. Creating Employee Profile...\n";
$res = request('POST', "$baseUrl/hr/$tenantId/employees", [
    'user_id' => $userId,
    'employee_number' => "EMP-$rand",
    'department_id' => $deptId,
    'position_id' => $posId,
    'employment_type' => 'full_time',
    'salary' => 95000
], $token);

if ($res['code'] !== 201) {
    echo "Create Employee failed\n";
    print_r($res);
    exit;
}
$empId = $res['body']['data']['id'];
echo "Employee Created: $empId\n";

// 5. Verify Employee Get
echo "\n5. Verifying Employee Profile...\n";
$res = request('GET', "$baseUrl/hr/$tenantId/employees/$empId", null, $token);
if ($res['code'] === 200 && $res['body']['data']['id'] === $empId) {
    echo "Verification Successful!\n";
} else {
    echo "Verification Failed\n";
    print_r($res);
}

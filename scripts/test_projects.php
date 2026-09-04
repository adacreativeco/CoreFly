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
    
    return ['code' => $code, 'body' => json_decode($result, true)];
}

$baseUrl = 'http://localhost:8001/api';
$rand = rand(1000, 9999);
$email = "project_mgr_$rand@example.com";

echo "1. Registering Manager ($email)...\n";
$res = request('POST', "$baseUrl/auth/register", [
    'email' => $email,
    'password' => 'password123',
    'full_name' => 'Project Manager',
    'tenant_name' => "Project Tenant $rand"
]);

if ($res['code'] !== 200) {
    echo "Register failed\n";
    print_r($res);
    exit;
}

$token = $res['body']['token'];
$tenantId = $res['body']['user']['tenant_id'];
$managerId = $res['body']['user']['id'];
echo "Login Successful. Tenant: $tenantId\n";

// 2. Create Project
echo "\n2. Creating Project (Website Redesign)...\n";
$res = request('POST', "$baseUrl/projects/$tenantId", [
    'name' => 'Website Redesign',
    'code' => "WEB-$rand",
    'description' => 'Redesign corporate website',
    'budget' => 50000
], $token);

if ($res['code'] !== 201) {
    echo "Create Project failed\n";
    print_r($res);
    exit;
}
$projectId = $res['body']['data']['id'];
echo "Project Created: $projectId\n";

// 3. Create Task
echo "\n3. Creating Task (Design Mockups)...\n";
$res = request('POST', "$baseUrl/projects/$tenantId/$projectId/tasks", [
    'title' => 'Design Mockups',
    'description' => 'Create Figma mockups for homepage',
    'priority' => 'high',
    'assignee_id' => $managerId // Assign to self for test
], $token);

if ($res['code'] !== 201) {
    echo "Create Task failed\n";
    print_r($res);
    exit;
}
$taskId = $res['body']['data']['id'];
echo "Task Created: $taskId\n";

// 4. Update Task Status
echo "\n4. Updating Task Status to 'in_progress'...\n";
$res = request('PATCH', "$baseUrl/projects/$tenantId/$projectId/tasks/$taskId", [
    'status' => 'in_progress'
], $token);

if ($res['code'] === 200 && $res['body']['data']['status'] === 'in_progress') {
    echo "Task Updated Successfully!\n";
} else {
    echo "Task Update Failed\n";
    print_r($res);
}

// 5. Verify Project List
echo "\n5. Verifying Project List...\n";
$res = request('GET', "$baseUrl/projects/$tenantId", null, $token);
if ($res['code'] === 200 && count($res['body']['data']) > 0) {
    echo "Project List Verified!\n";
} else {
    echo "Project List Failed\n";
    print_r($res);
}

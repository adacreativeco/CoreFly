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
$email = "notify_user_$rand@example.com";

echo "1. Registering User ($email)...\n";
$res = request('POST', "$baseUrl/auth/register", [
    'email' => $email,
    'password' => 'password123',
    'full_name' => 'Notify User',
    'tenant_name' => "Notify Tenant $rand"
]);

if ($res['code'] !== 200) {
    echo "Register failed\n";
    print_r($res);
    exit;
}

$token = $res['body']['token'];
$tenantId = $res['body']['user']['tenant_id'];
echo "User Registered. Tenant: $tenantId\n";

// 2. Create Test Notification
echo "\n2. Creating Test Notification...\n";
$res = request('POST', "$baseUrl/notifications/$tenantId/test", [
    'title' => 'Welcome!',
    'content' => 'Welcome to CoreFly.'
], $token);

if ($res['code'] !== 201) {
    echo "Create Notification failed\n";
    print_r($res);
    exit;
}
$notifId = $res['body']['data']['id'];
echo "Notification Created: $notifId\n";

// 3. List Notifications
echo "\n3. Listing Notifications...\n";
$res = request('GET', "$baseUrl/notifications/$tenantId", null, $token);
if ($res['code'] === 200 && count($res['body']['data']) > 0) {
    echo "Notifications Listed: " . count($res['body']['data']) . "\n";
} else {
    echo "List Notifications failed\n";
    print_r($res);
}

// 4. Mark as Read
echo "\n4. Marking Notification as Read...\n";
$res = request('PATCH', "$baseUrl/notifications/$tenantId/$notifId/read", [], $token);
if ($res['code'] === 200) {
    echo "Marked as Read Successfully.\n";
} else {
    echo "Mark as Read failed\n";
    print_r($res);
}

// 5. Create Test Audit Log
echo "\n5. Creating Test Audit Log...\n";
$res = request('POST', "$baseUrl/audit/$tenantId/test", [], $token);
if ($res['code'] === 201) {
    echo "Audit Log Created.\n";
} else {
    echo "Create Audit Log failed\n";
    print_r($res);
}

// 6. List Audit Logs
echo "\n6. Listing Audit Logs...\n";
$res = request('GET', "$baseUrl/audit/$tenantId", null, $token);
if ($res['code'] === 200 && count($res['body']['data']) > 0) {
    echo "Audit Logs Listed: " . count($res['body']['data']) . "\n";
} else {
    echo "List Audit Logs failed\n";
    print_r($res);
}

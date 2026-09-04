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
$emailA = "userA_$rand@example.com";
$emailB = "userB_$rand@example.com";

echo "1. Registering User A ($emailA)...\n";
$resA = request('POST', "$baseUrl/auth/register", [
    'email' => $emailA,
    'password' => 'password123',
    'full_name' => 'User A',
    'tenant_name' => "Messaging Tenant $rand"
]);

if ($resA['code'] !== 200) {
    echo "Register User A failed\n";
    print_r($resA);
    exit;
}

$tokenA = $resA['body']['token'];
$tenantId = $resA['body']['user']['tenant_id'];
$userAId = $resA['body']['user']['id'];
echo "User A Registered. Tenant: $tenantId\n";

echo "\n2. Registering User B ($emailB) in same tenant...\n";
// Manually create User B linked to same tenant via register endpoint (assuming logic supports it or separate flow)
// Our register logic creates a new tenant if tenant_id not provided.
// Let's modify register logic or just create a user directly via model? 
// Actually AuthController.register supports passing 'tenant_id'.

$resB = request('POST', "$baseUrl/auth/register", [
    'email' => $emailB,
    'password' => 'password123',
    'full_name' => 'User B',
    'tenant_id' => $tenantId
]);

if ($resB['code'] !== 200) {
    echo "Register User B failed\n";
    print_r($resB);
    exit;
}
$tokenB = $resB['body']['token'];
$userBId = $resB['body']['user']['id'];
echo "User B Registered.\n";

// 3. User A starts conversation with User B
echo "\n3. User A starts conversation with User B...\n";
$res = request('POST', "$baseUrl/conversations/$tenantId", [
    'type' => 'direct',
    'recipient_id' => $userBId,
    'title' => 'Direct Chat'
], $tokenA);

if ($res['code'] !== 201) {
    echo "Create Conversation failed\n";
    print_r($res);
    exit;
}
$convId = $res['body']['data']['id'];
echo "Conversation Created: $convId\n";

// 4. User A sends message
echo "\n4. User A sends message...\n";
$res = request('POST', "$baseUrl/conversations/$tenantId/$convId/messages", [
    'content' => 'Hello User B!'
], $tokenA);

if ($res['code'] !== 201) {
    echo "Send Message failed\n";
    print_r($res);
    exit;
}
echo "Message Sent by User A.\n";

// 5. User B lists messages
echo "\n5. User B lists messages...\n";
$res = request('GET', "$baseUrl/conversations/$tenantId/$convId/messages", null, $tokenB);

if ($res['code'] === 200 && count($res['body']['data']) > 0) {
    $msg = $res['body']['data'][0];
    echo "User B received: " . $msg['content'] . "\n";
} else {
    echo "List Messages failed\n";
    print_r($res);
}

// 6. User B replies
echo "\n6. User B replies...\n";
$res = request('POST', "$baseUrl/conversations/$tenantId/$convId/messages", [
    'content' => 'Hi User A, nice to meet you!'
], $tokenB);

if ($res['code'] === 201) {
    echo "Reply Sent by User B.\n";
} else {
    echo "Reply failed\n";
    print_r($res);
}

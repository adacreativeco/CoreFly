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
    
    // Parse headers for status code
    $http_response_header = $http_response_header ?? [];
    $code = 0;
    if (!empty($http_response_header)) {
        preg_match('#HTTP/\d\.\d (\d+)#', $http_response_header[0], $matches);
        $code = intval($matches[1]);
    }
    
    echo "DEBUG: URL: $url\n";
    echo "DEBUG: Code: $code\n";
    echo "DEBUG: Result: '$result'\n";
    
    return ['code' => $code, 'body' => json_decode($result, true)];
}

$baseUrl = 'http://localhost:8000/api';

echo "1. Registering User...\n";
$res = request('POST', "$baseUrl/auth/register", [
    'email' => 'test3@example.com',
    'password' => 'password123',
    'full_name' => 'Test User 3',
    'tenant_name' => 'Test Tenant 3'
]);
// print_r($res);

if ($res['code'] === 200 && isset($res['body']['token'])) {
    $token = $res['body']['token'];
    $tenantId = $res['body']['user']['tenant_id'];
    echo "Login Successful. Token: " . substr($token, 0, 10) . "...\n";
    
    echo "\n3. Creating Post...\n";
    $res = request('POST', "$baseUrl/workspace/$tenantId/posts", [
        'title' => 'Test Post',
        'content' => 'Content here',
        'post_type' => 'general'
    ], $token);
    // print_r($res);
    
    if ($res['code'] === 201) {
        $postId = $res['body']['data']['id'];
        echo "Post Created: $postId\n";
        
        echo "\n4. Getting Feed...\n";
        $res = request('GET', "$baseUrl/workspace/$tenantId/feed", null, $token);
        // print_r($res);
        
        echo "\n5. Adding Comment...\n";
        $res = request('POST', "$baseUrl/workspace/$tenantId/posts/$postId/comments", [
            'content' => 'Nice post!'
        ], $token);
        // print_r($res);
    }
} else {
    echo "Login/Register failed or token missing.\n";
    print_r($res);
}

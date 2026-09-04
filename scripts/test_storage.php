<?php

function request($method, $url, $data = null, $token = null, $isFile = false) {
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
    
    if ($isFile && $data) {
        // Handle Multipart manually for PHP stream
        $boundary = "------------------------" . uniqid();
        $options['http']['header'] = "Authorization: Bearer $token\r\n";
        $options['http']['header'] .= "Content-Type: multipart/form-data; boundary=$boundary\r\n";
        
        $body = "";
        
        // Add File
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$data['filename']}\"\r\n";
        $body .= "Content-Type: text/plain\r\n\r\n";
        $body .= $data['content'] . "\r\n";
        
        // Add other fields
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"entity_type\"\r\n\r\n";
        $body .= "test_script\r\n";
        
        $body .= "--$boundary--\r\n";
        
        $options['http']['content'] = $body;
    } elseif ($data) {
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
    
    // For download (not JSON)
    if ($method === 'GET' && strpos($url, '/files/') !== false && strpos($url, '/files') !== false && substr($url, -6) !== '/files') {
        return ['code' => $code, 'body' => $result]; // Raw body
    }
    
    return ['code' => $code, 'body' => json_decode($result, true)];
}

$baseUrl = 'http://localhost:8001/api';
$rand = rand(1000, 9999);
$email = "storage_user_$rand@example.com";

echo "1. Registering User ($email)...\n";
$res = request('POST', "$baseUrl/auth/register", [
    'email' => $email,
    'password' => 'password123',
    'full_name' => 'Storage User',
    'tenant_name' => "Storage Tenant $rand"
]);

if ($res['code'] !== 200) {
    echo "Register failed\n";
    print_r($res);
    exit;
}

$token = $res['body']['token'];
$tenantId = $res['body']['user']['tenant_id'];
echo "User Registered. Tenant: $tenantId\n";

// 2. Upload File
echo "\n2. Uploading File...\n";
$fileContent = "This is a test file content for tenant $tenantId.";
$res = request('POST', "$baseUrl/storage/$tenantId/upload", [
    'filename' => 'test_file.txt',
    'content' => $fileContent
], $token, true);

if ($res['code'] !== 201) {
    echo "Upload failed\n";
    print_r($res);
    exit;
}
$fileId = $res['body']['data']['id'];
$fileName = $res['body']['data']['file_name'];
echo "File Uploaded: $fileId ($fileName)\n";

// 3. List Files
echo "\n3. Listing Files...\n";
$res = request('GET', "$baseUrl/storage/$tenantId/files", null, $token);
if ($res['code'] === 200 && count($res['body']['data']) > 0) {
    echo "Files Listed: " . count($res['body']['data']) . "\n";
} else {
    echo "List Files failed\n";
    print_r($res);
}

// 4. Download File
echo "\n4. Downloading File...\n";
$res = request('GET', "$baseUrl/storage/$tenantId/files/$fileId", null, $token);
if ($res['code'] === 200 && $res['body'] === $fileContent) {
    echo "Download Verified! Content matches.\n";
} else {
    echo "Download Failed or Content Mismatch\n";
    // echo "Got: " . $res['body'] . "\n";
    print_r($res);
}

// 5. Delete File
echo "\n5. Deleting File...\n";
$res = request('DELETE', "$baseUrl/storage/$tenantId/files/$fileId", null, $token);
if ($res['code'] === 200) {
    echo "File Deleted Successfully.\n";
} else {
    echo "Delete Failed\n";
    print_r($res);
}

// Verify deletion
$res = request('GET', "$baseUrl/storage/$tenantId/files/$fileId", null, $token);
if ($res['code'] === 404) {
    echo "Deletion Verified (404 Not Found).\n";
} else {
    echo "File still exists?\n";
}

<?php
$url = 'http://localhost:8000/api/auth/login';
$data = [
    'email' => 'admin@demo.com',
    'password' => 'password123'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "HTTP Code: " . $http_response_header[0] . "\n";
echo "Response Body:\n";
echo $result . "\n";

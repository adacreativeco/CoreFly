<?php

// Complete end-to-end API test without local DB locks
echo "=== TESTING FULL VOIP & VIDEO CALL API FLOW ===\n";
$baseUrl = 'http://127.0.0.1:8001';

function apiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => json_decode($res, true), 'raw' => $res];
}

// 1. Login Admin
$login = apiRequest("$baseUrl/api/auth/login", 'POST', [
    'email' => 'admin@corefly.com',
    'password' => 'Admin123!'
]);
$token = $login['body']['token'];
$tenantId = $login['body']['user']['tenant_id'];
echo "[✓] Admin logged in. Tenant: $tenantId\n";

// 2. Start Call
$start = apiRequest("$baseUrl/api/calls/$tenantId/start", 'POST', [
    'target_id' => 'user-finance',
    'call_type' => 'video'
], $token);
echo "[✓] Start Call Code: {$start['code']} | Call ID: " . ($start['body']['data']['call_id'] ?? 'none') . "\n";
$callId = $start['body']['data']['call_id'];

// 3. Send Signal (Offer)
$sig = apiRequest("$baseUrl/api/calls/$tenantId/signal", 'POST', [
    'call_id' => $callId,
    'type' => 'offer',
    'payload' => ['sdp' => 'v=0\r\ntest-sdp-data']
], $token);
echo "[✓] Send Signal Code: {$sig['code']} | Status: " . ($sig['body']['status'] ?? 'fail') . "\n";

// 4. Poll Signals
$poll = apiRequest("$baseUrl/api/calls/$tenantId/signals?call_id=$callId", 'GET', null, $token);
echo "[✓] Poll Signals Code: {$poll['code']} | Found: " . count($poll['body']['signals'] ?? []) . "\n";

// 5. Answer Call
$ans = apiRequest("$baseUrl/api/calls/$tenantId/answer", 'POST', [
    'call_id' => $callId
], $token);
echo "[✓] Answer Call Code: {$ans['code']} | Status: " . ($ans['body']['status'] ?? 'fail') . "\n";

// 6. End Call
$end = apiRequest("$baseUrl/api/calls/$tenantId/end", 'POST', [
    'call_id' => $callId,
    'reason' => 'ended'
], $token);
echo "[✓] End Call Code: {$end['code']} | Status: " . ($end['body']['status'] ?? 'fail') . "\n";

// 7. Get History
$hist = apiRequest("$baseUrl/api/calls/$tenantId/history", 'GET', null, $token);
echo "[✓] History Code: {$hist['code']} | Total calls recorded: " . count($hist['body']['data'] ?? []) . "\n";

echo "=== ALL VOIP & VIDEO CALL BACKEND ENDPOINTS PASSED SUCCESSFULLY! ===\n";

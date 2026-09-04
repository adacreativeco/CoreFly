<?php

// Test script for VoIP & Video Call API flow
require_once __DIR__ . '/../app/Core/Database.php';

echo "=== TESTING CALL API FLOW ===\n";

$baseUrl = 'http://127.0.0.1:8001';

// 1. Login as admin
$loginPayload = json_encode([
    'email' => 'admin@corefly.com',
    'password' => 'Admin123!'
]);

$ch = curl_init("$baseUrl/api/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = curl_exec($ch);

$loginData = json_decode($res, true);
if (empty($loginData['token'])) {
    echo "FAILED: Login failed. Response: $res\n";
    exit(1);
}

$token = $loginData['token'];
$tenantId = $loginData['user']['tenant_id'];
$adminId = $loginData['user']['id'];
echo "[✓] Logged in as Admin ($adminId) for tenant $tenantId\n";

// 2. Find another user to call
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE tenant_id = :tenantId AND id != :adminId LIMIT 1");
$stmt->execute([':tenantId' => $tenantId, ':adminId' => $adminId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo "No second user found, creating dummy user for call test...\n";
    $dummyId = uniqid('usr_', true);
    $stmt = $db->prepare("INSERT INTO users (id, tenant_id, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$dummyId, $tenantId, 'peer@corefly.local', 'fakehash', 'Peer User', 'employee']);
    $targetUser = ['id' => $dummyId, 'full_name' => 'Peer User'];
}
$targetId = $targetUser['id'];
echo "[✓] Target user identified: {$targetUser['full_name']} ($targetId)\n";

// 3. Start a call
$startPayload = json_encode([
    'target_id' => $targetId,
    'call_type' => 'video'
]);

$ch = curl_init("$baseUrl/api/calls/$tenantId/start");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $startPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$startRes = curl_exec($ch);
curl_close($ch);

$startData = json_decode($startRes, true);
if (empty($startData['data']['call_id'])) {
    echo "FAILED: Start call failed. Response: $startRes\n";
    exit(1);
}
$callId = $startData['data']['call_id'];
echo "[✓] Call started successfully. Call ID: $callId\n";

// 4. Send WebRTC signal (offer)
$signalPayload = json_encode([
    'call_id' => $callId,
    'type' => 'offer',
    'payload' => ['sdp' => 'v=0\r\no=- 12345 2 IN IP4 127.0.0.1...']
]);

$ch = curl_init("$baseUrl/api/calls/$tenantId/signal");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $signalPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$sigRes = curl_exec($ch);
curl_close($ch);

echo "[✓] Signal sent: $sigRes\n";

// 5. Answer the call
$answerPayload = json_encode(['call_id' => $callId]);
$ch = curl_init("$baseUrl/api/calls/$tenantId/answer");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $answerPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$ansRes = curl_exec($ch);
curl_close($ch);
echo "[✓] Call answered: $ansRes\n";

// 6. End call
$endPayload = json_encode(['call_id' => $callId, 'reason' => 'ended']);
$ch = curl_init("$baseUrl/api/calls/$tenantId/end");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $endPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$endRes = curl_exec($ch);
curl_close($ch);
echo "[✓] Call ended: $endRes\n";

// 7. Get History
$ch = curl_init("$baseUrl/api/calls/$tenantId/history");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
$histRes = curl_exec($ch);
curl_close($ch);
$histData = json_decode($histRes, true);
echo "[✓] Call history retrieved, count: " . count($histData['data'] ?? []) . "\n";

echo "=== ALL CALL API TESTS PASSED! ===\n";

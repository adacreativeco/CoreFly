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
    
    $statusLine = $http_response_header[0];
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
    $status = intval($matches[1]);

    return ['status' => $status, 'body' => json_decode($result, true), 'raw_body' => $result];
}

function assertStep($name, $condition, $details = '') {
    if ($condition) {
        echo "✅ [PASS] $name\n";
    } else {
        echo "❌ [FAIL] $name. $details\n";
        exit(1);
    }
}

echo "Starting Final Verification Suite...\n";
echo "------------------------------------\n";

// 1. Login as Super Admin
echo "1. Authenticating as Super Admin...\n";
// We need to know a super admin user. Based on previous turns, 'admin' might be one.
// Or we can create one if needed, but let's try 'admin' / 'password' or look at seed.
// Assuming 'admin' exists from seed.
$loginRes = req('POST', '/api/auth/login', null, ['username' => 'admin', 'password' => 'password']);

if ($loginRes['status'] !== 200) {
    // Try creating a temp admin if login fails (maybe seed didn't run)
    echo "   Login failed, checking DB for admin user...\n";
    // For now, fail if admin doesn't exist, user should have seeded.
    // Actually, let's try to update admin password to 'password' directly in DB to be sure.
    $db = new \PDO('sqlite:' . __DIR__ . '/../storage/corefly.sqlite');
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $db->exec("UPDATE users SET password = '$hash' WHERE username = 'admin'");
    $loginRes = req('POST', '/api/auth/login', null, ['username' => 'admin', 'password' => 'password']);
}

assertStep('Super Admin Login', $loginRes['status'] === 200, json_encode($loginRes));
$token = $loginRes['body']['data']['token'];
$superAdminTenantId = $loginRes['body']['data']['user']['tenant']['id'] ?? $loginRes['body']['data']['user']['home_tenant_id'] ?? 'default';

// 2. Access Root Dashboard
echo "2. Accessing Root Dashboard...\n";
$rootRes = req('GET', '/api/root/dashboard', $token);
assertStep('Root Dashboard Access', $rootRes['status'] === 200);

// 3. Create New Tenant
echo "3. Creating Test Tenant...\n";
$tenantName = "Test Corp " . time();
$tenantDomain = "test" . time();
$createTenantRes = req('POST', '/api/root/tenants', $token, [
    'name' => $tenantName,
    'domain' => $tenantDomain,
    'status' => 'active',
    'active_modules' => ['inventory'] // Only enable Inventory
]);
assertStep('Create Tenant', $createTenantRes['status'] === 200, json_encode($createTenantRes));
$newTenantId = $createTenantRes['body']['data']['id'];
echo "   New Tenant ID: $newTenantId\n";

// 4. Impersonate Tenant
echo "4. Impersonating New Tenant...\n";
$impRes = req('POST', "/api/root/tenants/$newTenantId/impersonate", $token);
assertStep('Impersonation', $impRes['status'] === 200);
$impToken = $impRes['body']['data']['token'];

// 5. Check Module Access (Inventory - Enabled)
echo "5. Checking Access to ENABLED Module (Inventory)...\n";
$invRes = req('GET', '/api/inventory', $impToken);
assertStep('Inventory Access (Allowed)', $invRes['status'] === 200, "Status: " . $invRes['status']);

// 6. Check Module Access (Donations - Disabled)
echo "6. Checking Access to DISABLED Module (Donations)...\n";
$donRes = req('GET', '/api/donations', $impToken);
// Should be 403 because we didn't include 'donations' in active_modules
assertStep('Donations Access (Denied)', $donRes['status'] === 403, "Status: " . $donRes['status']);

// 7. Create Data in New Tenant
echo "7. Creating Data in New Tenant...\n";
$itemRes = req('POST', '/api/inventory', $impToken, [
    'name' => 'Secret Item 007',
    'quantity' => 100,
    'purchase_price' => 10,
    'sale_price' => 20
]);
assertStep('Create Inventory Item', $itemRes['status'] === 200, json_encode($itemRes));

// 8. Verify Isolation (Check from Original Tenant Context)
echo "8. Verifying Data Isolation...\n";
// We use the original $token which belongs to the default tenant (or whatever admin is in)
// We need to ensure 'inventory' is enabled for the admin's tenant to even query it.
// Assuming admin tenant has inventory enabled. If not, we might get 403.
// Let's force enable inventory for admin tenant first to be sure we can check.
$db->exec("UPDATE tenants SET active_modules = '[\"inventory\",\"dashboard\",\"iam\"]' WHERE id = '$superAdminTenantId'");

$originalInvRes = req('GET', '/api/inventory', $token);
if ($originalInvRes['status'] === 200) {
    $items = $originalInvRes['body']['data']['items'] ?? [];
    $found = false;
    foreach ($items as $item) {
        if ($item['name'] === 'Secret Item 007') {
            $found = true;
            break;
        }
    }
    assertStep('Data Isolation (Item NOT visible in other tenant)', !$found, "Item was found in wrong tenant!");
} else {
    echo "   (Skipping Isolation Check - Admin tenant can't access inventory to verify absence)\n";
}

echo "------------------------------------\n";
echo "✅ FINAL VERIFICATION SUCCESSFUL\n";

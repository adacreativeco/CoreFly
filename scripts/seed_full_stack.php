<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

echo "Seeding Full Stack Data...\n";

$dbPath = __DIR__ . '/../storage/corefly.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 0. Ensure Schema Exists
echo "Ensuring database schema...\n";
$schemaSql = file_get_contents(__DIR__ . '/../database/migrations/sqlite_schema.sql');
$pdo->exec($schemaSql);
echo "Schema applied.\n";

// --- Super Admin Seeding ---
echo "Seeding Super Admin...\n";
$saRoleId = 'super-admin-role';
$saUserId = 'super-admin-user';

// 1. Ensure super-admin-role exists
$stmt = $pdo->prepare("SELECT count(*) FROM roles WHERE id = ?");
$stmt->execute([$saRoleId]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO roles (id, tenant_id, name, description, is_system, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$saRoleId, 'default-tenant-uuid', 'Super Admin', 'System Administrator', 1, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "Super Admin Role created.\n";
}

// 2. Grant Super Admin Permissions
$saPermissions = [
    '*', 'tenants:*', 'users:*', 'roles:*', 'settings:*'
];
$stmt = $pdo->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES (?, ?)");
foreach ($saPermissions as $perm) {
    $stmt->execute([$saRoleId, $perm]);
}

// 3. Ensure Super Admin User exists
$stmt = $pdo->prepare("SELECT count(*) FROM users WHERE id = ?");
$stmt->execute([$saUserId]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (id, tenant_id, username, email, password, first_name, last_name, role_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute([$saUserId, 'default-tenant-uuid', 'superadmin', 'admin@corfly.com', $password, 'Super', 'Admin', $saRoleId, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "Super Admin User created (admin@corfly.com / admin123).\n";
}
// --- End Super Admin Seeding ---

// 1. Ensure tenant-admin-role exists
$roleId = 'tenant-admin-role';
$tenantId = 'default-tenant-uuid';
$userId = 'demo-user';

// Ensure Tenant Exists
echo "Ensuring default tenant exists...\n";
$stmt = $pdo->prepare("SELECT count(*) FROM tenants WHERE id = ?");
$stmt->execute([$tenantId]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO tenants (id, name, domain, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tenantId, 'Default Tenant', 'default.corfly.com', 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "Tenant created.\n";
}

// Ensure User Exists
echo "Ensuring demo-user exists...\n";
$stmt = $pdo->prepare("SELECT count(*) FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (id, tenant_id, username, email, password, first_name, last_name, role_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Password is 'password' hashed
    $password = password_hash('password', PASSWORD_DEFAULT);
    $stmt->execute([$userId, $tenantId, 'demo-user', 'demo@corfly.com', $password, 'Demo', 'User', $roleId, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "User created.\n";
}

// Check if role exists
$stmt = $pdo->prepare("SELECT count(*) FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
if ($stmt->fetchColumn() == 0) {
    echo "Creating tenant-admin-role...\n";
    // Need to ensure we don't violate FK if tenant_id is required for roles (it is in schema usually)
    $stmt = $pdo->prepare("INSERT INTO roles (id, tenant_id, name, description, is_system, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$roleId, $tenantId, 'Tenant Admin', 'Full access', 1, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
}


// 2. Grant Permissions
$permissions = [
    'inventory:read', 'inventory:create', 'inventory:update', 'inventory:delete',
    'donations:read', 'donations:create', 'donations:update', 'donations:delete',
    'fields:read', 'fields:create', 'fields:update', 'fields:delete',
    'projects:read', 'projects:create', 'projects:update', 'projects:delete',
    'users:read', 'users:create', 'users:update', 'users:delete',
    'roles:read', 'roles:create', 'roles:update', 'roles:delete'
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission) VALUES (?, ?)");
foreach ($permissions as $perm) {
    $stmt->execute([$roleId, $perm]);
}
echo "Permissions granted.\n";

// 3. Seed Inventory
$stmt = $pdo->prepare("SELECT count(*) FROM inventory WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
if ($stmt->fetchColumn() == 0) {
    echo "Seeding Inventory...\n";
    $items = [
        ['Tohum - Buğday', 'Tarım', 500, 'kg', 12.50, 'Depo A'],
        ['Gübre - Azotlu', 'Tarım', 200, 'torba', 450.00, 'Depo B'],
        ['Traktör Lastiği', 'Yedek Parça', 4, 'adet', 5000.00, 'Atölye'],
        ['Dizel Yakıt', 'Yakıt', 1500, 'litre', 42.00, 'Yakıt Tankı 1']
    ];
    
    $sql = "INSERT INTO inventory (id, tenant_id, name, category, quantity, unit, unit_cost, location, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($items as $item) {
        $id = bin2hex(random_bytes(16));
        $stmt->execute([
            $id, $tenantId, $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], 'active', $userId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
        ]);
    }
}

// 4. Seed Donations and Campaigns
$stmt = $pdo->prepare("SELECT count(*) FROM campaigns WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$campaignId = 'default-campaign';
if ($stmt->fetchColumn() == 0) {
    echo "Seeding Campaign...\n";
    $stmt = $pdo->prepare("INSERT INTO campaigns (id, tenant_id, name, description, target_amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$campaignId, $tenantId, 'Genel Bağış Kampanyası', 'Genel amaçlı bağışlar', 1000000, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
}

$stmt = $pdo->prepare("SELECT count(*) FROM donations WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
if ($stmt->fetchColumn() == 0) {
    echo "Seeding Donations...\n";
    $donations = [
        ['Ahmet Yılmaz', 1000.00, $campaignId, 'completed'],
        ['Ayşe Demir', 500.00, $campaignId, 'completed'],
        ['Mehmet Kaya', 2500.00, $campaignId, 'pending'],
        ['Fatma Çelik', 100.00, $campaignId, 'completed']
    ];
    
    $sql = "INSERT INTO donations (id, tenant_id, donor_name, amount, campaign_id, payment_status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($donations as $d) {
        $id = bin2hex(random_bytes(16));
        $stmt->execute([
            $id, $tenantId, $d[0], $d[1], $d[2], $d[3], $userId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
        ]);
    }
}

// 5. Seed Fields
$stmt = $pdo->prepare("SELECT count(*) FROM fields WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
if ($stmt->fetchColumn() == 0) {
    echo "Seeding Fields...\n";
    $fields = [
        ['Kuzey Tarlası', 'Konya Ovası', 120, 'dönüm', 'Buğday', 'active'],
        ['Güney Bahçesi', 'Antalya Serik', 45, 'dönüm', 'Domates', 'active'],
        ['Doğu Mera', 'Erzurum', 200, 'hektar', 'Yonca', 'resting']
    ];
    
    $sql = "INSERT INTO fields (id, tenant_id, name, location, area, area_unit, current_crop, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($fields as $f) {
        $id = bin2hex(random_bytes(16));
        $stmt->execute([
            $id, $tenantId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
        ]);
    }
}

echo "Seeding Complete!\n";

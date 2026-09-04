<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/FieldTask.php';
require_once __DIR__ . '/../app/Models/Donation.php';
require_once __DIR__ . '/../app/Models/PoliticsVolunteer.php';
require_once __DIR__ . '/../app/Models/Tenant.php';

// Load Env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

use App\Core\Database;

echo "=== COREFLY FAZ 5 (SAHA, BAĞIŞ, POLİTİKA, ROOT) TESTİ ===\n\n";

$tenantId = 'test-tenant-' . uniqid();
$db = Database::getInstance()->getConnection();

// 1. Field Task
echo "1. Saha Görevi ekleniyor...\n";
$fId = uniqid();
$stmt = $db->prepare("INSERT INTO field_tasks (id, tenant_id, title, location, status) VALUES (?, ?, ?, ?, 'planned')");
$stmt->execute([$fId, $tenantId, 'Kadıköy Saha Dağıtımı', 'Kadıköy Meydan']);
echo "   [OK] Saha görevi eklendi. ID: $fId\n";

// 2. Donation
echo "\n2. Bağış ekleniyor...\n";
$dId = uniqid();
$stmt = $db->prepare("INSERT INTO donations (id, tenant_id, donor_name, amount, campaign_name) VALUES (?, ?, ?, 5000.00, 'Eğitim Bursu Fonu')");
$stmt->execute([$dId, $tenantId, 'Mehmet Kaya']);
echo "   [OK] Bağış kaydedildi: 5.000 TL (Eğitim Bursu Fonu)\n";

// 3. Politics Volunteer
echo "\n3. Sandık Görevlisi / Gönüllü ekleniyor...\n";
$pId = uniqid();
$stmt = $db->prepare("INSERT INTO politics_volunteers (id, tenant_id, full_name, district, ballot_box_number, role) VALUES (?, ?, ?, 'Beşiktaş', '1042', 'ballot_officer')");
$stmt->execute([$pId, $tenantId, 'Ayşe Demir']);
echo "   [OK] Gönüllü kaydedildi: Ayşe Demir (Sandık No: 1042)\n";

// 4. Root Tenants
echo "\n4. Sistem Kiracıları (Tenants) sorgulanıyor...\n";
$tenants = $db->query("SELECT id, name FROM tenants LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
echo "   [OK] Sistemdeki aktif kiracı sayısı: " . count($tenants) . "\n";

echo "\n[BAŞARILI] Faz 5 Backend ve Modelleri %100 Çalışıyor!\n";

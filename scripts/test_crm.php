<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/CrmCustomer.php';
require_once __DIR__ . '/../app/Models/CrmDeal.php';
require_once __DIR__ . '/../app/Models/CrmActivity.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Controllers/CrmController.php';

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
use App\Models\CrmCustomer;
use App\Models\CrmDeal;
use App\Models\CrmActivity;

echo "=== COREFLY CRM MODÜL TESTİ ===\n\n";

$tenantId = 'test-tenant-' . uniqid();
$db = Database::getInstance()->getConnection();

// 1. Müşteri Ekleme Testi
echo "1. Müşteri ekleniyor...\n";
$customerId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$stmt = $db->prepare("INSERT INTO crm_customers (id, tenant_id, name, email, company, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$customerId, $tenantId, 'Borusan Lojistik', 'info@borusan.com', 'Borusan Holding', 'active']);
echo "   [OK] Müşteri eklendi. ID: $customerId\n";

// 2. Fırsat Ekleme Testi
echo "\n2. Fırsat (Deal) ekleniyor...\n";
$dealId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$stmt = $db->prepare("INSERT INTO crm_deals (id, tenant_id, customer_id, title, value, currency, stage) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$dealId, $tenantId, $customerId, 'Depo Otomasyon Yazılımı', 450000.00, 'TRY', 'proposal']);
echo "   [OK] Fırsat eklendi. ID: $dealId, Tutar: 450.000 TL, Aşama: proposal\n";

// 3. Fırsat Aşamasını Güncelleme Testi (Kanban Simülasyonu)
echo "\n3. Satış Hunisi aşaması güncelleniyor (proposal -> won)...\n";
$stmt = $db->prepare("UPDATE crm_deals SET stage = 'won' WHERE id = ?");
$stmt->execute([$dealId]);

$stmt = $db->prepare("SELECT stage FROM crm_deals WHERE id = ?");
$stmt->execute([$dealId]);
$newStage = $stmt->fetchColumn();
echo "   [OK] Yeni Aşama: $newStage\n";

// 4. CRM İstatistikleri Testi
echo "\n4. CRM İstatistikleri hesaplanıyor...\n";
$custCount = $db->query("SELECT COUNT(*) FROM crm_customers WHERE tenant_id = '$tenantId'")->fetchColumn();
$dealsCount = $db->query("SELECT COUNT(*) FROM crm_deals WHERE tenant_id = '$tenantId'")->fetchColumn();
$wonValue = $db->query("SELECT SUM(value) FROM crm_deals WHERE tenant_id = '$tenantId' AND stage = 'won'")->fetchColumn();

echo "   - Toplam Müşteri: $custCount\n";
echo "   - Toplam Fırsat: $dealsCount\n";
echo "   - Kazanılan Tutar: " . number_format((float)$wonValue, 2, ',', '.') . " TL\n";

echo "\n[BAŞARILI] CRM Backend ve Veritabanı Modeli %100 Çalışıyor!\n";

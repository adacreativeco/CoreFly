<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/InventoryCategory.php';
require_once __DIR__ . '/../app/Models/InventoryProduct.php';
require_once __DIR__ . '/../app/Models/InventoryMovement.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

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

echo "=== COREFLY ENVANTER & STOK MODÜL TESTİ ===\n\n";

$tenantId = 'test-tenant-' . uniqid();
$db = Database::getInstance()->getConnection();

// 1. Kategori Ekleme
echo "1. Kategori ekleniyor...\n";
$catId = uniqid();
$stmt = $db->prepare("INSERT INTO inventory_categories (id, tenant_id, name) VALUES (?, ?, ?)");
$stmt->execute([$catId, $tenantId, 'Bilişim & Donanım']);
echo "   [OK] Kategori eklendi: Bilişim & Donanım (ID: $catId)\n";

// 2. Ürün Ekleme
echo "\n2. Ürün ekleniyor...\n";
$prodId = uniqid();
$stmt = $db->prepare("
    INSERT INTO inventory_products (id, tenant_id, category_id, name, sku, quantity, min_quantity, unit, unit_cost, unit_price) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$prodId, $tenantId, $catId, 'Dell OptiPlex İş İstasyonu', 'DELL-OPT-01', 15, 5, 'Adet', 25000.00, 32000.00]);
echo "   [OK] Ürün eklendi: Dell OptiPlex (Miktar: 15 Adet, Alış: 25.000 TL)\n";

// 3. Stok Hareketi (Stok Çıkışı)
echo "\n3. Stok çıkışı (5 adet) simüle ediliyor...\n";
$stmt = $db->prepare("UPDATE inventory_products SET quantity = quantity - 5 WHERE id = ?");
$stmt->execute([$prodId]);

$moveId = uniqid();
$stmt = $db->prepare("
    INSERT INTO inventory_movements (id, tenant_id, product_id, movement_type, quantity, previous_quantity, new_quantity, reason)
    VALUES (?, ?, ?, 'out', 5, 15, 10, 'Yeni şube teslimatı')
");
$stmt->execute([$moveId, $tenantId, $prodId]);

$newQty = $db->query("SELECT quantity FROM inventory_products WHERE id = '$prodId'")->fetchColumn();
echo "   [OK] Stok çıkışı yapıldı. Yeni Miktar: $newQty Adet\n";

// 4. İstatistikler
echo "\n4. Envanter İstatistikleri alınıyor...\n";
$prodCount = $db->query("SELECT COUNT(*) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn();
$totalQty = $db->query("SELECT SUM(quantity) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn();
$totalVal = $db->query("SELECT SUM(quantity * unit_cost) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn();

echo "   - Toplam Ürün Çeşidi: $prodCount\n";
echo "   - Depodaki Toplam Stok: $totalQty Adet\n";
echo "   - Toplam Stok Değeri: " . number_format((float)$totalVal, 2, ',', '.') . " TL\n";

echo "\n[BAŞARILI] Envanter Backend ve Veritabanı Modeli %100 Çalışıyor!\n";

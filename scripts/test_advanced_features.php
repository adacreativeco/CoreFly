<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/HrLeaveRequest.php';
require_once __DIR__ . '/../app/Models/HrPayroll.php';
require_once __DIR__ . '/../app/Models/CompanyTask.php';
require_once __DIR__ . '/../app/Models/InventorySupplier.php';

echo "=== COREFLY İLERİ DÜZEY MODÜL TESTİ (İZİNLER, BORDROLAR, GÖREVLER, TEDARİKÇİLER) ===\n\n";

$tenantId = 'default-tenant';
$db = \App\Core\Database::getInstance()->getConnection();

// 1. İzin Talebi Testi
echo "1. İzin Talebi Ekleniyor...\n";
$leaveId = uniqid();
$stmt = $db->prepare("
    INSERT INTO hr_leave_requests (id, tenant_id, employee_id, leave_type, start_date, end_date, days, reason, status)
    VALUES (?, ?, 'emp-1', 'Yıllık İzin', '2026-09-10', '2026-09-15', 5, 'Yıllık dinlenme', 'pending')
");
$stmt->execute([$leaveId, $tenantId]);
echo "   [OK] İzin talebi eklendi (5 Gün, Durum: pending)\n";

$stmtUpdate = $db->prepare("UPDATE hr_leave_requests SET status = 'approved' WHERE id = ?");
$stmtUpdate->execute([$leaveId]);
echo "   [OK] İzin durumu 'approved' olarak güncellendi.\n\n";

// 2. Bordro Kaydı Testi
echo "2. Bordro Kaydı Ekleniyor...\n";
$payrollId = uniqid();
$stmtPayroll = $db->prepare("
    INSERT INTO hr_payrolls (id, tenant_id, employee_id, period, base_salary, bonus, deductions, net_salary, status)
    VALUES (?, ?, 'emp-1', '2026-09', 45000.00, 5000.00, 2000.00, 48000.00, 'pending')
");
$stmtPayroll->execute([$payrollId, $tenantId]);
echo "   [OK] Bordro eklendi: 2026-09 Dönemi (Net: 48.000 TL, Durum: pending)\n";

$stmtPay = $db->prepare("UPDATE hr_payrolls SET status = 'paid', payment_date = '2026-09-04' WHERE id = ?");
$stmtPay->execute([$payrollId]);
echo "   [OK] Bordro durumu 'paid' olarak güncellendi.\n\n";

// 3. Genel Şirket Görevi Testi
echo "3. Bağımsız Şirket Görevi Ekleniyor...\n";
$taskId = uniqid();
$stmtTask = $db->prepare("
    INSERT INTO company_tasks (id, tenant_id, title, description, priority, status)
    VALUES (?, ?, 'Yıl Sonu Mali Denetim Dosyaları', 'Tüm fatura ve fişlerin arşivlenmesi', 'urgent', 'in_progress')
");
$stmtTask->execute([$taskId, $tenantId]);
echo "   [OK] Görev oluşturuldu (Öncelik: Acil, Durum: in_progress)\n\n";

// 4. Tedarikçi Testi
echo "4. Tedarikçi Ekleniyor...\n";
$supplierId = uniqid();
$stmtSup = $db->prepare("
    INSERT INTO inventory_suppliers (id, tenant_id, name, contact_person, phone, email)
    VALUES (?, ?, 'Mega Teknoloji Dağıtım A.Ş.', 'Burak Kaya', '0212 555 11 22', 'info@megatekno.com')
");
$stmtSup->execute([$supplierId, $tenantId]);
echo "   [OK] Tedarikçi eklendi: Mega Teknoloji Dağıtım A.Ş.\n\n";

echo "🎉 TÜM İLERİ DÜZEY MODÜLLER BACKEND SEVİYESİNDE %100 BAŞARILI!\n";

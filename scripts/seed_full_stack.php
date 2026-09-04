<?php

require_once __DIR__ . '/../app/Core/Database.php';

echo "=== COREFLY FULL-STACK SEEDER BAŞLATILIYOR ===\n\n";

$db = \App\Core\Database::getInstance()->getConnection();
$tenantId = 'default-tenant';

function uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// 1. Tenant
echo "1. Varsayılan Şirket (Tenant) Hazırlanıyor...\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE id = ?");
$stmt->execute([$tenantId]);
if ($stmt->fetchColumn() == 0) {
    $db->prepare("INSERT INTO tenants (id, name, status, created_at) VALUES (?, 'CoreFly Kurumsal Teknoloji A.Ş.', 'active', CURRENT_TIMESTAMP)")
       ->execute([$tenantId]);
    echo "   [OK] Tenant oluşturuldu: CoreFly Kurumsal Teknoloji A.Ş.\n";
} else {
    echo "   [OK] Tenant zaten mevcut.\n";
}

// 2. Roller ve İzinler
echo "2. Roller ve İzinler Tanımlanıyor...\n";
$roles = [
    ['id' => 'role-admin', 'name' => 'Süper Admin', 'desc' => 'Tüm sistem ve modüllere tam erişim', 'perms' => ['*']],
    ['id' => 'role-hr', 'name' => 'İK Yöneticisi', 'desc' => 'Personel, izin ve bordro yönetimi', 'perms' => ['hr:*', 'projects:*']],
    ['id' => 'role-finance', 'name' => 'Finans & Muhasebe', 'desc' => 'Faturalar, kasa ve banka hesapları', 'perms' => ['accounting:*', 'crm:*']],
    ['id' => 'role-sales', 'name' => 'Satış & CRM Lideri', 'desc' => 'Müşteri adayı ve anlaşma takibi', 'perms' => ['crm:*', 'projects:*']],
    ['id' => 'role-field', 'name' => 'Saha Koordinatörü', 'desc' => 'Saha ekipleri ve görev yönetimi', 'perms' => ['field:*', 'tasks:*']],
    ['id' => 'role-member', 'name' => 'Standart Çalışan', 'desc' => 'Temel proje ve görev erişimi', 'perms' => ['projects:*', 'tasks:*']]
];

foreach ($roles as $r) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE id = ?");
    $stmt->execute([$r['id']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO roles (id, tenant_id, name, description, is_system) VALUES (?, ?, ?, ?, 1)")
           ->execute([$r['id'], $tenantId, $r['name'], $r['desc']]);
        foreach ($r['perms'] as $p) {
            $db->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)")
               ->execute([$r['id'], $p]);
        }
    }
}
echo "   [OK] 6 Adet Kurumsal Rol ve İzin Matrisi yüklendi.\n";

// 3. Kullanıcılar
echo "3. Kurumsal Kullanıcılar Oluşturuluyor...\n";
$defaultHash = password_hash('Admin123!', PASSWORD_DEFAULT);
$users = [
    ['id' => 'user-admin', 'email' => 'admin@corefly.com', 'name' => 'Sistem Yöneticisi (Admin)', 'role' => 'admin'],
    ['id' => 'user-hr', 'email' => 'ik@corefly.com', 'name' => 'Zeynep Kaya (İK Müdürü)', 'role' => 'hr'],
    ['id' => 'user-finance', 'email' => 'muhasebe@corefly.com', 'name' => 'Murat Arslan (Finans Müdürü)', 'role' => 'finance'],
    ['id' => 'user-sales', 'email' => 'satis@corefly.com', 'name' => 'Canan Çetin (Satış Direktörü)', 'role' => 'sales']
];

foreach ($users as $u) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$u['email']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO users (id, email, password_hash, full_name, tenant_id, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)")
           ->execute([$u['id'], $u['email'], $defaultHash, $u['name'], $tenantId, $u['role']]);
    }
}
echo "   [OK] Varsayılan Kullanıcılar (admin@corefly.com / Admin123!) hazırlandı.\n";

// 4. Departmanlar ve Pozisyonlar
echo "4. İK Departman ve Pozisyonları Oluşturuluyor...\n";
$departments = [
    ['id' => 'dept-it', 'name' => 'Yazılım ve Bilgi Teknolojileri'],
    ['id' => 'dept-finance', 'name' => 'Finans ve Mali İşler'],
    ['id' => 'dept-hr', 'name' => 'İnsan Kaynakları ve Akademi'],
    ['id' => 'dept-sales', 'name' => 'Satış ve İş Geliştirme'],
    ['id' => 'dept-ops', 'name' => 'Saha ve Teknik Operasyon']
];

foreach ($departments as $d) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE id = ?");
    $stmt->execute([$d['id']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO departments (id, tenant_id, name) VALUES (?, ?, ?)")
           ->execute([$d['id'], $tenantId, $d['name']]);
    }
}

$positions = [
    ['id' => 'pos-arch', 'dept' => 'dept-it', 'title' => 'Kıdemli Yazılım Mimarı'],
    ['id' => 'pos-devops', 'dept' => 'dept-it', 'title' => 'DevOps ve Sistem Mühendisi'],
    ['id' => 'pos-accountant', 'dept' => 'dept-finance', 'title' => 'Mali Müşavir & Denetçi'],
    ['id' => 'pos-sales', 'dept' => 'dept-sales', 'title' => 'Kurumsal Müşteri Temsilcisi'],
    ['id' => 'pos-ops', 'dept' => 'dept-ops', 'title' => 'Saha Operasyon Şefi']
];

foreach ($positions as $pos) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM positions WHERE id = ?");
    $stmt->execute([$pos['id']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO positions (id, tenant_id, department_id, title) VALUES (?, ?, ?, ?)")
           ->execute([$pos['id'], $tenantId, $pos['dept'], $pos['title']]);
    }
}
echo "   [OK] Departmanlar ve Pozisyonlar tanımlandı.\n";

// 5. Personel Kadrosu (Employees)
echo "5. Personel Kadrosu Ekleniyor...\n";
$employees = [
    ['id' => 'emp-1', 'user_id' => 'user-emp-1', 'dept' => 'dept-it', 'pos' => 'pos-arch', 'first' => 'Ahmet', 'last' => 'Yılmaz', 'email' => 'ahmet.yilmaz@corefly.com', 'emp_no' => 'EMP-001', 'salary' => 65000],
    ['id' => 'emp-2', 'user_id' => 'user-emp-2', 'dept' => 'dept-it', 'pos' => 'pos-devops', 'first' => 'Burak', 'last' => 'Kaya', 'email' => 'burak.kaya@corefly.com', 'emp_no' => 'EMP-002', 'salary' => 58000],
    ['id' => 'emp-3', 'user_id' => 'user-emp-3', 'dept' => 'dept-finance', 'pos' => 'pos-accountant', 'first' => 'Selin', 'last' => 'Demir', 'email' => 'selin.demir@corefly.com', 'emp_no' => 'EMP-003', 'salary' => 52000],
    ['id' => 'emp-4', 'user_id' => 'user-emp-4', 'dept' => 'dept-sales', 'pos' => 'pos-sales', 'first' => 'Mert', 'last' => 'Aydın', 'email' => 'mert.aydin@corefly.com', 'emp_no' => 'EMP-004', 'salary' => 45000],
    ['id' => 'emp-5', 'user_id' => 'user-emp-5', 'dept' => 'dept-ops', 'pos' => 'pos-ops', 'first' => 'Hakan', 'last' => 'Öz', 'email' => 'hakan.oz@corefly.com', 'emp_no' => 'EMP-005', 'salary' => 42000],
];

foreach ($employees as $e) {
    // Önce Kullanıcıyı users tablosuna ekle
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->execute([$e['user_id']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO users (id, email, password_hash, full_name, tenant_id, role, is_active) VALUES (?, ?, ?, ?, ?, 'member', 1)")
           ->execute([$e['user_id'], $e['email'], $defaultHash, $e['first'] . ' ' . $e['last'], $tenantId]);
    }

    // Sonra employees tablosuna ekle
    $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE id = ?");
    $stmt->execute([$e['id']]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO employees (id, tenant_id, user_id, employee_number, department_id, position_id, hire_date, salary, currency, status) VALUES (?, ?, ?, ?, ?, ?, '2024-01-15', ?, 'TRY', 'active')")
           ->execute([$e['id'], $tenantId, $e['user_id'], $e['emp_no'], $e['dept'], $e['pos'], $e['salary']]);
    }
}
echo "   [OK] 5 Personel ve kullanıcı profili sisteme kaydedildi.\n";

// 6. İzin Talepleri ve Bordrolar
echo "6. İzinler ve Maaş Bordroları Dolduruluyor...\n";
$db->prepare("INSERT OR IGNORE INTO hr_leave_requests (id, tenant_id, employee_id, leave_type, start_date, end_date, days, reason, status) VALUES
    ('leave-1', ?, 'emp-1', 'Yıllık İzin', '2026-09-15', '2026-09-20', 5, 'Aile tatili', 'approved'),
    ('leave-2', ?, 'emp-3', 'Mazeret İzni', '2026-09-08', '2026-09-09', 1, 'Resmi daire işlemleri', 'pending'),
    ('leave-3', ?, 'emp-4', 'Hastalık / Rapor', '2026-09-01', '2026-09-03', 2, 'Grip istirahati', 'approved')
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO hr_payrolls (id, tenant_id, employee_id, period, base_salary, bonus, deductions, net_salary, status, payment_date) VALUES
    ('pay-1', ?, 'emp-1', '2026-09', 65000.00, 5000.00, 2500.00, 67500.00, 'paid', '2026-09-01'),
    ('pay-2', ?, 'emp-2', '2026-09', 58000.00, 3000.00, 2000.00, 59000.00, 'paid', '2026-09-01'),
    ('pay-3', ?, 'emp-3', '2026-09', 52000.00, 2500.00, 1800.00, 52700.00, 'pending', NULL)
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] İzin ve Bordro kayıtları oluşturuldu.\n";

// 7. CRM Müşterileri ve Satış Hunisi (Kanban)
echo "7. CRM Müşterileri ve Satış Fırsatları Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO crm_customers (id, tenant_id, name, company, email, phone, status) VALUES
    ('crm-c-1', ?, 'Ali Koç', 'Koç Sistem Bilişim A.Ş.', 'ali.koc@koc.com', '0216 555 0101', 'active'),
    ('crm-c-2', ?, 'Gülfem Tandoğan', 'Turkcell Dijital Çözümler', 'gulfem@turkcell.com.tr', '0212 313 0000', 'active'),
    ('crm-c-3', ?, 'Serdar Yılmaz', 'Aselsan Tedarik Zinciri', 'syilmaz@aselsan.com.tr', '0312 592 1000', 'lead'),
    ('crm-c-4', ?, 'Banu Şen', 'Migros Lojistik Dağıtım', 'banu.sen@migros.com.tr', '0216 574 3000', 'active')
")->execute([$tenantId, $tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO crm_deals (id, tenant_id, customer_id, title, value, currency, stage, probability, expected_close_date) VALUES
    ('deal-1', ?, 'crm-c-1', 'Kurumsal ERP Bulut Dönüşüm Projesi', 850000.00, 'TRY', 'won', 100, '2026-08-30'),
    ('deal-2', ?, 'crm-c-2', 'Saha Ekipleri Mobil Takip Lisanslama', 420000.00, 'TRY', 'negotiation', 80, '2026-09-20'),
    ('deal-3', ?, 'crm-c-3', 'Yedek Veri Merkezi Donanım Tedariği', 1250000.00, 'TRY', 'proposal', 50, '2026-10-15'),
    ('deal-4', ?, 'crm-c-4', 'Merkez Depo Barkod ve RFID Entegrasyonu', 330000.00, 'TRY', 'lead', 20, '2026-11-01')
")->execute([$tenantId, $tenantId, $tenantId, $tenantId]);
echo "   [OK] CRM Müşterileri ve Satış Fırsatları yüklendi.\n";

// 8. Envanter, Ürünler ve Tedarikçiler
echo "8. Envanter ve Depo Stoğu Hazırlanıyor...\n";
$db->prepare("INSERT OR IGNORE INTO inventory_categories (id, tenant_id, name, description) VALUES
    ('cat-1', ?, 'Sunucu & Ağ Cihazları', 'Server, switch, firewall ve kablolama'),
    ('cat-2', ?, 'Kullanıcı Bilgisayarları', 'Laptop, masaüstü iş istasyonları'),
    ('cat-3', ?, 'Ofis & Çevre Birimleri', 'Monitör, yazıcı ve aksesuarlar')
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO inventory_suppliers (id, tenant_id, name, contact_person, phone, email) VALUES
    ('sup-1', ?, 'Arena Bilgisayar San. Tic. A.Ş.', 'Gökhan Varol', '0212 364 6464', 'satis@arena.com.tr'),
    ('sup-2', ?, 'İndeks Bilgisayar A.Ş.', 'Hande Sezer', '0212 331 2121', 'kurumsal@index.com.tr')
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO inventory_products (id, tenant_id, category_id, name, sku, quantity, min_quantity, unit, unit_cost, unit_price, location) VALUES
    ('prod-1', ?, 'cat-2', 'Apple MacBook Pro 16\" M3 Max 36GB', 'APL-MBP-16', 14, 3, 'Adet', 95000.00, 115000.00, 'A-101 Ana Depo'),
    ('prod-2', ?, 'cat-1', 'Dell PowerEdge R760 Rack Server', 'DLL-PE-R760', 4, 2, 'Adet', 180000.00, 225000.00, 'Sistem Odası Kabinet 2'),
    ('prod-3', ?, 'cat-1', 'Cisco Catalyst 9300 48-Port PoE+ Switch', 'CSC-C9300-48P', 8, 2, 'Adet', 45000.00, 58000.00, 'A-204 Ağ Rafı'),
    ('prod-4', ?, 'cat-3', 'Dell UltraSharp 32\" 4K USB-C Hub Monitör', 'DLL-U3223QE', 2, 5, 'Adet', 22000.00, 28000.00, 'B-01 Ofis Deposu'),
    ('prod-5', ?, 'cat-3', 'HP LaserJet Enterprise Çok Fonksiyonlu Yazıcı', 'HP-LJ-M636', 3, 2, 'Adet', 38000.00, 47000.00, 'B-02 Ofis Deposu')
")->execute([$tenantId, $tenantId, $tenantId, $tenantId, $tenantId]);
echo "   [OK] Ürünler, Kategoriler ve Tedarikçiler eklendi.\n";

// 9. Ön Muhasebe, Faturalar ve Banka Hesapları
echo "9. Ön Muhasebe, Kasalar ve Faturalar Oluşturuluyor...\n";
$db->prepare("INSERT OR IGNORE INTO accounting_accounts (id, tenant_id, name, type, currency, balance, bank_name, iban) VALUES
    ('acc-1', ?, 'Garanti BBVA Şirket Ticari Hesabı', 'bank', 'TRY', 1450000.00, 'Garanti BBVA', 'TR56 0006 2000 0001 2345 6789 01'),
    ('acc-2', ?, 'İş Bankası Operasyon Hesabı', 'bank', 'TRY', 320000.00, 'Türkiye İş Bankası', 'TR33 0006 4000 0012 3456 7890 12'),
    ('acc-3', ?, 'Merkez Ofis TL Kasası', 'cash', 'TRY', 45000.00, 'Nakit Kasa', NULL)
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO accounting_invoices (id, tenant_id, number, type, title, customer_name, issue_date, due_date, subtotal, tax_total, total, status) VALUES
    ('inv-1', ?, 'FTR-2026-0089', 'sale', 'ERP Yazılım Lisans Bedeli', 'Koç Sistem Bilişim A.Ş.', '2026-08-15', '2026-09-15', 850000.00, 170000.00, 1020000.00, 'paid'),
    ('inv-2', ?, 'FTR-2026-0090', 'sale', 'Saha Ekipleri Lisans Faturası', 'Turkcell Dijital Çözümler', '2026-09-01', '2026-10-01', 420000.00, 84000.00, 504000.00, 'sent'),
    ('inv-3', ?, 'ALIS-2026-0044', 'purchase', 'Donanım ve Sunucu Alım Faturası', 'Arena Bilgisayar San. Tic. A.Ş.', '2026-08-20', '2026-09-20', 190000.00, 38000.00, 228000.00, 'paid')
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO accounting_transactions (id, tenant_id, account_id, type, amount, category, description, date) VALUES
    ('trans-1', ?, 'acc-1', 'income', 1020000.00, 'Satış Geliri', 'Koç Sistem ERP Dönüşüm Fatura Tahsilatı', '2026-08-25'),
    ('trans-2', ?, 'acc-1', 'expense', 228000.00, 'Donanım Alımı', 'Arena Bilgisayar MacBook Alım Ödemesi', '2026-08-26'),
    ('trans-3', ?, 'acc-2', 'expense', 85000.00, 'Ofis & Kira', 'Eylül Ayı Genel Merkez Plaza Kira Bedeli', '2026-09-01')
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] Hesaplar, Faturalar ve Finansal Hareketler kaydedildi.\n";

// 10. Şirket Görevleri (Tasks)
echo "10. Şirket Genel Görevleri Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO company_tasks (id, tenant_id, title, description, priority, status, due_date) VALUES
    ('ctask-1', ?, 'Q3 Çeyrek Sonu Mali ve Finansal Denetim', 'Tüm tahsilat makbuzlarının ve faturaların mali müşavirle mutabakatı', 'urgent', 'in_progress', '2026-09-15'),
    ('ctask-2', ?, 'ISO 27001 Bilgi Güvenliği Güvenlik Taraması', 'Sunucu port taramaları ve penetrasyon test raporunun incelenmesi', 'high', 'todo', '2026-09-25'),
    ('ctask-3', ?, 'Yeni Saha Ekipmanlarının Barkodlanması', 'Dell sunucular ve el terminallerinin envanter kaydına işlenmesi', 'medium', 'done', '2026-09-02')
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] Şirket Görevleri oluşturuldu.\n";

// 11. Saha, Bağış, Teşkilat
echo "11. Saha Operasyon, Bağış ve Teşkilat Verileri Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO field_tasks (id, tenant_id, title, description, team_name, location, priority, status, task_date) VALUES
    ('ftask-1', ?, 'Kadıköy Rıhtım Fiber Optik Hat Devreye Alma', 'Metropol altyapı bağlantısı ve sinyal testi', 'Marmara Saha Ekibi 1', 'Kadıköy / İstanbul', 'urgent', 'in_progress', '2026-09-05'),
    ('ftask-2', ?, 'Levent Plaza Sunucu Kabinet Kurulumu', 'Rack montajı, kesintisiz güç kaynağı bağlantısı', 'Avrupa Saha Ekibi 2', 'Levent / İstanbul', 'high', 'planned', '2026-09-10')
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO donations (id, tenant_id, donor_name, donor_phone, amount, currency, campaign_name, payment_method, notes) VALUES
    ('don-1', ?, 'Selçuk Güler', '0532 999 88 77', 25000.00, 'TRY', 'Geleceğe Umut Eğitim Bursu', 'bank', 'Makbuz No: 2026-B-104'),
    ('don-2', ?, 'Kalkınma Vakfı İktisadi İşletmesi', '0212 444 01 02', 150000.00, 'TRY', 'Kırsal Bölge Teknoloji Sınıfları', 'bank', 'Kurumsal Hibe Destek Fonu'),
    ('don-3', ?, 'Emre Doğan', '0544 333 22 11', 5000.00, 'TRY', 'Genel Bağış', 'credit_card', 'Düzenli aylık bağış')
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO politics_volunteers (id, tenant_id, full_name, phone, city, district, neighborhood, ballot_box_number, role) VALUES
    ('pol-1', ?, 'Mehmet Akif Özcan', '0532 888 77 66', 'İstanbul', 'Kadıköy', 'Caferağa Mah.', '1042', 'ballot_officer'),
    ('pol-2', ?, 'Ayşe Şimşek', '0533 777 66 55', 'İstanbul', 'Kadıköy', 'Moda', '1043', 'observer'),
    ('pol-3', ?, 'Hüseyin Kaya', '0535 666 55 44', 'Ankara', 'Çankaya', 'Kavaklıdere', '2011', 'coordinator')
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] Saha, Bağış ve Teşkilat kayıtları oluşturuldu.\n";

// 12. Takvim, Duyurular, Destek (Helpdesk) ve Bildirimler
echo "12. Duyurular, Takvim, Destek Masası ve Bildirimler Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO announcements (id, tenant_id, title, content, priority, is_pinned, created_by) VALUES
    ('ann-1', ?, '🎉 CoreFly v2.0 Modern ERP Sistemi Canlıya Alındı', 'Şirketimizin tüm süreçleri modern web arayüzüne taşınmıştır. İK, CRM, Envanter ve Ön Muhasebe modüllerini kullanabilirsiniz.', 'urgent', 1, 'user-admin'),
    ('ann-2', ?, '📢 Yıllık Özel Sağlık Sigortası Poliçe Yenilemeleri', 'Poliçe kapsamınızı İK portalı üzerinden 20 Eylül tarihine kadar kontrol etmeniz rica olunur.', 'normal', 0, 'user-hr')
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO calendar_events (id, tenant_id, title, description, start_date, end_date, location, event_type, created_by) VALUES
    ('cal-1', ?, 'Haftalık Üst Yönetim İcra Kurulu Toplantısı', 'Genel şirket hedefleri ve bütçe değerlendirmesi', '2026-09-07 10:00:00', '2026-09-07 12:00:00', 'Büyük Toplantı Salonu A', 'meeting', 'user-admin'),
    ('cal-2', ?, 'Q3 CRM & Satış Kapanış Değerlendirmesi', 'Satış hunisindeki büyük fırsatların kapanış stratejisi', '2026-09-11 14:00:00', '2026-09-11 16:00:00', 'Online / Google Meet', 'meeting', 'user-sales')
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO helpdesk_tickets (id, tenant_id, title, description, category, priority, status, created_by) VALUES
    ('tkt-1', ?, 'Yedek Monitör ve Type-C Çoğaltıcı Talebi', 'Yazılım ekibi yeni başlayan arkadaşımız için 32 inç monitör gerekiyor.', 'Donanım', 'medium', 'in_progress', 'user-emp-1'),
    ('tkt-2', ?, 'VPN Bağlantısı ve Güvenlik Sertifikası Güncellemesi', 'Evden çalışırken şirket içi veri tabanına erişimde zaman aşımı yaşanıyor.', 'Ağ & Sistem', 'high', 'open', 'user-emp-2')
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO notifications (id, tenant_id, user_id, type, title, content, is_read, created_at) VALUES
    ('notif-1', ?, 'user-admin', 'success', 'Yeni Fatura Tahsil Edildi', 'Koç Sistem 1.020.000 TL tutarındaki fatura tahsilatı hesabınıza geçti.', 0, CURRENT_TIMESTAMP),
    ('notif-2', ?, 'user-admin', 'info', 'Yeni İzin Onay Talebi', 'Ahmet Yılmaz tarafından 5 günlük yıllık izin talebi iletildi.', 0, CURRENT_TIMESTAMP),
    ('notif-3', ?, 'user-admin', 'warning', 'Kritik Stok Uyarısı', 'Dell UltraSharp 32\" Monitör stoğu kritik eşiğin altına indi (2 Adet kaldı).', 0, CURRENT_TIMESTAMP)
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] Duyurular, Takvim Etkinlikleri ve Bildirimler eklendi.\n";

// 13. Denetim Günlükleri (Audit Logs)
echo "13. Sistem Güvenlik ve Denetim Günlükleri (Audit Logs) Yazılıyor...\n";
$db->prepare("INSERT OR IGNORE INTO audit_logs (id, tenant_id, user_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES
    ('audit-1', ?, 'user-admin', 'login', 'auth', 'user-admin', 'Admin kullanıcısı başarıyla oturum açtı', '127.0.0.1', '2026-09-04 17:30:12'),
    ('audit-2', ?, 'user-admin', 'create', 'invoice', 'inv-1', 'FTR-2026-0089 numaralı satış faturası kesildi (1.020.000 TL)', '127.0.0.1', '2026-09-04 17:35:40'),
    ('audit-3', ?, 'user-admin', 'update', 'deal', 'deal-1', 'Satış fırsatı aşaması \"Won\" olarak güncellendi', '127.0.0.1', '2026-09-04 17:38:22'),
    ('audit-4', ?, 'user-admin', 'create', 'leave_request', 'leave-1', 'Ahmet Yılmaz için yıllık izin talebi onaylandı', '127.0.0.1', '2026-09-04 17:40:15')
")->execute([$tenantId, $tenantId, $tenantId, $tenantId]);
echo "   [OK] Güvenlik ve Denetim Kayıtları (Audit Logs) işlendi.\n";

// 14. Projeler ve Proje Görevleri (Kanban)
echo "14. Projeler ve Kanban Görevleri Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO projects (id, tenant_id, code, name, description, type, status, priority, start_date, end_date, budget, currency, progress, created_by) VALUES
    ('prj-1', ?, 'PRJ-ERP-01', 'ERP Bulut Modernizasyonu', 'React ve RESTful mimarisine geçiş ve modül entegrasyonları', 'internal', 'active', 'urgent', '2026-08-01', '2026-12-31', 1500000.00, 'TRY', 65.0, 'user-admin'),
    ('prj-2', ?, 'PRJ-MOB-02', 'Saha Mobil GPS ve Takip Sistemi', 'Saha ekipleri için konum tabanlı offline görev yönetimi', 'client', 'planning', 'high', '2026-09-01', '2026-11-30', 480000.00, 'TRY', 25.0, 'user-admin'),
    ('prj-3', ?, 'PRJ-SEC-03', 'ISO 27001 Bilgi Güvenliği Uyum Projesi', 'Güvenlik denetimleri, RBAC yetki matrisleri ve şifreleme', 'internal', 'active', 'high', '2026-07-15', '2026-10-15', 320000.00, 'TRY', 80.0, 'user-admin')
")->execute([$tenantId, $tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO project_tasks (id, tenant_id, project_id, title, description, status, priority, due_date, assignee_id, created_by) VALUES
    ('ptask-1', ?, 'prj-1', 'Veritabanı İndeks ve Şema İyileştirmeleri', 'SQLite/MySQL sorgu hızlandırma ve dış anahtar bağları', 'done', 'high', '2026-08-20', 'user-emp-1', 'user-admin'),
    ('ptask-2', ?, 'prj-1', 'Modern Executive Dashboard Tasarımı', 'Yönetici KPI kartları, son görevler ve hızlı erişim menüsü', 'done', 'urgent', '2026-09-04', 'user-emp-1', 'user-admin'),
    ('ptask-3', ?, 'prj-1', 'REST API Güvenlik ve RBAC Middleware Testleri', 'Tüm uç noktalarda rol ve yetki kontrollerinin doğrulanması', 'in_progress', 'high', '2026-09-12', 'user-emp-2', 'user-admin'),
    ('ptask-4', ?, 'prj-2', 'Saha Ekipleri Harita Görünümü Prototipi', 'Leaflet / OpenStreetMap üzerinde saha personeli işaretçileri', 'todo', 'medium', '2026-09-25', 'user-emp-2', 'user-admin')
")->execute([$tenantId, $tenantId, $tenantId, $tenantId]);
echo "   [OK] Projeler ve Proje Görevleri (Kanban) hazırlandı.\n";

// 15. Mesajlaşma Kanalları ve Örnek Sohbetler
echo "15. Kurumsal Sohbet Kanalları ve Mesajlar Ekleniyor...\n";
$db->prepare("INSERT OR IGNORE INTO conversations (id, tenant_id, title, type, description, created_by, last_message_at) VALUES
    ('conv-1', ?, '📢 Genel Şirket Duyuru ve Paylaşım', 'channel', 'Tüm şirket personelinin açık iletişim kanalı', 'user-admin', CURRENT_TIMESTAMP),
    ('conv-2', ?, '💻 Ar-Ge ve Yazılım Ekibi', 'group', 'Yazılım geliştiricileri ve sistem mühendisleri grubu', 'user-admin', CURRENT_TIMESTAMP)
")->execute([$tenantId, $tenantId]);

$db->prepare("INSERT OR IGNORE INTO conversation_participants (id, conversation_id, user_id, role) VALUES
    ('cp-1', 'conv-1', 'user-admin', 'admin'),
    ('cp-2', 'conv-1', 'user-hr', 'member'),
    ('cp-3', 'conv-1', 'user-emp-1', 'member'),
    ('cp-4', 'conv-2', 'user-admin', 'admin'),
    ('cp-5', 'conv-2', 'user-emp-1', 'member'),
    ('cp-6', 'conv-2', 'user-emp-2', 'member')
")->execute();

$db->prepare("INSERT OR IGNORE INTO messages (id, tenant_id, conversation_id, sender_id, content, created_at) VALUES
    ('msg-1', ?, 'conv-1', 'user-admin', 'Değerli çalışma arkadaşlarımız, CoreFly Kurumsal v2.0 ERP sistemimiz tüm modülleriyle devreye alınmıştır. Hayırlı olsun!', CURRENT_TIMESTAMP),
    ('msg-2', ?, 'conv-1', 'user-hr', 'Harika bir haber! İK izin talepleri ve bordro dökümleri de panel üzerinden anlık olarak onaylanabilmektedir.', CURRENT_TIMESTAMP),
    ('msg-3', ?, 'conv-2', 'user-emp-1', 'Yazılım ve altyapı ekibi olarak API servislerini, testleri ve derlemeyi başarıyla tamamladık.', CURRENT_TIMESTAMP)
")->execute([$tenantId, $tenantId, $tenantId]);
echo "   [OK] Sohbet Kanalları ve Örnek Mesajlaşmalar dolduruldu.\n";

echo "\n=======================================================\n";
echo "🎉 TÜM SİSTEM FULL-STACK DEMO VE KURUMSAL VERİLERLE DOLDURULDU!\n";
echo "Giriş Bilgileri:\n";
echo "  E-Posta : admin@corefly.com\n";
echo "  Şifre   : Admin123!\n";
echo "=======================================================\n";

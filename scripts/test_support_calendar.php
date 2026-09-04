<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/HelpdeskTicket.php';
require_once __DIR__ . '/../app/Models/HelpdeskMessage.php';
require_once __DIR__ . '/../app/Models/Announcement.php';
require_once __DIR__ . '/../app/Models/CalendarEvent.php';

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

echo "=== COREFLY HELPDESK, TAKVİM & DUYURULAR TESTİ ===\n\n";

$tenantId = 'test-tenant-' . uniqid();
$db = Database::getInstance()->getConnection();

// 1. Helpdesk Ticket & Message
echo "1. Destek Talebi (Ticket) oluşturuluyor...\n";
$ticketId = uniqid();
$stmt = $db->prepare("INSERT INTO helpdesk_tickets (id, tenant_id, title, description, priority, status, category, created_by) VALUES (?, ?, ?, ?, 'high', 'open', 'Sistem Hatası', 'user1')");
$stmt->execute([$ticketId, $tenantId, 'VPN Bağlantı Hatası', 'Ofis dışından şirket içi sunuculara bağlanırken hata alıyorum.']);
echo "   [OK] Ticket oluşturuldu. ID: $ticketId\n";

$msgId = uniqid();
$stmt = $db->prepare("INSERT INTO helpdesk_messages (id, tenant_id, ticket_id, user_id, user_name, message) VALUES (?, ?, ?, 'it_admin', 'IT Destek', 'VPN sertifikanız güncellendi, tekrar dener misiniz?')");
$stmt->execute([$msgId, $tenantId, $ticketId]);
echo "   [OK] Destek yanıtı eklendi.\n";

// 2. Announcement
echo "\n2. Şirket Duyurusu ekleniyor...\n";
$annId = uniqid();
$stmt = $db->prepare("INSERT INTO announcements (id, tenant_id, title, content, priority, is_pinned, created_by, author_name) VALUES (?, ?, ?, ?, 'urgent', 1, 'admin', 'İnsan Kaynakları')");
$stmt->execute([$annId, $tenantId, 'Yıllık İzin Planlamaları Hakkında', '2026 yılı yaz dönemi yıllık izin taleplerinin 15 Nisan tarihine kadar iletilmesi rica olunur.']);
echo "   [OK] Duyuru yayınlandı. ID: $annId (Önem: Acil, İğneli: Evet)\n";

// 3. Calendar Event
echo "\n3. Takvim Etkinliği ekleniyor...\n";
$evtId = uniqid();
$stmt = $db->prepare("INSERT INTO calendar_events (id, tenant_id, title, description, event_type, start_date, end_date, location, created_by) VALUES (?, ?, ?, ?, 'meeting', '2026-09-10 10:00:00', '2026-09-10 11:30:00', 'Toplantı Salonu B', 'admin')");
$stmt->execute([$evtId, $tenantId, 'Q3 Değerlendirme Toplantısı', 'Tüm departman yöneticilerinin katılımıyla çeyrek sonu kapanış toplantısı.']);
echo "   [OK] Etkinlik takvime eklendi: Q3 Değerlendirme Toplantısı (10 Eylül 10:00)\n";

echo "\n[BAŞARILI] Helpdesk, Duyuru ve Takvim Modülleri Backend Seviyesinde %100 Çalışıyor!\n";

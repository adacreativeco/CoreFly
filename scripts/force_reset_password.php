<?php
require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables safely
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    try {
        $dotenv->safeLoad();
    } catch (Exception $e) {}
}

$dbPath = __DIR__ . '/../storage/corefly.sqlite';
echo "Veritabani: $dbPath\n";

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Kullaniciyi Bul
    $email = 'admin@corefly.com';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Kullanici ($email) bulunamadi! Olusturuluyor...\n";
        $id = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, first_name, last_name, role_id, status, tenant_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id, 
            'superadmin', 
            $email, 
            password_hash('123456', PASSWORD_DEFAULT), 
            'Super', 
            'Admin', 
            'super-admin-role', 
            'active', 
            'default-tenant-uuid',
            date('Y-m-d H:i:s'), 
            date('Y-m-d H:i:s')
        ]);
        echo "Kullanici olusturuldu.\n";
    } else {
        echo "Kullanici bulundu (ID: " . $user['id'] . "). Sifre guncelleniyor...\n";
        $newHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, status = 'active', login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
        echo "Sifre '123456' olarak guncellendi.\n";
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}

<?php

require_once __DIR__ . '/../app/Core/Database.php';

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

echo "===============================================" . PHP_EOL;
echo "   COREFLY MULTI-DATABASE BAĞLANTI TESTİ       " . PHP_EOL;
echo "===============================================" . PHP_EOL;

$config = require __DIR__ . '/../config/database.php';
$default = $config['default'] ?? 'sqlite';
$dbConfig = $config['connections'][$default] ?? [];

echo "Yapılandırılmış Sürücü: " . strtoupper($default) . PHP_EOL;

if ($default === 'sqlite') {
    echo "Veritabanı Dosyası: " . ($dbConfig['database'] ?? '') . PHP_EOL;
} else {
    echo "Host:     " . ($dbConfig['host'] ?? '') . ":" . ($dbConfig['port'] ?? '') . PHP_EOL;
    echo "Database: " . ($dbConfig['database'] ?? '') . PHP_EOL;
    echo "User:     " . ($dbConfig['username'] ?? '') . PHP_EOL;
}

echo PHP_EOL . "Bağlantı kuruluyor..." . PHP_EOL;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    
    echo "✅ BAĞLANTI BAŞARILI!" . PHP_EOL;
    echo "   Aktif PDO Sürücüsü: " . strtoupper($driverName) . PHP_EOL;
    echo "   Sunucu Sürümü:      " . $serverVersion . PHP_EOL;

    // Test simple query
    if ($driverName === 'sqlite') {
        $count = $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
        echo "   Mevcut Tablo Sayısı: " . $count . PHP_EOL;
    } elseif ($driverName === 'mysql') {
        $count = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
        echo "   Mevcut Tablo Sayısı: " . $count . PHP_EOL;
    } elseif ($driverName === 'pgsql') {
        $count = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();
        echo "   Mevcut Tablo Sayısı: " . $count . PHP_EOL;
    }

    echo PHP_EOL . "Sistem " . strtoupper($driverName) . " üzerinde sorunsuz çalışmaya hazırdır." . PHP_EOL;
    echo "===============================================" . PHP_EOL;
    exit(0);
} catch (\Exception $e) {
    echo "❌ BAĞLANTI HATASI!" . PHP_EOL;
    echo "   Hata Mesajı: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL . "İpucu: .env dosyasındaki DB_CONNECTION (sqlite, mysql, pgsql) ayarlarını kontrol ediniz." . PHP_EOL;
    echo "===============================================" . PHP_EOL;
    exit(1);
}

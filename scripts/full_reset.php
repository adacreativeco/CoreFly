<?php
$dbPath = __DIR__ . '/../storage/corefly.sqlite';
$schemaPath = __DIR__ . '/../database/migrations/sqlite_schema.sql';

try {
    // 1. Veritabanını Sıfırla
    if (file_exists($dbPath)) {
        unlink($dbPath);
    }
    
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Şemayı Yükle
    echo "Semalar yukleniyor...\n";
    $sql = file_get_contents($schemaPath);
    // SQLite exec çoklu sorguyu desteklemeyebilir, split edelim
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        if (trim($statement)) {
            $pdo->exec($statement);
        }
    }

    // 3. Admin Kullanıcısını ve Rolleri Oluştur (Seed)
    echo "Seed verileri yukleniyor...\n";
    
    // Rolleri ekle
    $pdo->exec("INSERT INTO roles (id, tenant_id, name, is_system, status) VALUES ('super-admin-role', 'default', 'Super Admin', 1, 'active')");
    
    // Admin Kullanıcısını ekle
    $hash = password_hash('123456', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, first_name, last_name, role_id, status, tenant_id, email_verified, preferences) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '{}')");
    
    $stmt->execute([
        bin2hex(random_bytes(16)), 
        'admin', 
        'admin@corefly.com', 
        $hash, 
        'Super', 
        'Admin', 
        'super-admin-role', // Role ID ile eşleşmeli
        'active', 
        'default'
    ]);

    // İzinleri Ekle (Full yetki)
    // Normalde permission tablosu dolar ama biz bypass edip role_permissions'a '*' basabiliriz veya kodda kontrol edebiliriz.
    // BaseController::hasPermission metodu '*' yetkisini tanıyor.
    
    $pdo->exec("INSERT INTO role_permissions (role_id, permission) VALUES ('super-admin-role', '*')");

    echo "Basariyla tamamlandi.\n";
    echo "Email: admin@corefly.com\n";
    echo "Sifre: 123456\n";

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}

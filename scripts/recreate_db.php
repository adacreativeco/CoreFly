<?php
$dbPath = __DIR__ . '/../storage/corefly.sqlite';
$backupPath = __DIR__ . '/../storage/corefly.sqlite.bak.' . time();

// 1. Yedekle ve Sil
if (file_exists($dbPath)) {
    copy($dbPath, $backupPath);
    unlink($dbPath);
    echo "Eski veritabani yedeklendi ve silindi.\n";
}

// 2. Yeniden Oluştur
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabloyu oluştur
    $pdo->exec("CREATE TABLE users (
        id TEXT PRIMARY KEY,
        username TEXT,
        email TEXT UNIQUE,
        password TEXT,
        first_name TEXT,
        last_name TEXT,
        role_id TEXT,
        status TEXT DEFAULT 'active',
        tenant_id TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        login_attempts INTEGER DEFAULT 0,
        locked_until DATETIME NULL,
        last_login_at DATETIME NULL,
        avatar TEXT NULL
    )");

    // Admin ekle
    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, first_name, last_name, role_id, status, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Şifre: 123456
    $hash = password_hash('123456', PASSWORD_BCRYPT);
    
    $stmt->execute([
        bin2hex(random_bytes(16)), 
        'admin', 
        'admin@corefly.com', 
        $hash, 
        'Super', 
        'Admin', 
        'super-admin', 
        'active', 
        'default'
    ]);

    echo "Yeni veritabani ve admin kullanicisi olusturuldu.\n";
    echo "Email: admin@corefly.com\n";
    echo "Sifre: 123456\n";

    // Doğrulama
    $check = $pdo->query("SELECT * FROM users WHERE email = 'admin@corefly.com'")->fetch(PDO::FETCH_ASSOC);
    if ($check) {
        echo "VERIFICATION: User found in DB.\n";
    } else {
        echo "VERIFICATION: User NOT found!\n";
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}

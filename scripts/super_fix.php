<?php
require_once __DIR__ . '/../vendor/autoload.php';

use CoreFly\Utils\Database;

// Force create admin user in BOTH locations to be 100% sure
$paths = [
    __DIR__ . '/../storage/corefly.sqlite',
    __DIR__ . '/../database/corfly.sqlite'
];

foreach ($paths as $path) {
    echo "Processing DB: $path\n";
    try {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ensure users table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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

        // Reset user
        $email = 'admin@corefly.com';
        $password = '123456';
        $hash = password_hash($password, PASSWORD_BCRYPT); // Use BCRYPT specifically
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch();

        if ($exists) {
            $pdo->prepare("UPDATE users SET password = ?, status = 'active', login_attempts = 0, locked_until = NULL WHERE email = ?")
                ->execute([$hash, $email]);
            echo "  -> User UPDATED with password '$password'\n";
        } else {
            $pdo->prepare("INSERT INTO users (id, username, email, password, first_name, last_name, role_id, status, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([uniqid(), 'admin', $email, $hash, 'Super', 'Admin', 'super-admin', 'active', 'default']);
            echo "  -> User CREATED with password '$password'\n";
        }

    } catch (Exception $e) {
        echo "  -> Error: " . $e->getMessage() . "\n";
    }
}

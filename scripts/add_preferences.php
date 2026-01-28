<?php
$dbPath = __DIR__ . '/../storage/corefly.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add missing columns if not exists
    $columns = [
        'preferences' => 'TEXT DEFAULT NULL'
    ];

    foreach ($columns as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $type");
            echo "Added column: $col\n";
        } catch (Exception $e) {
            // Column likely exists, ignore
            echo "Column $col might already exist or error: " . $e->getMessage() . "\n";
        }
    }

    echo "Veritabani semasi guncellendi (preferences).\n";

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}

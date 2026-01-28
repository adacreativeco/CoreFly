<?php
$dbPath = __DIR__ . '/../storage/corefly.sqlite';
$schemaPath = __DIR__ . '/../database/migrations/sqlite_schema.sql';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Şemayı oku
    $sql = file_get_contents($schemaPath);
    
    // İşlem (Transaction) başlat
    $pdo->beginTransaction();
    
    // Sorguları tek tek çalıştır (SQLite çoklu sorguyu tek seferde desteklemeyebilir, ama exec genelde çalışır)
    $pdo->exec($sql);
    
    $pdo->commit();
    
    echo "Veritabani tablolari basariyla olusturuldu.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "HATA: " . $e->getMessage() . "\n";
}

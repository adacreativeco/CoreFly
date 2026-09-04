<?php

echo "=== COREFLY TÜM FAZLAR GENEL ENTEGRASYON TESTİ ===\n";

$tests = [
    'CRM & Satış' => 'test_crm.php',
    'Envanter & Stok' => 'test_inventory.php',
    'Ön Muhasebe & Finans' => 'test_accounting.php',
    'İletişim & Destek & Takvim' => 'test_support_calendar.php',
    'Özel Modüller & Süper Admin (Faz 5)' => 'test_phase5.php',
    'İleri Düzey Modüller (İzinler, Bordro, Görevler, Tedarikçiler)' => 'test_advanced_features.php'
];

$allPassed = true;

foreach ($tests as $phaseName => $script) {
    echo "\n--- [TEST BAŞLATILIYOR]: $phaseName ($script) ---\n";
    $cmd = "php " . escapeshellarg(__DIR__ . '/' . $script);
    passthru($cmd, $returnVar);
    if ($returnVar !== 0) {
        echo "❌ HATA: $phaseName başarısız oldu!\n";
        $allPassed = false;
    } else {
        echo "✅ BAŞARILI: $phaseName sorunsuz geçti.\n";
    }
}

echo "\n==============================================\n";
if ($allPassed) {
    echo "🎉 TÜM FAZLAR (1, 2, 3, 4, 5) %100 BAŞARIYLA TAMAMLANDI VE TEST EDİLDİ!\n";
} else {
    echo "⚠️ Bazı testlerde sorunlar tespit edildi.\n";
}

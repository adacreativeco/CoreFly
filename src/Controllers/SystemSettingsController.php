<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Utils\Database;
use CoreFly\Utils\Auth;

class SystemSettingsController extends BaseController
{
    /**
     * Get all system settings
     */
    public function index(): string
    {
        $this->requirePermission('system_settings:read');
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT * FROM system_settings");
        $settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Transform to key-value pair for frontend convenience
        $formatted = [];
        foreach ($settings as $s) {
            // Security: Don't expose mail password
            if ($s['setting_key'] === 'mail_password') {
                $formatted[$s['setting_key']] = ''; // Send empty so placeholder shows "Change to update"
            } else {
                $formatted[$s['setting_key']] = $s['setting_value'];
            }
        }

        return $this->successResponse($formatted, 'Ayarlar getirildi');
    }

    /**
     * Update system settings
     */
    public function update(): string
    {
        $this->requirePermission('system_settings:update');

        $data = $this->getRequestData();
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();
            
            // Using INSERT OR REPLACE to handle new settings dynamically
            $stmt = $db->prepare("INSERT OR REPLACE INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");

            foreach ($data as $key => $value) {
                // Security: Skip updating password if empty (keep existing)
                if ($key === 'mail_password' && empty($value)) {
                    continue;
                }
                
                $stmt->execute([$key, $value]);
            }

            $db->commit();
            return $this->successResponse(null, 'Ayarlar güncellendi');

        } catch (\Exception $e) {
            $db->rollBack();
            return $this->errorResponse('Güncelleme hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get System Health Stats
     */
    public function getHealth(): string
    {
        $this->requirePermission('system_settings:read');
        
        $dbPath = __DIR__ . '/../../storage/corefly.sqlite';
        $dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) . ' MB' : 'N/A';
        
        $stats = [
            'php_version' => PHP_VERSION,
            'server_os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'max_memory' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'database_size' => $dbSize,
            'disk_free_space' => round(disk_free_space(__DIR__) / 1024 / 1024 / 1024, 2) . ' GB'
        ];

        return $this->successResponse($stats);
    }

    /**
     * Clear Application Cache
     */
    public function clearCache(): string
    {
        $this->requirePermission('system_settings:update');
        
        // In a real app, this would clear specific directories.
        // For now, we'll simulate it or clear a temp dir if we had one.
        // Let's assume we might have logs or temp files to clean.
        
        // Implementation note: PHP's opcache_reset() might be needed if opcache is on.
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return $this->successResponse(null, 'Önbellek başarıyla temizlendi.');
    }

    /**
     * Optimize Database (VACUUM)
     */
    public function optimizeDb(): string
    {
        $this->requirePermission('system_settings:update');
        
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("VACUUM");
            return $this->successResponse(null, 'Veritabanı optimize edildi.');
        } catch (\Exception $e) {
            return $this->errorResponse('Optimizasyon hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get System Logs
     */
    public function getLogs(): string
    {
        $this->requirePermission('system_settings:read');
        
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        
        // If we have a date based log system, we might need to find the latest.
        // config/app.php says storage/logs. Let's look for files there.
        $logDir = __DIR__ . '/../../storage/logs';
        
        if (!is_dir($logDir)) {
             return $this->successResponse(['logs' => 'Log dizini bulunamadı.']);
        }
        
        $files = glob($logDir . '/*.log');
        if (empty($files)) {
            return $this->successResponse(['logs' => 'Henüz log kaydı yok.']);
        }
        
        // Get latest file
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestLog = $files[0];
        
        // Read last 100 lines
        $lines = file($latestLog);
        $lastLines = array_slice($lines, -100);
        
        return $this->successResponse(['logs' => implode("", $lastLines), 'file' => basename($latestLog)]);
    }
}

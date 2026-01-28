<?php

declare(strict_types=1);

namespace CoreFly\Services;

use CoreFly\Utils\Database;

class PermissionsService
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function getPermissionsForRole(string $roleId): array
    {
        try {
            // Fix for SQLite/PDO fetching
            $stmt = $this->db->prepare('SELECT permission FROM role_permissions WHERE role_id = ?');
            $stmt->execute([$roleId]);
            // Fetch column directly
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Exception $e) {
            error_log("PermissionsService error: " . $e->getMessage());
        }
        
        $cfg = $this->config['roles'][$roleId]['permissions'] ?? [];
        return $cfg;
    }
}


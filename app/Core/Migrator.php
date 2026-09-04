<?php

namespace App\Core;

use PDOException;

class Migrator
{
    private $db;
    private $migrationsPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = __DIR__ . '/../../storage/database/migrations';
    }

    public function run()
    {
        $driver = $this->getDriver();
        echo "Database Driver: " . strtoupper($driver) . PHP_EOL;

        $this->createMigrationsTable();
        
        $executedMigrations = $this->getExecutedMigrations();
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executedMigrations)) {
                echo "Migrating: $filename" . PHP_EOL;
                $this->executeMigration($file);
                $this->logMigration($filename);
                echo "Migrated:  $filename" . PHP_EOL;
            }
        }
    }

    private function createMigrationsTable()
    {
        $driver = $this->getDriver();
        
        if ($driver === 'mysql') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        }

        $this->db->query($sql);
    }

    private function getExecutedMigrations()
    {
        $sql = "SELECT migration FROM migrations";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function executeMigration($file)
    {
        $sql = file_get_contents($file);
        $adaptedSql = $this->adaptSqlForDriver($sql, $this->getDriver());
        $this->db->getConnection()->exec($adaptedSql);
    }

    private function logMigration($filename)
    {
        $sql = "INSERT INTO migrations (migration) VALUES (?)";
        $this->db->query($sql, [$filename]);
    }

    private function adaptSqlForDriver(string $sql, string $driver): string
    {
        if ($driver === 'sqlite') {
            return $sql;
        }

        if ($driver === 'mysql') {
            // Convert SQLite AUTOINCREMENT to MySQL standard
            $sql = preg_replace('/INTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT/i', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
            return $sql;
        }

        if ($driver === 'pgsql') {
            // Convert SQLite/MySQL AUTOINCREMENT to PostgreSQL SERIAL
            $sql = preg_replace('/INTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT/i', 'SERIAL PRIMARY KEY', $sql);
            $sql = preg_replace('/INT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'SERIAL PRIMARY KEY', $sql);
            // Convert DATETIME to TIMESTAMP
            $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql);
            // Convert integer boolean defaults
            $sql = preg_replace('/BOOLEAN\s+DEFAULT\s+0/i', 'BOOLEAN DEFAULT FALSE', $sql);
            $sql = preg_replace('/BOOLEAN\s+DEFAULT\s+1/i', 'BOOLEAN DEFAULT TRUE', $sql);
            return $sql;
        }

        return $sql;
    }
    
    private function getDriver(): string
    {
        $driver = $this->db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return strtolower($driver);
    }
}

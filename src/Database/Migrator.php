<?php

declare(strict_types=1);

namespace CoreFly\Database;

use CoreFly\Utils\Database;
use Exception;

class Migrator
{
    private Database $db;
    private string $migrationsPath;

    public function __construct(string $migrationsPath)
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        // Adjust for MySQL if needed (AUTOINCREMENT -> AUTO_INCREMENT)
        if ($this->db->getConfig()['driver'] === 'mysql') {
             $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        }
        
        $this->db->exec($sql);
    }

    public function run(): void
    {
        $files = glob($this->migrationsPath . '/*.php');
        sort($files); // Ensure order by timestamp

        $executedMigrations = $this->getExecutedMigrations();
        $batch = $this->getNextBatch();
        $count = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if (in_array($migrationName, $executedMigrations)) {
                continue;
            }

            require_once $file;
            
            // Extract class name from file content or convention
            // Convention: 2024_12_07_001_create_users_table.php -> CreateUsersTable
            // But simple way: read file and regex class name, or use return new class approach
            // Let's assume file returns the migration instance or declares a class with predictable name
            // Better yet: Let's use a simple class name convention based on filename or regex search
            
            $className = $this->getClassNameFromFile($file);
            if (!$className) {
                echo "Could not determine class name for $migrationName\n";
                continue;
            }

            echo "Migrating: $migrationName\n";
            
            try {
                /** @var Migration $migration */
                $migration = new $className();
                $migration->up();

                $this->logMigration($migrationName, $batch);
                echo "Migrated:  $migrationName\n";
                $count++;
            } catch (Exception $e) {
                echo "Error migrating $migrationName: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        if ($count === 0) {
            echo "Nothing to migrate.\n";
        }
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getNextBatch(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        return ((int)$stmt->fetchColumn()) + 1;
    }

    private function logMigration(string $name, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$name, $batch]);
    }

    private function getClassNameFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

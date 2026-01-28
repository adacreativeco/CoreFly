<?php

declare(strict_types=1);

namespace CoreFly\Utils;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    public ?PDO $pdo = null; // Renamed from connection to match migration usage
    private array $config;
    private string $currentTenant;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->currentTenant = 'default';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConfig(): array
    {
        return $this->config['database']['default'];
    }

    public function connect(?string $tenantId = null): PDO
    {
        if ($this->pdo !== null && $tenantId === $this->currentTenant) {
            return $this->pdo;
        }

        // Use existing tenant if not provided
        $targetTenant = $tenantId ?? $this->currentTenant ?? 'default';

        try {
            $dbConfig = $this->config['database']['default'];
            $driver = $dbConfig['driver'];
            if ($driver === 'sqlite') {
                $dbPath = $dbConfig['database'];
                // FORCE ABSOLUTE PATH LOGGING - DISABLED to prevent API corruption
                // error_log("DATABASE_DEBUG: Connecting to SQLite at: " . realpath($dbPath) . " (Configured: $dbPath)");

                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $dsn = 'sqlite:' . $dbPath;
                $this->pdo = new PDO(
                    $dsn,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $dbConfig['host'],
                    $dbConfig['port'],
                    $dbConfig['database'],
                    $dbConfig['charset']
                );
                $this->pdo = new PDO(
                    $dsn,
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']} COLLATE {$dbConfig['collation']}"
                    ]
                );
            }

            $this->currentTenant = $targetTenant;
            
            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect($this->currentTenant);
        }
        return $this->pdo;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
        self::$instance = null;
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }

    public function prepare(string $sql): \PDOStatement
    {
        return $this->getConnection()->prepare($sql);
    }

    public function query(string $sql): \PDOStatement
    {
        return $this->getConnection()->query($sql);
    }

    public function exec(string $sql): int
    {
        return $this->getConnection()->exec($sql);
    }

    public function quote(string $string): string
    {
        return $this->getConnection()->quote($string);
    }

    public function getCurrentTenant(): string
    {
        return $this->currentTenant;
    }

    public function setCurrentTenant(string $tenantId): void
    {
        $this->currentTenant = $tenantId;
    }

    public function createTenantConnection(string $tenantId): PDO
    {
        return $this->connect($tenantId);
    }

    public function executeMigration(string $sql): void
    {
        try {
            $this->beginTransaction();
            $this->exec($sql);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw new Exception("Migration failed: " . $e->getMessage());
        }
    }

    public function seedTable(string $table, array $data): void
    {
        try {
            $this->beginTransaction();
            
            foreach ($data as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                $placeholders = array_fill(0, count($columns), '?');
                
                $sql = sprintf(
                    "INSERT INTO %s (%s) VALUES (%s)",
                    $table,
                    implode(', ', $columns),
                    implode(', ', $placeholders)
                );
                
                $stmt = $this->prepare($sql);
                $stmt->execute($values);
            }
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw new Exception("Seeding failed: " . $e->getMessage());
        }
    }

    public function tableExists(string $table): bool
    {
        $driver = $this->config['database']['default']['driver'];
        if ($driver === 'sqlite') {
            $stmt = $this->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
            $stmt->execute(['table' => $table]);
            $row = $stmt->fetch();
            return $row !== false;
        }
        $stmt = $this->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        return $stmt->rowCount() > 0;
    }

    public function getTableSchema(string $table): array
    {
        $driver = $this->config['database']['default']['driver'];
        if ($driver === 'sqlite') {
            $stmt = $this->query("PRAGMA table_info('{$table}')");
            return $stmt->fetchAll();
        }
        $stmt = $this->query("SHOW FULL COLUMNS FROM {$table}");
        return $stmt->fetchAll();
    }

    public function getVersion(): string
    {
        return $this->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

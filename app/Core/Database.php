<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    private $tenantId = null;
    private $driver = 'sqlite';

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $default = $config['default'] ?? 'sqlite';
        $dbConfig = $config['connections'][$default] ?? $config['connections']['sqlite'];
        $this->driver = $default;

        try {
            if ($default === 'sqlite') {
                $dsn = "sqlite:" . $dbConfig['database'];
                $this->connection = new PDO($dsn);
            } elseif ($default === 'pgsql') {
                $sslmode = $dbConfig['sslmode'] ?? 'prefer';
                $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};sslmode={$sslmode}";
                $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                if (!empty($dbConfig['schema'])) {
                    $this->connection->exec("SET search_path TO " . $dbConfig['schema']);
                }
            } else {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
                $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']}"
                ]);
            }
            
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Error (" . $default . "): " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function isMysql(): bool
    {
        return $this->driver === 'mysql';
    }

    public function isPgsql(): bool
    {
        return $this->driver === 'pgsql';
    }

    public function isSqlite(): bool
    {
        return $this->driver === 'sqlite';
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Query Failed: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
}

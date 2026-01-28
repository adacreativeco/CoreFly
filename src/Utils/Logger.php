<?php

declare(strict_types=1);

namespace CoreFly\Utils;

class Logger
{
    private static $monolog = null;

    private static function ensureMonolog(): void
    {
        if (self::$monolog !== null) return;
        $loggerClass = '\\Monolog\\Logger';
        $handlerClass = '\\Monolog\\Handler\\RotatingFileHandler';
        if (class_exists($loggerClass) && class_exists($handlerClass)) {
            $config = require __DIR__ . '/../../config/app.php';
            $path = rtrim($config['logging']['path'] ?? 'storage/logs', '/');
            if (!is_dir($path)) mkdir($path, 0775, true);
            $logger = new $loggerClass($config['app']['name'] ?? 'CoreFly');
            $handler = new $handlerClass($path . '/app.log', 30);
            $logger->pushHandler($handler);
            self::$monolog = $logger;
        }
    }

    public static function write(string $level, string $message): void
    {
        self::ensureMonolog();
        if (self::$monolog) {
            self::$monolog->log($level, $message);
            return;
        }
        $config = require __DIR__ . '/../../config/app.php';
        $path = rtrim($config['logging']['path'] ?? 'storage/logs', '/');
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
        $file = $path . '/' . date('Y-m-d') . '.log';
        $line = sprintf('[%s] %s %s%s', strtoupper($level), date('Y-m-d H:i:s'), $message, PHP_EOL);
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    public static function error(string $message): void
    {
        self::write('error', $message);
    }
}

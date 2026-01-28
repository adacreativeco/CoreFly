<?php

declare(strict_types=1);

namespace CoreFly\Utils;

class RateLimiter
{
    public static function allow(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $baseDir = __DIR__ . '/../../storage/ratelimits';
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }
        $bucket = $baseDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;

        $data = ['requests' => []];
        if (file_exists($bucket)) {
            $raw = file_get_contents($bucket);
            $data = json_decode($raw, true) ?: ['requests' => []];
        }

        $data['requests'] = array_values(array_filter($data['requests'], fn($ts) => $ts >= $windowStart));
        if (count($data['requests']) >= $maxRequests) {
            return false;
        }

        $data['requests'][] = $now;
        file_put_contents($bucket, json_encode($data), LOCK_EX);
        return true;
    }
}


<?php

declare(strict_types=1);

namespace CoreFly\Tests;

class BaseTestCase
{
    protected function assertTrue($cond): void
    {
        if (!$cond) {
            throw new \Exception('Assertion failed: not true');
        }
    }

    protected function assertArrayHasKey($key, $arr): void
    {
        if (!is_array($arr) || !array_key_exists($key, $arr)) {
            throw new \Exception('Assertion failed: array key missing');
        }
    }

    protected function assertIsArray($val): void
    {
        if (!is_array($val)) {
            throw new \Exception('Assertion failed: not array');
        }
    }

    protected function assertSame($expected, $actual): void
    {
        if ($expected !== $actual) {
            throw new \Exception('Assertion failed: values not same');
        }
    }

    protected function assertGreaterThanOrEqual($expected, $actual): void
    {
        if (!($actual >= $expected)) {
            throw new \Exception('Assertion failed: not >=');
        }
    }

    protected function assertContains($needle, $haystack, $message = ''): void
    {
        if (!in_array($needle, $haystack)) {
            throw new \Exception($message ?: "Assertion failed: $needle not found in array");
        }
    }

    protected function assertNotContains($needle, $haystack, $message = ''): void
    {
        if (in_array($needle, $haystack)) {
            throw new \Exception($message ?: "Assertion failed: $needle found in array");
        }
    }
}


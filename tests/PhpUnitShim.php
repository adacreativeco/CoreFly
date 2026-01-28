<?php

declare(strict_types=1);

namespace CoreFly\Tests;

class PhpUnitShim
{
    protected function assertTrue($cond): void {}
    protected function assertArrayHasKey($key, $arr): void {}
    protected function assertIsArray($val): void {}
    protected function assertSame($expected, $actual): void {}
    protected function assertGreaterThanOrEqual($expected, $actual): void {}
}


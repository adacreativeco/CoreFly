<?php

declare(strict_types=1);

namespace CoreFly\Database;

use CoreFly\Utils\Database;

abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    abstract public function up(): void;
    abstract public function down(): void;
}

<?php

require_once __DIR__ . '/../../tests/bootstrap.php';

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Running migration: create_projects_table\n";

// Projects Table
$sqlProjects = "CREATE TABLE IF NOT EXISTS projects (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    project_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'planning',
    start_date DATETIME,
    end_date DATETIME,
    manager_id VARCHAR(36),
    department_id VARCHAR(36),
    progress INT DEFAULT 0,
    budget DECIMAL(15, 2),
    priority VARCHAR(50) DEFAULT 'medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($sqlProjects);
    echo "Created table: projects\n";
} catch (Exception $e) {
    echo "Error creating projects table: " . $e->getMessage() . "\n";
}

// Project Members Table
$sqlMembers = "CREATE TABLE IF NOT EXISTS project_members (
    id VARCHAR(36) PRIMARY KEY,
    project_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
)";

try {
    $db->exec($sqlMembers);
    echo "Created table: project_members\n";
} catch (Exception $e) {
    echo "Error creating project_members table: " . $e->getMessage() . "\n";
}

echo "Migration completed.\n";

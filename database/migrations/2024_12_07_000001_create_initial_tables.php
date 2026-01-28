<?php

declare(strict_types=1);

use CoreFly\Database\Migration;

class CreateInitialTables extends Migration
{
    public function up(): void
    {
        // Users
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            username VARCHAR(255),
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50),
            avatar TEXT,
            department_id VARCHAR(36),
            role_id VARCHAR(50),
            status VARCHAR(50) DEFAULT 'active',
            email_verified TINYINT DEFAULT 0,
            two_factor_enabled TINYINT DEFAULT 0,
            two_factor_secret TEXT,
            last_login_at DATETIME,
            login_attempts INT DEFAULT 0,
            locked_until DATETIME,
            preferences TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Tenants
        $this->db->exec("CREATE TABLE IF NOT EXISTS tenants (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            domain VARCHAR(255),
            logo TEXT,
            theme_config TEXT,
            active_modules TEXT,
            settings TEXT,
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Roles & Permissions
        $this->db->exec("CREATE TABLE IF NOT EXISTS roles (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            is_system TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS role_permissions (
            id VARCHAR(36) PRIMARY KEY,
            role_id VARCHAR(36) NOT NULL,
            permission VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Core Modules Tables
        
        // Announcements
        $this->db->exec("CREATE TABLE IF NOT EXISTS announcements (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            priority VARCHAR(50) DEFAULT 'normal',
            is_pinned TINYINT DEFAULT 0,
            created_by VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'published',
            type VARCHAR(50) DEFAULT 'general',
            target_type VARCHAR(50) DEFAULT 'all',
            target_values TEXT,
            expiry_date DATETIME
        )");

        // Tasks
        $this->db->exec("CREATE TABLE IF NOT EXISTS tasks (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            project_id VARCHAR(36),
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status VARCHAR(50) DEFAULT 'todo',
            priority VARCHAR(50) DEFAULT 'medium',
            assigned_to VARCHAR(36),
            created_by VARCHAR(36),
            due_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Events (Calendar)
        $this->db->exec("CREATE TABLE IF NOT EXISTS events (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            location VARCHAR(255),
            type VARCHAR(50) DEFAULT 'meeting',
            created_by VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Helpdesk
        $this->db->exec("CREATE TABLE IF NOT EXISTS tickets (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            category_id VARCHAR(36),
            subject VARCHAR(255) NOT NULL,
            description TEXT,
            status VARCHAR(50) DEFAULT 'open',
            priority VARCHAR(50) DEFAULT 'medium',
            assigned_to VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Politics Module Tables
        $this->db->exec("CREATE TABLE IF NOT EXISTS politics_voters (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            tc_no VARCHAR(11),
            phone VARCHAR(20),
            email VARCHAR(255),
            region VARCHAR(100),
            neighborhood VARCHAR(100),
            box_number VARCHAR(50),
            status VARCHAR(50) DEFAULT 'undecided',
            notes TEXT,
            created_by VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS politics_boxes (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            box_number VARCHAR(50) NOT NULL,
            region VARCHAR(100),
            neighborhood VARCHAR(100),
            school_name VARCHAR(255),
            official_name VARCHAR(255),
            official_phone VARCHAR(20),
            observer_name VARCHAR(255),
            observer_phone VARCHAR(20),
            voter_count INT DEFAULT 0,
            valid_votes INT DEFAULT 0,
            invalid_votes INT DEFAULT 0,
            created_by VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS politics_volunteers (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            role VARCHAR(50),
            assigned_box_id VARCHAR(36),
            status VARCHAR(50) DEFAULT 'active',
            notes TEXT,
            created_by VARCHAR(36),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function down(): void
    {
        // Reverse order
        $this->db->exec("DROP TABLE IF EXISTS politics_volunteers");
        $this->db->exec("DROP TABLE IF EXISTS politics_boxes");
        $this->db->exec("DROP TABLE IF EXISTS politics_voters");
        $this->db->exec("DROP TABLE IF EXISTS tickets");
        $this->db->exec("DROP TABLE IF EXISTS events");
        $this->db->exec("DROP TABLE IF EXISTS tasks");
        $this->db->exec("DROP TABLE IF EXISTS announcements");
        $this->db->exec("DROP TABLE IF EXISTS role_permissions");
        $this->db->exec("DROP TABLE IF EXISTS roles");
        $this->db->exec("DROP TABLE IF EXISTS tenants");
        $this->db->exec("DROP TABLE IF EXISTS users");
    }
}

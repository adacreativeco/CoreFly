<?php

use CoreFly\Utils\Database;

class CreateBillingTables
{
    public function up()
    {
        $db = Database::getInstance();
        
        // Plans Table
        $sqlPlans = "CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL, -- 'free', 'pro', 'enterprise'
            price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'USD',
            limits JSON, -- {'users': 5, 'storage_gb': 1}
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->pdo->exec($sqlPlans);

        // Usage Records Table (Metering)
        $sqlUsage = "CREATE TABLE IF NOT EXISTS usage_records (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            metric_key VARCHAR(50) NOT NULL, -- 'api_calls', 'storage_mb', 'active_users'
            value DECIMAL(15, 4) NOT NULL,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tenant_metric (tenant_id, metric_key),
            INDEX idx_recorded_at (recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->pdo->exec($sqlUsage);
        
        // Invoices Table (Simplified)
        $sqlInvoices = "CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            status ENUM('pending', 'paid', 'failed', 'overdue') DEFAULT 'pending',
            period_start DATE,
            period_end DATE,
            pdf_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->pdo->exec($sqlInvoices);

        echo "Billing tables created successfully.\n";
    }
}

<?php

use CoreFly\Utils\Database;

$db = Database::getInstance();

echo "Running migration: 002_create_missing_tables\n";

// --- Accounting ---
$db->exec("CREATE TABLE IF NOT EXISTS accounting_accounts (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36),
    code VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    tax_number VARCHAR(50),
    tax_office VARCHAR(100),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS accounting_transactions (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36),
    account_id VARCHAR(36),
    type VARCHAR(20) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    category VARCHAR(100),
    invoice_id VARCHAR(36),
    payment_method VARCHAR(50),
    file_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounting_accounts(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS accounting_invoices (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36),
    account_id VARCHAR(36),
    number VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(15, 2) DEFAULT 0.00,
    tax_total DECIMAL(15, 2) DEFAULT 0.00,
    total DECIMAL(15, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'draft',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounting_accounts(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS accounting_invoice_items (
    id VARCHAR(36) PRIMARY KEY,
    invoice_id VARCHAR(36),
    description VARCHAR(255),
    quantity DECIMAL(10, 2) DEFAULT 1.00,
    unit_price DECIMAL(15, 2) DEFAULT 0.00,
    tax_rate DECIMAL(5, 2) DEFAULT 0.00,
    total DECIMAL(15, 2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES accounting_invoices(id) ON DELETE CASCADE
)");

// --- Donations ---
$db->exec("CREATE TABLE IF NOT EXISTS campaigns (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    target_amount DECIMAL(15, 2) DEFAULT 0,
    raised_amount DECIMAL(15, 2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'TRY',
    start_date DATE,
    end_date DATE,
    status VARCHAR(50) DEFAULT 'active',
    is_active BOOLEAN DEFAULT 1,
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS donors (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    type VARCHAR(50) DEFAULT 'individual',
    tax_id VARCHAR(50),
    company_name VARCHAR(255),
    notes TEXT,
    status VARCHAR(50) DEFAULT 'active',
    total_donated DECIMAL(15, 2) DEFAULT 0,
    last_donation_date DATETIME,
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS donations (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    donor_id VARCHAR(36),
    donor_name VARCHAR(255),
    donor_email VARCHAR(255),
    donor_phone VARCHAR(50),
    donor_address TEXT,
    donor_type VARCHAR(50),
    campaign_id VARCHAR(36),
    campaign_name VARCHAR(255),
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'TRY',
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(100),
    donation_type VARCHAR(50) DEFAULT 'one-time',
    purpose TEXT,
    notes TEXT,
    is_anonymous BOOLEAN DEFAULT 0,
    is_recurring BOOLEAN DEFAULT 0,
    recurring_frequency VARCHAR(50),
    recurring_end_date DATE,
    status VARCHAR(50) DEFAULT 'pending',
    processed_by VARCHAR(36),
    acknowledgment_sent BOOLEAN DEFAULT 0,
    tax_receipt_sent BOOLEAN DEFAULT 0,
    tax_receipt_number VARCHAR(100),
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// --- Messaging ---
$db->exec("CREATE TABLE IF NOT EXISTS message_groups (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'public',
    created_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    sender_id VARCHAR(36) NOT NULL,
    receiver_id VARCHAR(36),
    group_id VARCHAR(36),
    message TEXT,
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES message_groups(id) ON DELETE CASCADE
)");

// --- Helpdesk ---
$db->exec("CREATE TABLE IF NOT EXISTS ticket_categories (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(50) DEFAULT '#3b82f6',
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME,
    updated_at DATETIME
)");

$db->exec("CREATE TABLE IF NOT EXISTS ticket_messages (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    ticket_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    message TEXT NOT NULL,
    attachments TEXT,
    is_internal TINYINT(1) DEFAULT 0,
    created_at DATETIME
)");

// Ensure tickets table exists (might be in 001 but let's be safe or just alter)
// Assuming tickets table exists from 001, we just add columns if missing
$alterStatements = [
    "ALTER TABLE tickets ADD COLUMN category_id VARCHAR(36)",
    "ALTER TABLE tickets ADD COLUMN assigned_to VARCHAR(36)",
    "ALTER TABLE tickets ADD COLUMN updated_at DATETIME"
];
foreach ($alterStatements as $sql) {
    try {
        $db->exec($sql);
    } catch (\Exception $e) {}
}

// --- System Settings ---
$db->exec("CREATE TABLE IF NOT EXISTS system_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE,
    setting_value TEXT,
    setting_group TEXT DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- Dashboard / Core Modules ---
$db->exec("CREATE TABLE IF NOT EXISTS notifications (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data TEXT,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS announcements (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    department_id VARCHAR(36),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id VARCHAR(36) NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(20) DEFAULT 'published',
    type VARCHAR(50) DEFAULT 'general',
    target_type VARCHAR(50) DEFAULT 'all',
    target_values TEXT,
    expiry_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS tasks (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    department_id VARCHAR(36),
    project_id VARCHAR(36),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to VARCHAR(36),
    status VARCHAR(50) DEFAULT 'todo',
    priority VARCHAR(20) DEFAULT 'medium',
    due_date DATE,
    progress INT DEFAULT 0,
    estimated_hours DECIMAL(10, 2),
    tags TEXT,
    position INT DEFAULT 0,
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS events (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255),
    type VARCHAR(50) DEFAULT 'event',
    is_all_day TINYINT(1) DEFAULT 0,
    recurrence_rule TEXT,
    attendees TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// --- Tenant Alters ---
try {
    $stmt = $db->query("PRAGMA table_info(tenants)");
    $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN, 1);
    
    if (!in_array('active_modules', $columns)) {
        $db->exec("ALTER TABLE tenants ADD COLUMN active_modules TEXT");
    }
    if (!in_array('settings', $columns)) {
        $db->exec("ALTER TABLE tenants ADD COLUMN settings TEXT");
    }
} catch (\Throwable $e) {}

echo "Migration 002 completed successfully.\n";

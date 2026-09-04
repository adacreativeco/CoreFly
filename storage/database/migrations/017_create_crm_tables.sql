CREATE TABLE IF NOT EXISTS crm_customers (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    company VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    tax_id VARCHAR(50),
    industry VARCHAR(100),
    website VARCHAR(255),
    lead_source VARCHAR(100),
    status VARCHAR(50) DEFAULT 'lead',
    notes TEXT,
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS crm_deals (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    value DECIMAL(15, 2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'TRY',
    stage VARCHAR(50) DEFAULT 'lead', -- lead, proposal, negotiation, won, lost
    probability INT DEFAULT 0,
    expected_close_date DATE,
    notes TEXT,
    assigned_to VARCHAR(36),
    created_by VARCHAR(36),
    updated_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES crm_customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS crm_activities (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    related_to_type VARCHAR(50) NOT NULL, -- customer, deal
    related_to_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL, -- call, meeting, email, note
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    date DATETIME,
    status VARCHAR(50) DEFAULT 'completed', -- planned, completed
    created_by VARCHAR(36),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

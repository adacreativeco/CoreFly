CREATE TABLE positions (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    title TEXT NOT NULL,
    code TEXT,
    description TEXT,
    department_id TEXT,
    level TEXT,
    min_salary REAL,
    max_salary REAL,
    currency TEXT DEFAULT 'USD',
    reporting_to TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (reporting_to) REFERENCES positions(id) ON DELETE SET NULL
);

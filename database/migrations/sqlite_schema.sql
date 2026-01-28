BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS tenants (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    domain TEXT,
    database_name TEXT,
    status TEXT,
    settings TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    phone TEXT,
    avatar TEXT,
    department_id TEXT,
    role_id TEXT NOT NULL,
    status TEXT,
    email_verified INTEGER,
    two_factor_enabled INTEGER,
    two_factor_secret TEXT,
    last_login_at TEXT,
    login_attempts INTEGER,
    locked_until TEXT,
    preferences TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS departments (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    parent_id TEXT,
    manager_id TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS roles (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    permissions TEXT,
    is_system INTEGER,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS permissions (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id TEXT NOT NULL,
    permission TEXT NOT NULL,
    PRIMARY KEY (role_id, permission)
);

CREATE TABLE IF NOT EXISTS user_sessions (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    token TEXT UNIQUE NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    last_activity TEXT,
    expires_at TEXT NOT NULL,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS password_resets (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at TEXT NOT NULL,
    used INTEGER,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    user_id TEXT,
    action TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    old_values TEXT,
    new_values TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS notifications (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    data TEXT,
    is_read INTEGER,
    read_at TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS announcements (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    author_id TEXT NOT NULL,
    is_pinned INTEGER,
    priority TEXT,
    status TEXT DEFAULT 'published',
    type TEXT DEFAULT 'general',
    target_type TEXT DEFAULT 'all',
    target_values TEXT,
    expiry_date TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS tasks (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    title TEXT NOT NULL,
    description TEXT,
    assigned_to TEXT NOT NULL,
    status TEXT,
    priority TEXT,
    due_date TEXT,
    progress INTEGER,
    project_id TEXT,
    tags TEXT,
    estimated_hours REAL,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS subtasks (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    task_id TEXT NOT NULL,
    title TEXT NOT NULL,
    is_completed INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS events (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    user_id TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    start_date TEXT NOT NULL,
    end_date TEXT,
    location TEXT,
    type TEXT,
    color TEXT,
    all_day INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS documents (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    title TEXT NOT NULL,
    file_path TEXT,
    version TEXT,
    uploaded_by TEXT NOT NULL,
    mime_type TEXT,
    size INTEGER,
    parent_id TEXT,
    is_folder INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS messages (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    from_user_id TEXT NOT NULL,
    to_user_id TEXT NOT NULL,
    content TEXT NOT NULL,
    read_at TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS tickets (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    user_id TEXT NOT NULL,
    category_id TEXT,
    priority TEXT,
    status TEXT,
    title TEXT NOT NULL,
    description TEXT,
    assigned_to TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS ticket_categories (
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
);

CREATE TABLE IF NOT EXISTS ticket_messages (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    ticket_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    message TEXT NOT NULL,
    attachments TEXT,
    is_internal TINYINT(1) DEFAULT 0,
    created_at DATETIME
);

CREATE TABLE IF NOT EXISTS projects (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    department_id TEXT,
    title TEXT NOT NULL,
    description TEXT,
    project_type TEXT,
    status TEXT DEFAULT 'planning',
    start_date TEXT,
    end_date TEXT,
    manager_id TEXT,
    budget REAL DEFAULT 0,
    priority TEXT,
    progress INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS project_members (
    id TEXT PRIMARY KEY,
    project_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    role TEXT DEFAULT 'member',
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS suppliers (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    contact_person TEXT,
    email TEXT,
    phone TEXT,
    address TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS inventory (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    category TEXT,
    sku TEXT,
    barcode TEXT,
    quantity INTEGER DEFAULT 0,
    min_quantity INTEGER DEFAULT 0,
    max_quantity INTEGER DEFAULT 1000,
    unit TEXT,
    unit_cost REAL DEFAULT 0,
    total_value REAL DEFAULT 0,
    location TEXT,
    supplier_id TEXT,
    status TEXT DEFAULT 'active',
    expiry_date TEXT,
    created_by TEXT,
    updated_by TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS inventory_movements (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    inventory_id TEXT NOT NULL,
    user_id TEXT,
    old_quantity INTEGER,
    new_quantity INTEGER,
    adjustment INTEGER,
    reason TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS campaigns (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    type TEXT,
    target_amount REAL DEFAULT 0,
    raised_amount REAL DEFAULT 0,
    currency TEXT DEFAULT 'TRY',
    start_date TEXT,
    end_date TEXT,
    status TEXT DEFAULT 'active',
    is_active INTEGER DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS donations (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    donor_name TEXT NOT NULL,
    donor_email TEXT,
    donor_phone TEXT,
    campaign_id TEXT,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'TRY',
    payment_method TEXT,
    payment_status TEXT DEFAULT 'pending',
    transaction_id TEXT,
    is_recurring INTEGER DEFAULT 0,
    recurring_frequency TEXT,
    created_by TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS fields (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    type TEXT,
    location TEXT,
    coordinates TEXT,
    area REAL,
    area_unit TEXT,
    current_crop TEXT,
    planting_date TEXT,
    expected_harvest_date TEXT,
    status TEXT DEFAULT 'active',
    field_manager_id TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS field_activities (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    field_id TEXT NOT NULL,
    activity_type TEXT NOT NULL,
    description TEXT,
    activity_date TEXT,
    status TEXT DEFAULT 'scheduled',
    assigned_to TEXT,
    completed_by TEXT,
    completed_date TEXT,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS field_productions (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    field_id TEXT NOT NULL,
    season_year TEXT,
    crop_type TEXT,
    planted_area REAL,
    actual_yield REAL,
    total_revenue REAL,
    total_cost REAL,
    net_profit REAL,
    created_at TEXT,
    updated_at TEXT
);

COMMIT;

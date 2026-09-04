CREATE TABLE file_attachments (
    id TEXT PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    uploaded_by TEXT NOT NULL,
    entity_type TEXT, -- e.g., 'message', 'task', 'project'
    entity_id TEXT,
    file_name TEXT NOT NULL,
    original_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type TEXT,
    is_public INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

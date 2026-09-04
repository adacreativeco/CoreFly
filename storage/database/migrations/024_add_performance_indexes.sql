-- Performance Indexes for Multi-Tenant ERP Query Optimization

CREATE INDEX IF NOT EXISTS idx_users_tenant_email ON users(tenant_id, email);
CREATE INDEX IF NOT EXISTS idx_users_tenant_role ON users(tenant_id, role);

CREATE INDEX IF NOT EXISTS idx_departments_tenant ON departments(tenant_id);
CREATE INDEX IF NOT EXISTS idx_positions_dept ON positions(tenant_id, department_id);
CREATE INDEX IF NOT EXISTS idx_employees_tenant_user ON employees(tenant_id, user_id);
CREATE INDEX IF NOT EXISTS idx_employees_tenant_dept ON employees(tenant_id, department_id);

CREATE INDEX IF NOT EXISTS idx_projects_tenant_status ON projects(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_project_members_proj_user ON project_members(project_id, user_id);
CREATE INDEX IF NOT EXISTS idx_project_tasks_proj_status ON project_tasks(project_id, status);
CREATE INDEX IF NOT EXISTS idx_project_tasks_assignee ON project_tasks(assignee_id);

CREATE INDEX IF NOT EXISTS idx_company_tasks_tenant_status ON company_tasks(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_company_tasks_assignee ON company_tasks(assigned_to);

CREATE INDEX IF NOT EXISTS idx_conv_part_user ON conversation_participants(user_id);
CREATE INDEX IF NOT EXISTS idx_conv_part_conv ON conversation_participants(conversation_id);
CREATE INDEX IF NOT EXISTS idx_messages_conv_created ON messages(conversation_id, created_at);

CREATE INDEX IF NOT EXISTS idx_file_attachments_tenant ON file_attachments(tenant_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant_created ON audit_logs(tenant_id, created_at);

CREATE INDEX IF NOT EXISTS idx_crm_customers_tenant ON crm_customers(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_crm_deals_tenant_stage ON crm_deals(tenant_id, stage);
CREATE INDEX IF NOT EXISTS idx_crm_deals_cust ON crm_deals(customer_id);
CREATE INDEX IF NOT EXISTS idx_crm_activities_tenant ON crm_activities(tenant_id, related_to_type, related_to_id);

CREATE INDEX IF NOT EXISTS idx_inv_products_tenant_cat ON inventory_products(tenant_id, category_id);
CREATE INDEX IF NOT EXISTS idx_inv_products_sku ON inventory_products(tenant_id, sku);
CREATE INDEX IF NOT EXISTS idx_inv_movements_prod ON inventory_movements(product_id, created_at);
CREATE INDEX IF NOT EXISTS idx_inv_suppliers_tenant ON inventory_suppliers(tenant_id);

CREATE INDEX IF NOT EXISTS idx_acc_invoices_tenant_type ON accounting_invoices(tenant_id, type, status);
CREATE INDEX IF NOT EXISTS idx_acc_trans_tenant_account ON accounting_transactions(tenant_id, account_id);
CREATE INDEX IF NOT EXISTS idx_acc_trans_date ON accounting_transactions(date);

CREATE INDEX IF NOT EXISTS idx_helpdesk_tenant_status ON helpdesk_tickets(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_helpdesk_messages_ticket ON helpdesk_messages(ticket_id);
CREATE INDEX IF NOT EXISTS idx_announcements_tenant ON announcements(tenant_id, created_at);
CREATE INDEX IF NOT EXISTS idx_calendar_tenant_time ON calendar_events(tenant_id, start_date);
CREATE INDEX IF NOT EXISTS idx_field_tasks_tenant_status ON field_tasks(tenant_id, status);

CREATE INDEX IF NOT EXISTS idx_leave_req_tenant_status ON hr_leave_requests(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_leave_req_employee ON hr_leave_requests(employee_id);
CREATE INDEX IF NOT EXISTS idx_payrolls_tenant_period ON hr_payrolls(tenant_id, period);
CREATE INDEX IF NOT EXISTS idx_payrolls_employee ON hr_payrolls(employee_id);

CREATE INDEX IF NOT EXISTS idx_role_perms_role ON role_permissions(role_id, permission);

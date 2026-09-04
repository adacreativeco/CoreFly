-- Migration 025: Official GIB E-Invoice and E-Archive Fields

ALTER TABLE accounting_invoices ADD COLUMN ettn VARCHAR(36);
ALTER TABLE accounting_invoices ADD COLUMN einvoice_type VARCHAR(20) DEFAULT 'none';
ALTER TABLE accounting_invoices ADD COLUMN profile_id VARCHAR(50) DEFAULT 'TICARIFATURA';
ALTER TABLE accounting_invoices ADD COLUMN gib_status_code VARCHAR(20) DEFAULT NULL;
ALTER TABLE accounting_invoices ADD COLUMN gib_status_description VARCHAR(255) DEFAULT NULL;
ALTER TABLE accounting_invoices ADD COLUMN ubl_xml TEXT DEFAULT NULL;
ALTER TABLE accounting_invoices ADD COLUMN sent_at DATETIME DEFAULT NULL;
ALTER TABLE accounting_invoices ADD COLUMN integrator VARCHAR(50) DEFAULT 'gib_portal';

CREATE INDEX IF NOT EXISTS idx_acc_invoices_ettn ON accounting_invoices(ettn);
CREATE INDEX IF NOT EXISTS idx_acc_invoices_einvoice ON accounting_invoices(tenant_id, einvoice_type);

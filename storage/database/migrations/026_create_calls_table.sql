-- Migration 026: VoIP & Video Calling Support (WebRTC Signaling & History)

CREATE TABLE IF NOT EXISTS call_history (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    caller_id VARCHAR(36) NOT NULL,
    callee_id VARCHAR(36) NOT NULL,
    call_type VARCHAR(20) DEFAULT 'audio', -- audio, video
    status VARCHAR(20) DEFAULT 'initiated', -- initiated, ringing, answered, ended, missed, rejected
    started_at DATETIME,
    ended_at DATETIME,
    duration_seconds INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (callee_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS call_signals (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    call_id VARCHAR(36) NOT NULL,
    sender_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL, -- offer, answer, ice-candidate, end
    payload TEXT NOT NULL,
    is_processed BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (call_id) REFERENCES call_history(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_call_history_tenant ON call_history(tenant_id);
CREATE INDEX IF NOT EXISTS idx_call_history_caller ON call_history(tenant_id, caller_id);
CREATE INDEX IF NOT EXISTS idx_call_history_callee ON call_history(tenant_id, callee_id);
CREATE INDEX IF NOT EXISTS idx_call_signals_lookup ON call_signals(call_id, sender_id, is_processed);

-- Schema for mri AI consultant backend
-- Run this once after creating the database on Hostinger

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    problem TEXT,
    consent_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    notes TEXT,
    INDEX idx_email (email),
    INDEX idx_created (created_at),
    INDEX idx_deleted (deleted_at)
);

CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    email_hash VARCHAR(64),
    summary TEXT,
    message_count INT DEFAULT 0,
    consent_to_store BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME,
    deleted_at DATETIME,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at),
    INDEX idx_deleted (deleted_at)
);

CREATE TABLE IF NOT EXISTS conversation_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL,
    content LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at),
    CONSTRAINT fk_session FOREIGN KEY (session_id) REFERENCES conversations(session_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(64) NOT NULL,
    email_hash VARCHAR(64),
    ip VARCHAR(45),
    session_id VARCHAR(255),
    details JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

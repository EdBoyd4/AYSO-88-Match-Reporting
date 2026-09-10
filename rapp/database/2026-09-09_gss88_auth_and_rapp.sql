-- ============================================================================
-- gss88 auth + RAPP schema
-- Target database: xnbglkce_gss88cardreports  (same DB as the match-report app)
-- Date: 2026-09-09
--
-- Idempotent: CREATE TABLE IF NOT EXISTS everywhere, role seed is an upsert.
-- Safe to run on the dev box now and on A2 production later.
--
--   mysql -u <user> -p xnbglkce_gss88cardreports < 2026-09-09_gss88_auth_and_rapp.sql
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Boyd's Little Login Library core tables
-- (email address is stored in users.user_name; these users sign in by OTP,
--  but the library schema requires a password column, so a random unusable
--  hash is written there at user-creation time.)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_name  VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role    VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45) NOT NULL,
    username     VARCHAR(255) NOT NULL,
    attempt_time INT NOT NULL,
    INDEX idx_login_attempts_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_audit_logs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    event_type        VARCHAR(50) NOT NULL,
    ip_address        VARCHAR(45) NOT NULL,
    username_involved VARCHAR(255) NULL,
    details           TEXT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_ip (ip_address),
    INDEX idx_audit_user (username_involved),
    INDEX idx_audit_event (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Login library additive features: OTP + runtime session length
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_settings (
    setting_key   VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otp_challenges (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    identifier  VARCHAR(255) NOT NULL,
    code_hash   VARCHAR(255) NOT NULL,
    expires_at  INT NOT NULL,
    attempts    INT NOT NULL DEFAULT 0,
    consumed_at INT NULL,
    created_at  INT NOT NULL,
    INDEX idx_otp_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- gss88 user profile (1:1 extension of users)
--   division_id -> divisions_with_coordinators._id, only meaningful for DCs
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gss88_user_profiles (
    user_id     INT NOT NULL PRIMARY KEY,
    full_name   VARCHAR(120) NOT NULL,
    division_id INT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_gss88_profile_division (division_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- RAPP: Referee Abuse Prevention Program reports
--   scheduled_match_id  -> scheduled_matches._id   (no FK: keep cross-engine safe)
--   submitted_by_user_id -> users.user_id
--   body_text + media are wiped by the retention job after retention_hours
--   unless retain = 1; the row itself (metadata) is kept for the season archive.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rapp_reports (
    _id                  INT AUTO_INCREMENT PRIMARY KEY,
    scheduled_match_id   INT NOT NULL,
    submitted_by_user_id INT NOT NULL,
    body_text            MEDIUMTEXT NULL,
    has_audio            TINYINT(1) NOT NULL DEFAULT 0,
    status               ENUM('new','acknowledged','retained','closed') NOT NULL DEFAULT 'new',
    retain               TINYINT(1) NOT NULL DEFAULT 0,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at      DATETIME NULL,
    content_purged_at    DATETIME NULL,
    INDEX idx_rapp_match (scheduled_match_id),
    INDEX idx_rapp_submitter (submitted_by_user_id),
    INDEX idx_rapp_status (status),
    INDEX idx_rapp_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rapp_media (
    _id            INT AUTO_INCREMENT PRIMARY KEY,
    rapp_report_id INT NOT NULL,
    filename       VARCHAR(255) NOT NULL,
    mime           VARCHAR(100) NOT NULL,
    bytes          INT UNSIGNED NOT NULL,
    original_name  VARCHAR(255) NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    purged_at      DATETIME NULL,
    INDEX idx_rapp_media_report (rapp_report_id),
    FOREIGN KEY (rapp_report_id) REFERENCES rapp_reports(_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rapp_alternates (
    _id                  INT AUTO_INCREMENT PRIMARY KEY,
    alternate_user_id    INT NOT NULL,
    activated_by_user_id  INT NOT NULL,
    starts_at            DATETIME NOT NULL,
    ends_at              DATETIME NULL,
    is_active            TINYINT(1) NOT NULL DEFAULT 1,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rapp_alt_active (is_active, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rapp_media_access_log (
    _id            INT AUTO_INCREMENT PRIMARY KEY,
    rapp_report_id INT NOT NULL,
    user_id        INT NOT NULL,
    accessed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rapp_access_report (rapp_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Role seed (idempotent). 'system_manager' kept for login-library parity.
-- ---------------------------------------------------------------------------
INSERT INTO roles (role) VALUES
    ('system_manager'),
    ('rra'),
    ('senior_board'),
    ('authorized_board'),
    ('dc'),
    ('ref')
ON DUPLICATE KEY UPDATE role = role;

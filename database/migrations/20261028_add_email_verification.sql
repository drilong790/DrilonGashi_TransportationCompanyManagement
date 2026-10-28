USE transport_management;

ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL DEFAULT NULL;

UPDATE users
SET email_verified_at = CURRENT_TIMESTAMP
WHERE is_active = 1;

CREATE TABLE email_verifications (
    user_id INT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_email_verifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

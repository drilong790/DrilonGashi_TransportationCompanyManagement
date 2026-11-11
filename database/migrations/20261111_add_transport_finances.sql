USE transport_management;

ALTER TABLE transports
    ADD COLUMN revenue DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER status;

CREATE TABLE transport_expenses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    transport_id INT UNSIGNED NOT NULL,
    category VARCHAR(30) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    expense_date DATE NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_transport_expenses_transport_id (transport_id),
    KEY idx_transport_expenses_expense_date (expense_date),
    CONSTRAINT fk_transport_expenses_transport
        FOREIGN KEY (transport_id) REFERENCES transports(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_transport_expenses_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

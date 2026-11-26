USE transport_management;

ALTER TABLE users
    MODIFY COLUMN role ENUM('SUPERADMIN', 'ADMIN', 'DISPATCHER', 'DRIVER') NOT NULL,
    ADD COLUMN driver_id INT UNSIGNED NULL AFTER role,
    ADD UNIQUE KEY uq_users_driver_id (driver_id),
    ADD CONSTRAINT fk_users_driver
        FOREIGN KEY (driver_id) REFERENCES drivers(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

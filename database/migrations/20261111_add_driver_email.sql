USE transport_management;

ALTER TABLE drivers
    ADD COLUMN email VARCHAR(150) NULL AFTER phone,
    ADD UNIQUE KEY uq_drivers_email (email);

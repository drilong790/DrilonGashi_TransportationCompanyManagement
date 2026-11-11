USE transport_management;

ALTER TABLE transports
    ADD COLUMN scheduled_end DATETIME NULL AFTER scheduled_date;

UPDATE transports
SET scheduled_end = DATE_ADD(scheduled_date, INTERVAL 2 HOUR)
WHERE scheduled_end IS NULL;

ALTER TABLE transports
    MODIFY COLUMN scheduled_end DATETIME NOT NULL;

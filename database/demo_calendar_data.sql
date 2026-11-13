USE transport_management;

START TRANSACTION;

INSERT INTO transports (
    customer_id, driver_id, vehicle_id, created_by, pickup_address, destination_address,
    cargo_description, scheduled_date, scheduled_end, status, revenue
)
SELECT
    c.id, d.id, v.id, creator.id, demo.pickup_address, demo.destination_address,
    demo.cargo_description, demo.scheduled_date, demo.scheduled_end, demo.status, demo.revenue
FROM (
    SELECT sample.*,
        TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL sample.day_offset DAY), sample.start_time) AS scheduled_date,
        DATE_ADD(
            TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL sample.day_offset DAY), sample.start_time),
            INTERVAL sample.duration_hours HOUR
        ) AS scheduled_end
    FROM (
        SELECT 'info@albanialogistics.al' AS customer_email, 'AL-2024-001' AS driver_license,
            'AB-101-TR' AS vehicle_plate, 'Magazina qendrore, Tiranë' AS pickup_address,
            'Porti i Durrësit, Durrës' AS destination_address, 'DEMO-CALENDAR-01 | Pajisje magazinimi' AS cargo_description,
            1 AS day_offset, '08:00:00' AS start_time, 3 AS duration_hours, 'PLANNED' AS status, 1200.00 AS revenue
        UNION ALL SELECT 'ops@balkanfreight.al', 'AL-2024-002', 'CD-202-TR',
            'Zona industriale, Elbasan', 'Terminali i Prishtinës', 'DEMO-CALENDAR-02 | Mallra të paketuara',
            1, '12:30:00', 2, 'PLANNED', 950.00
        UNION ALL SELECT 'hello@cityexpress.al', 'AL-2025-004', 'AA-505-TR',
            'Depoja e Durrësit, Durrës', 'Qendra e shpërndarjes, Tiranë', 'DEMO-CALENDAR-03 | Produkte frigoriferike',
            2, '09:00:00', 3, 'PLANNED', 1750.00
        UNION ALL SELECT 'logistics@dardaniatrade.example', 'KS-2025-005', 'KS-909-01',
            'Magazina, Prishtinë', 'Terminali doganor, Morinë', 'DEMO-CALENDAR-04 | Pajisje elektronike',
            4, '14:00:00', 3, 'PLANNED', 2300.00
        UNION ALL SELECT 'transport@adriatikmarket.example', 'AL-2025-008', 'CC-707-TR',
            'Porti i Durrësit, Durrës', 'Qendra e furnizimit, Shkodër', 'DEMO-CALENDAR-05 | Furnizime javore',
            6, '06:30:00', 4, 'PLANNED', 2100.00
    ) AS sample
) AS demo
JOIN customers c ON c.email = demo.customer_email
JOIN drivers d ON d.license_number = demo.driver_license AND d.is_active = 1
JOIN vehicles v ON v.plate_number = demo.vehicle_plate AND v.is_active = 1
CROSS JOIN (
    SELECT id
    FROM users
    WHERE is_active = 1 AND email_verified_at IS NOT NULL AND role IN ('SUPERADMIN', 'ADMIN')
    ORDER BY role = 'ADMIN' DESC, id
    LIMIT 1
) AS creator
WHERE NOT EXISTS (
    SELECT 1 FROM transports existing WHERE existing.cargo_description = demo.cargo_description
)
AND NOT EXISTS (
    SELECT 1 FROM transports existing
    WHERE existing.status <> 'CANCELLED'
        AND existing.driver_id = d.id
        AND existing.scheduled_date < demo.scheduled_end
        AND existing.scheduled_end > demo.scheduled_date
)
AND NOT EXISTS (
    SELECT 1 FROM transports existing
    WHERE existing.status <> 'CANCELLED'
        AND existing.vehicle_id = v.id
        AND existing.scheduled_date < demo.scheduled_end
        AND existing.scheduled_end > demo.scheduled_date
);

INSERT INTO transport_expenses (transport_id, category, description, amount, expense_date, created_by)
SELECT t.id, demo.category, demo.description, demo.amount,
    DATE_ADD(CURRENT_DATE, INTERVAL demo.day_offset DAY), creator.id
FROM (
    SELECT 'DEMO-CALENDAR-01 | Pajisje magazinimi' AS cargo_description, 'FUEL' AS category,
        'DEMO | Karburant' AS description, 180.50 AS amount, 1 AS day_offset
    UNION ALL SELECT 'DEMO-CALENDAR-01 | Pajisje magazinimi', 'TOLL', 'DEMO | Taksa rruge', 45.00, 1
    UNION ALL SELECT 'DEMO-CALENDAR-03 | Produkte frigoriferike', 'FUEL', 'DEMO | Karburant frigoriferik', 220.00, 2
    UNION ALL SELECT 'DEMO-CALENDAR-05 | Furnizime javore', 'MAINTENANCE', 'DEMO | Kontroll teknik', 95.00, 6
) AS demo
JOIN transports t ON t.cargo_description = demo.cargo_description
CROSS JOIN (
    SELECT id
    FROM users
    WHERE is_active = 1 AND email_verified_at IS NOT NULL AND role IN ('SUPERADMIN', 'ADMIN')
    ORDER BY role = 'ADMIN' DESC, id
    LIMIT 1
) AS creator
WHERE NOT EXISTS (
    SELECT 1 FROM transport_expenses existing
    WHERE existing.transport_id = t.id AND existing.description = demo.description
);

COMMIT;

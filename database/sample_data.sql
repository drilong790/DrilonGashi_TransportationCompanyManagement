USE transport_management;

START TRANSACTION;

INSERT IGNORE INTO users (full_name, email, password_hash, role, is_active, email_verified_at, created_at) VALUES
('Admin User', 'admin@transport.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'ADMIN', 1, NOW(), NOW()),
('Dispatcher User', 'dispatcher@transport.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'DISPATCHER', 1, NOW(), NOW());

INSERT IGNORE INTO customers (company_name, contact_person, phone, email, address, created_at) VALUES
('Albania Logistics', 'Ardit Hoxha', '+355 68 111 1111', 'info@albanialogistics.al', 'Rruga e Durrësit 12, Tiranë', NOW()),
('Balkan Freight', 'Marina Leka', '+355 68 222 2222', 'ops@balkanfreight.al', 'Bulevardi Zogu i Parë 45, Tiranë', NOW()),
('City Express', 'Elton Berisha', '+355 68 333 3333', 'hello@cityexpress.al', 'Rruga 28 Nëntori 7, Shkodër', NOW()),
('Dardania Trade', 'Era Krasniqi', '+383 44 111 111', 'logistics@dardaniatrade.example', 'Rruga B, Prishtinë', NOW()),
('Adriatik Market', 'Ilir Dervishi', '+355 69 444 4444', 'transport@adriatikmarket.example', 'Lagjja 13, Durrës', NOW()),
('Green Valley Foods', 'Mira Selimi', '+355 68 555 5555', 'supply@greenvalley.example', 'Rruga e Kavajës 80, Tiranë', NOW()),
('North Star Construction', 'Blerim Shala', '+383 49 222 222', 'dispatch@northstar.example', 'Rruga UÇK, Prizren', NOW()),
('Blue Coast Supplies', 'Drita Gjoni', '+355 69 666 6666', 'office@bluecoast.example', 'Rruga Taulantia 18, Durrës', NOW()),
('Central Pharma Distribution', 'Arben Meta', '+355 68 777 7777', 'delivery@centralpharma.example', 'Bulevardi Bajram Curri 22, Tiranë', NOW()),
('Mountain View Furniture', 'Lira Berisha', '+383 44 333 333', 'orders@mountainview.example', 'Rruga e Pejës 14, Gjakovë', NOW());

INSERT IGNORE INTO drivers (full_name, license_number, phone, is_active, created_at) VALUES
('Besnik Kodra', 'AL-2024-001', '+355 69 101 0101', 1, NOW()),
('Genti Xhafa', 'AL-2024-002', '+355 69 202 0202', 1, NOW()),
('Luan Gashi', 'AL-2024-003', '+355 69 303 0303', 0, NOW()),
('Arjan Dema', 'AL-2025-004', '+355 69 404 0404', 1, NOW()),
('Flora Hoxha', 'KS-2025-005', '+383 44 505 505', 1, NOW()),
('Driton Bytyqi', 'KS-2025-006', '+383 49 606 606', 1, NOW()),
('Valon Morina', 'KS-2025-007', '+383 45 707 707', 0, NOW()),
('Elira Shala', 'AL-2025-008', '+355 68 808 0808', 1, NOW());

INSERT IGNORE INTO vehicles (plate_number, vehicle_type, capacity, is_active, created_at) VALUES
('AB-101-TR', 'Kamion', 4, 1, NOW()),
('CD-202-TR', 'Furgon', 2, 1, NOW()),
('EF-303-TR', 'Kamion', 5, 0, NOW()),
('GH-404-TR', 'Furgon', 3, 1, NOW()),
('AA-505-TR', 'Kamion frigoriferik', 8, 1, NOW()),
('BB-606-TR', 'Kamion i rëndë', 12, 1, NOW()),
('CC-707-TR', 'Furgon', 2, 1, NOW()),
('DD-808-TR', 'Kamion me rimorkio', 18, 0, NOW()),
('KS-909-01', 'Kamion', 10, 1, NOW());

INSERT INTO transports (
    customer_id, driver_id, vehicle_id, created_by,
    pickup_address, destination_address, cargo_description,
    scheduled_date, status, created_at, updated_at
)
SELECT
    c.id, d.id, v.id, creator.id,
    sample.pickup_address, sample.destination_address, sample.cargo_description,
    sample.scheduled_date, sample.status, sample.scheduled_date, NULL
FROM (
    SELECT 'info@albanialogistics.al' AS customer_email, 'AL-2024-001' AS driver_license, 'AB-101-TR' AS vehicle_plate,
        'Rruga e Durrësit 12, Tiranë' AS pickup_address, 'Porti i Durrësit, Durrës' AS destination_address,
        'Pajisje dhe mallra për konsum' AS cargo_description, '2026-10-25 09:00:00' AS scheduled_date, 'PLANNED' AS status
    UNION ALL SELECT 'ops@balkanfreight.al', 'AL-2024-002', 'CD-202-TR',
        'Bulevardi Zogu i Parë 45, Tiranë', 'Zona industriale, Elbasan',
        'Materiale paketimi dhe mobilie', '2026-10-26 08:30:00', 'IN_PROGRESS'
    UNION ALL SELECT 'hello@cityexpress.al', NULL, NULL,
        'Rruga 28 Nëntori 7, Shkodër', 'Qendra e Distribucionit, Lezhë',
        'Pako të përziera për shpërndarje', '2026-10-27 11:15:00', 'PLANNED'
    UNION ALL SELECT 'logistics@dardaniatrade.example', 'KS-2025-005', 'KS-909-01',
        'Rruga B, Prishtinë', 'Terminali doganor, Morinë',
        'Pajisje elektronike të paketuara', '2026-10-28 07:45:00', 'IN_PROGRESS'
    UNION ALL SELECT 'transport@adriatikmarket.example', 'AL-2025-004', 'AA-505-TR',
        'Lagjja 13, Durrës', 'Qendra e magazinimit, Tiranë',
        'Produkte ushqimore të freskëta', '2026-10-29 06:30:00', 'PLANNED'
    UNION ALL SELECT 'supply@greenvalley.example', 'AL-2024-001', 'AA-505-TR',
        'Rruga e Kavajës 80, Tiranë', 'Depoja rajonale, Shkodër',
        'Fruta dhe perime në kushte frigoriferike', '2026-10-20 05:45:00', 'COMPLETED'
    UNION ALL SELECT 'dispatch@northstar.example', 'KS-2025-006', 'BB-606-TR',
        'Rruga UÇK, Prizren', 'Kantieri i ndërtimit, Gjakovë',
        'Çimento dhe materiale ndërtimi', '2026-10-18 08:00:00', 'COMPLETED'
    UNION ALL SELECT 'office@bluecoast.example', 'AL-2024-002', 'GH-404-TR',
        'Rruga Taulantia 18, Durrës', 'Zona industriale, Vlorë',
        'Pajisje për mirëmbajtjen e hoteleve', '2026-10-17 10:15:00', 'CANCELLED'
    UNION ALL SELECT 'delivery@centralpharma.example', 'AL-2025-008', 'CC-707-TR',
        'Bulevardi Bajram Curri 22, Tiranë', 'Qendra shëndetësore, Kukës',
        'Produkte farmaceutike të paketuara', '2026-10-30 07:00:00', 'PLANNED'
    UNION ALL SELECT 'orders@mountainview.example', 'KS-2025-005', 'CD-202-TR',
        'Rruga e Pejës 14, Gjakovë', 'Showroom-i, Prishtinë',
        'Mobilie dhe pajisje shtëpiake', '2026-10-22 09:30:00', 'COMPLETED'
    UNION ALL SELECT 'info@albanialogistics.al', 'AL-2025-004', 'BB-606-TR',
        'Porti i Durrësit, Durrës', 'Magazina qendrore, Tiranë',
        'Paleta me mallra importi', '2026-10-31 12:00:00', 'PLANNED'
    UNION ALL SELECT 'ops@balkanfreight.al', 'KS-2025-006', 'KS-909-01',
        'Zona industriale, Elbasan', 'Terminali i Prishtinës',
        'Pajisje industriale dhe pjesë këmbimi', '2026-10-21 06:00:00', 'COMPLETED'
    UNION ALL SELECT 'hello@cityexpress.al', 'AL-2024-001', 'GH-404-TR',
        'Qendra e Distribucionit, Lezhë', 'Rruga 28 Nëntori 7, Shkodër',
        'Pako për kthim dhe dokumente', '2026-10-23 14:00:00', 'COMPLETED'
    UNION ALL SELECT 'transport@adriatikmarket.example', NULL, NULL,
        'Porti i Durrësit, Durrës', 'Lagjja 13, Durrës',
        'Ngarkesë e planifikuar në pritje të caktimit', '2026-11-02 08:00:00', 'PLANNED'
    UNION ALL SELECT 'supply@greenvalley.example', 'AL-2025-008', 'AA-505-TR',
        'Depoja rajonale, Shkodër', 'Rruga e Kavajës 80, Tiranë',
        'Kthim i kontejnerëve frigoriferikë', '2026-10-24 16:30:00', 'CANCELLED'
) AS sample
JOIN customers c ON c.email = sample.customer_email
LEFT JOIN drivers d ON d.license_number = sample.driver_license
LEFT JOIN vehicles v ON v.plate_number = sample.vehicle_plate
JOIN (
    SELECT id
    FROM users
    WHERE is_active = 1
    ORDER BY (role IN ('SUPERADMIN', 'ADMIN')) DESC, id
    LIMIT 1
) AS creator ON 1 = 1
WHERE NOT EXISTS (
    SELECT 1
    FROM transports existing
    WHERE existing.customer_id = c.id
        AND existing.pickup_address = sample.pickup_address
        AND existing.scheduled_date = sample.scheduled_date
);

COMMIT;

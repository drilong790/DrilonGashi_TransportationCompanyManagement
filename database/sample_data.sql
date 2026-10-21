USE transport_management;

INSERT INTO users (full_name, email, password_hash, role, is_active, created_at) VALUES
('Admin User', 'admin@transport.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'ADMIN', 1, NOW()),
('Dispatcher User', 'dispatcher@transport.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'DISPATCHER', 1, NOW());

INSERT INTO customers (company_name, contact_person, phone, email, address, created_at) VALUES
('Albania Logistics', 'Ardit Hoxha', '+355 68 111 1111', 'info@albanialogistics.al', 'Rruga e Durrësit 12, Tirana', NOW()),
('Balkan Freight', 'Marina Leka', '+355 68 222 2222', 'ops@balkanfreight.al', 'Bulevardi Zogu i Parë 45, Tirana', NOW()),
('City Express', 'Elton Berisha', '+355 68 333 3333', 'hello@cityexpress.al', 'Rruga 28 Nëntori 7, Shkodër', NOW());

INSERT INTO drivers (full_name, license_number, phone, is_active, created_at) VALUES
('Besnik Kodra', 'AL-2024-001', '+355 69 101 0101', 1, NOW()),
('Genti Xhafa', 'AL-2024-002', '+355 69 202 0202', 1, NOW()),
('Luan Gashi', 'AL-2024-003', '+355 69 303 0303', 0, NOW());

INSERT INTO vehicles (plate_number, vehicle_type, capacity, is_active, created_at) VALUES
('AB-101-TR', 'Truck', 4, 1, NOW()),
('CD-202-TR', 'Van', 2, 1, NOW()),
('EF-303-TR', 'Truck', 5, 0, NOW()),
('GH-404-TR', 'Van', 3, 1, NOW());

INSERT INTO transports (customer_id, driver_id, vehicle_id, created_by, pickup_address, destination_address, cargo_description, scheduled_date, status, created_at, updated_at) VALUES
(1, 1, 1, 1, 'Rruga e Durrësit 12, Tirana', 'Porti i Durrësit, Durrës', 'Paketat e mallrave për konsum dhe qira', '2026-10-25 09:00:00', 'PLANNED', NOW(), NOW()),
(2, 2, 2, 2, 'Bulevardi Zogu i Parë 45, Tirana', 'Fusha e Produktit, Elbasan', 'Furniture and packaging materials', '2026-10-26 08:30:00', 'IN_PROGRESS', NOW(), NOW()),
(3, NULL, NULL, 1, 'Rruga 28 Nëntori 7, Shkodër', 'Qendra e Distribucionit, Lezhë', 'Malli i rregulluar për shpërndarje', '2026-10-27 11:15:00', 'PLANNED', NOW(), NOW());

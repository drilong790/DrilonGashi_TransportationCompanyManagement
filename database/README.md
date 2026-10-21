# Database për Sistemin e Menaxhimit të Kompanive të Transportit

Ky folder përmban skedarët e nevojshëm për krijimin e databazës `transport_management` me MySQL dhe lidhjen nga PHP me PDO.

## 1. Skedarët e krijuara

- `schema.sql` – krijon bazën e të dhënave dhe tabelat e nevojshme
- `sample_data.sql` – shton të dhëna fals (fiktive) për klientë, shoferë, automjete dhe transportet

## 2. Importimi në phpMyAdmin

1. Hap XAMPP Control Panel dhe nis `Apache` dhe `MySQL`.
2. Shko te `http://localhost/phpmyadmin`.
3. Krijo një databazë të re me emrin `transport_management`.
4. Kliko te skeda `Import`.
5. Zgjidh skedarin `database/schema.sql` dhe importo.
6. Pastaj importo edhe skedarin `database/sample_data.sql`.

> Nëse e keni krijuar bazën e të dhënave me `schema.sql`, importimi i `sample_data.sql` do të shtojë të dhënat demo pa fshirë strukturën e tabelave.

## 3. Importimi në XAMPP me terminal

Nëse dëshiron ta importosh nga linja e komandës:

```bash
mysql -u root -p
CREATE DATABASE IF NOT EXISTS transport_management;
USE transport_management;
SOURCE C:/xampp/mysql/data/transport_management.sql;
```

Ose edhe më thjeshtë:

```bash
mysql -u root -p transport_management < C:/xampp/htdocs/projekti/database/schema.sql
mysql -u root -p transport_management < C:/xampp/htdocs/projekti/database/sample_data.sql
```

## 4. Raporti i tabelave

- `users` është tabela e përdoruesve të sistemit (admin dhe dispatcher).
- `customers` përmban klientët e transportit.
- `drivers` përmban shoferët.
- `vehicles` përmban automjetet e disponueshme.
- `transports` lidhet me `customers`, `drivers`, `vehicles` dhe `users`:
  - `customer_id` -> `customers.id`
  - `driver_id` -> `drivers.id`
  - `vehicle_id` -> `vehicles.id`
  - `created_by` -> `users.id`

## 5. Kontrollat e nevojshme në PHP para se të ruhet një transport

Para se të ruhet një transport, PHP duhet të kontrollojë:

- `customer_id` ekziston dhe është i vlefshëm.
- `created_by` ekziston dhe është përdorues i aktiv.
- `driver_id`, nëse është dhënë, ekziston dhe është `is_active = 1`.
- `vehicle_id`, nëse është dhënë, ekziston dhe është `is_active = 1`.
- Adresat e nisjes dhe destinacionit nuk janë bosh.
- `scheduled_date` është e formatuar saktë dhe jo në të kaluarën (nëse kjo është kërkuar nga logjika e biznesit).
- `status` duhet të jetë një nga: `PLANNED`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`.
- Nqs `driver_id` ose `vehicle_id` janë null, sistemi duhet ta lejojë transportin e planifikuar pa qënë ende caktuar.

Për shembull, kontrolli për shoferin dhe automjetin aktiv:

```php
$stmt = $pdo->prepare('SELECT id, is_active FROM drivers WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $driverId]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if ($driver && (int)$driver['is_active'] !== 1) {
    throw new Exception('Shoferi nuk është aktiv.');
}
```

```php
$stmt = $pdo->prepare('SELECT id, is_active FROM vehicles WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $vehicleId]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vehicle && (int)$vehicle['is_active'] !== 1) {
    throw new Exception('Automjeti nuk është aktiv.');
}
```

## 6. Fjalëkalimi i përdoruesve

Mos ruaj fjalëkalime në tekst të thjeshtë. Në PHP, llogaria fillestare duhet të krijohet me `password_hash()`:

```php
$passwordHash = password_hash('Admin@123', PASSWORD_DEFAULT);
```

Pastaj ai hash ruhet në kolumnën `password_hash` e tabelës `users`.

## 7. Viktima e tabelave dhe constraint-ave

- `users.email` është unik
- `drivers.license_number` është unik
- `vehicles.plate_number` është unik
- `transports.customer_id` dhe `created_by` janë të detyrueshme
- `transports.driver_id` dhe `vehicle_id` lejojnë `NULL` derisa të caktohet transporti
- Të gjitha tabelat përdorin `InnoDB` dhe `utf8mb4`
- Nuk përmban komanda shkatërruese si `DROP`, `TRUNCATE` ose fshirje të të dhënave ekzistuese

## 8. Si ta përdorësh bazën e të dhënave

Pas importimit, mund të përdorësh PDO në PHP si:

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=transport_management;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
```

Ky model është i përshtatshëm për XAMPP, ku përdoruesi default i MySQL është zakonisht `root` dhe passwordi është bosh.

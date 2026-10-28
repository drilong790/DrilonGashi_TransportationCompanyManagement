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

Para se të ruhet një transport, PHP kontrollon:

- `customer_id` ekziston dhe është i vlefshëm.
- `created_by` vendoset automatikisht nga sesioni i përdoruesit aktiv.
- `driver_id`, nëse është dhënë, ekziston dhe është `is_active = 1`.
- `vehicle_id`, nëse është dhënë, ekziston dhe është `is_active = 1`.
- Adresat e nisjes dhe destinacionit nuk janë bosh.
- `scheduled_date` është e formatuar saktë dhe jo në të kaluarën (nëse kjo është kërkuar nga logjika e biznesit).
- `status` duhet të jetë një nga: `PLANNED`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`.
- Nëse `driver_id` ose `vehicle_id` janë null, transporti mund të ruhet pa caktim.

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

Mos ruaj fjalëkalime në tekst të thjeshtë. Pas importimit të `schema.sql`, përdor skriptin interaktiv `bin/create-user.php` për të krijuar administratorin e parë. Ai ruan vetëm hash-in e krijuar me `password_hash()` dhe kërkon rol `ADMIN` ose `DISPATCHER`. Skripti kërkon terminal interaktiv dhe fsheh fjalëkalimin gjatë futjes. Vizitorët mund të regjistrohen te `?page=register`, por regjistrimi publik krijon vetëm llogari `DISPATCHER`. Pasi të hyjë, administratori mund të krijojë administratorë të tjerë nga menyja **Shto administrator**.

## 7. Viktima e tabelave dhe constraint-ave

- `users.email` është unik
- `drivers.license_number` është unik
- `vehicles.plate_number` është unik
- `transports.customer_id` dhe `created_by` janë të detyrueshme
- `transports.driver_id` dhe `vehicle_id` lejojnë `NULL` derisa të caktohet transporti
- Të gjitha tabelat përdorin `InnoDB` dhe `utf8mb4`
- Nuk përmban komanda shkatërruese si `DROP`, `TRUNCATE` ose fshirje të të dhënave ekzistuese

## 8. Si ta përdorësh bazën e të dhënave

Pas importimit, aplikacioni përdor lidhjen e përbashkët në `src/Database/Connection.php`. Konfigurimi merret nga variablat e ambientit `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` dhe `DB_PASSWORD`. Për XAMPP parazgjedhjet janë `127.0.0.1`, `3306`, `transport_management`, `root` dhe fjalëkalim bosh. Mos vendos kredenciale reale në skedarë të versionuar.

Përdorimi i lidhjes në PHP:

```php
use App\Database\Connection;

$pdo = Connection::create();
```

Lidhja përdoret nga paneli, hyrja dhe modulet CRUD për klientë, shoferë, automjete dhe transporte. Për XAMPP me kredenciale të parazgjedhura, pas importimit të skemës mund të nisesh aplikacionin me `php -S localhost:8000 -t public`. Kërkohet PHP me shtesat `pdo_mysql` dhe `mbstring` aktive.

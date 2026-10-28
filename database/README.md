# Databaza e sistemit të transportit

Ky folder përmban skedarët për krijimin dhe përditësimin e databazës MySQL `transport_management`.

## Instalimi i ri

1. Nise Apache dhe MySQL nga XAMPP.
2. Krijo/importo databazën përmes phpMyAdmin ose terminalit:

   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p transport_management < database/sample_data.sql
   ```

3. Instalo varësitë PHP nga dosja kryesore e projektit:

   ```bash
   composer install
   ```

`schema.sql` krijon tabelat e përdoruesve, klientëve, shoferëve, automjeteve dhe transporteve. Llogaritë demo në `sample_data.sql` janë të shënuara si të verifikuara.

## Përditësimi i një databaze ekzistuese

Mos e riimporto `schema.sql` në një bazë që përmban të dhëna. Ekzekuto vetëm një herë migrimin `migrations/20261028_add_email_verification.sql` për të shtuar kolonën e verifikimit dhe tabelën e kodeve. Migrimi i shënon si të verifikuara llogaritë aktive ekzistuese.

Përmes terminalit, kur databaza ekziston:

```bash
mysql -u root -p transport_management < database/migrations/20261028_add_email_verification.sql
```

## Konfigurimi i aplikacionit

Aplikacioni përdor PDO nga `src/Database/Connection.php`. Databaza konfigurohet me variablat `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` dhe `DB_PASSWORD`. Për XAMPP, vlerat e parazgjedhura janë `127.0.0.1`, `3306`, `transport_management`, `root` dhe fjalëkalim bosh. Mos vendos kredenciale reale në skedarë të versionuar.

Për të krijuar administratorin fillestar, përdor `bin/create-user.php` në terminal interaktiv. Ai ruan hash-in e fjalëkalimit dhe e shënon llogarinë si të verifikuar. Përdoruesit e rinj të krijuar përmes regjistrimit ose nga administratori duhet të verifikojnë email-in para hyrjes.

## Konfigurimi i email-it SMTP

PHPMailer dërgon kodin e verifikimit përmes SMTP. Vendos variablat e mëposhtme në konfigurimin lokal të Apache/PHP (p.sh. me direktivat `SetEnv` në konfigurimin lokal të Apache), pastaj rinise Apache. Mos i shto vlerat reale në Git.

| Variabla | Përshkrimi |
|---|---|
| `SMTP_HOST` | Hostname i serverit SMTP |
| `SMTP_PORT` | Porta SMTP: zakonisht `587` për TLS ose `465` për SSL |
| `SMTP_USERNAME` | Emri i përdoruesit SMTP |
| `SMTP_PASSWORD` | Fjalëkalimi ose app-password SMTP |
| `SMTP_FROM_EMAIL` | Adresa e vlefshme dërguese |
| `SMTP_FROM_NAME` | Emri i dërguesit; opsional, parazgjedhja `Transporti` |
| `SMTP_ENCRYPTION` | `tls` (parazgjedhje) ose `ssl` |
| `APP_URL` | Adresa bazë e aplikacionit për lidhjen në email; opsionale |

Kodi i verifikimit ka 6 shifra, ruhet vetëm si hash, skadon pas 10 minutash, lejon maksimumi 5 tentativa dhe mund të ridërgohet jo më shpesh se një herë në 60 sekonda.

Për XAMPP, kur PDO MySQL dhe `mbstring` janë aktive dhe SMTP është konfiguruar, aplikacioni mund të niset nga dosja kryesore me:

```bash
php -S localhost:8000 -t public
```

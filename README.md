# Transportation Company Management

Aplikacion PHP për menaxhimin e një kompanie transporti, me panel përmbledhës dhe module CRUD për klientët, shoferët, automjetet dhe transportet.

## Databaza

Paneli dhe modulet përdorin PDO për t'u lidhur me MySQL. Importo [database/schema.sql](database/schema.sql). Për të pasur përdorues që mund të zgjidhen si krijues i transportit, importo edhe [database/sample_data.sql](database/sample_data.sql) ose shto vetë një përdorues aktiv në tabelën `users`. Udhëzimet gjenden te [database/README.md](database/README.md).

Lidhja përdor këto variabla ambienti; parazgjedhjet janë të përshtatshme për një instalim lokal XAMPP me `root` dhe pa fjalëkalim:

| Variabla | Parazgjedhja |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `transport_management` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | bosh |

Për konfigurim tjetër, vendosi variablat në ambientin ku niset PHP/Apache. Mos ruaj fjalëkalime databaze në kod ose në Git. Nëse databaza nuk mund të arrihet, paneli shfaq njoftim dhe detajet teknike regjistrohen në log-un e PHP-së.

## Nisja lokalisht

Kërkohet PHP 8.0 ose më i ri me PDO MySQL aktiv. Nga direktoria kryesore e projektit, nis serverin e integruar të PHP-së:

```bash
php -S localhost:8000 -t public
```

Pastaj hape [http://localhost:8000](http://localhost:8000) në shfletues.

## Struktura e projektit

```text
public/
  index.php                 Hyrja e aplikacionit dhe zgjedhja e faqes
  assets/app.css            Stilet dhe paraqitja responsive
src/
  Database/
    Connection.php          Lidhja e përbashkët me MySQL përmes PDO
  Modules/
    CrudModule.php           Listim, validim dhe veprimet CRUD të moduleve
  Views/
    components/
      navigation.php        Navigimi i përbashkët i aplikacionit
    pages/
      dashboard.php         Paneli kryesor dhe lidhjet te modulet
      module.php             Pamja e përbashkët për listat dhe formularët CRUD
      not-found.php          Pamja për adresa të panjohura
    layout.php              Struktura e përbashkët e faqeve
database/
  schema.sql                Struktura e databazës MySQL
  sample_data.sql           Të dhëna demo për databazën
```

## Navigimi

Përdor menynë anësore ose kartat në panelin kryesor për të hapur:

- Panelin kryesor
- Transportet
- Klientët
- Shoferët
- Automjetet

Modulet **Klientët**, **Shoferët**, **Automjetet** dhe **Transportet** mbështesin listimin, shtimin, ndryshimin dhe fshirjen e regjistrimeve. Formularët validohen në server; kërkesat përdorin PDO, parametrizim dhe token CSRF. Fshirja e klientit parandalohet nëse ka transporte të lidhura. Transporti lidhet me klientin, përdoruesin që e regjistroi dhe shoferin/automjetin (opsionalë).

> **Shënim:** Hyrja/autentikimi i përdoruesve nuk është implementuar ende. Përdore lokalisht ose në mjedis të besuar; mos e ekspozo aplikacionin në internet para se të shtohet autentikimi dhe autorizimi.
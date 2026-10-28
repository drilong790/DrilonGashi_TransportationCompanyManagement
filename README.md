# Transportation Company Management

Aplikacion PHP për menaxhimin e një kompanie transporti, me panel përmbledhës dhe module CRUD për klientët, shoferët, automjetet dhe transportet.

## Databaza

Paneli dhe modulet përdorin PDO për t'u lidhur me MySQL. Importo [database/schema.sql](database/schema.sql). Mund të importosh edhe [database/sample_data.sql](database/sample_data.sql) për të dhëna demo; krijo llogaritë e hyrjes me skriptin `bin/create-user.php`.

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
bin/
  create-user.php           Krijon llogari me fjalëkalim të hash-uar
src/
  Auth/
    Authentication.php      Hyrja, sesioni dhe dalja nga sistemi
  Database/
    Connection.php          Lidhja e përbashkët me MySQL përmes PDO
  Modules/
    CrudModule.php           Listim, validim dhe veprimet CRUD të moduleve
  Views/
    components/
      navigation.php        Navigimi i përbashkët i aplikacionit
    pages/
      add-admin.php          Forma për krijimin e administratorëve (vetëm admin)
      dashboard.php         Paneli kryesor dhe lidhjet te modulet
      login.php              Hyrja e përdoruesit
      module.php             Pamja e përbashkët për listat dhe formularët CRUD
      not-found.php          Pamja për adresa të panjohura
      register.php           Regjistrimi publik si dispeçer
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

## Hyrja dhe përdoruesit

Hyrja përdor email dhe fjalëkalim me hash `password_hash()`. Përdoruesit joaktivë nuk mund të hyjnë. Regjistrimi publik krijon vetëm llogari `DISPATCHER`. Administratori i kyçur mund të krijojë administratorë të tjerë përmes opsionit **Shto administrator** në menynë anësore; roli caktohet nga serveri. Administratori mund të menaxhojë të gjitha modulet; dispeçeri mund t’i shohë klientët, shoferët dhe automjetet dhe mund të menaxhojë transportet. Krijuesi i një transporti caktohet automatikisht nga llogaria aktive.

Për krijimin e llogarisë së parë, pasi të jetë importuar skema, hape terminalin në direktorinë e projektit dhe nis:

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/create-user.php
```

Skripti kërkon emrin, email-in, rolin dhe fjalëkalimin (fjalëkalimi nuk shfaqet në terminal dhe duhet të ketë të paktën 12 karaktere). Krijo fillimisht një llogari `ADMIN`. Pastaj admini mund të përdorë opsionin e menusë për të krijuar adminë të tjerë. Përdor `http://localhost:8000/?page=register` për regjistrimin e dispeçerëve dhe `http://localhost:8000/?page=login` për hyrjen. Mos i ekspozo kredencialet në dokumentacion ose Git.

Sesioni përdor cookie `HttpOnly`, `SameSite=Lax`, rotacion të ID-së pas hyrjes dhe token CSRF për hyrje/dalje dhe veprimet e moduleve. Për përdorim publik duhen gjithashtu HTTPS, konfigurim i sigurt i serverit dhe kontrolle të rregullta të sigurisë.
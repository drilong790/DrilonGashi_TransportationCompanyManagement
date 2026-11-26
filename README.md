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
  Controllers/
    AccountController.php   Krijimi dhe menaxhimi i llogarive
    AuthController.php      Hyrja, regjistrimi dhe verifikimi i email-it
    CalendarController.php Kërkimi i transporteve sipas datës dhe caktimeve
  Database/
    Connection.php          Lidhja e përbashkët me MySQL përmes PDO
  Modules/
    CrudModule.php           Listim, validim dhe veprimet CRUD të moduleve
  Views/
    components/
      navigation.php        Navigimi i përbashkët i aplikacionit
    pages/
      add-admin.php          Forma për krijimin e administratorëve (superadmin/admin)
      calendar.php           Pamja ditore dhe javore e transporteve
      dashboard.php         Paneli kryesor dhe lidhjet te modulet
      login.php              Hyrja e përdoruesit
      module.php             Pamja e përbashkët për listat dhe formularët CRUD
      not-found.php          Pamja për adresa të panjohura
      register.php           Regjistrimi publik si dispeçer
      users.php              Menaxhimi i llogarive sipas rolit
      verify-email.php       Verifikimi i email-it
    layout.php              Struktura e përbashkët e faqeve
database/
  schema.sql                Struktura e databazës MySQL
  sample_data.sql           Të dhëna demo për databazën
```

## Navigimi

Përdor menynë anësore ose kartat në panelin kryesor për të hapur:

- Panelin kryesor
- Kalendarin ditor/javor të transporteve
- Transportet
- Klientët
- Shoferët
- Automjetet

Modulet **Klientët**, **Shoferët**, **Automjetet** dhe **Transportet** mbështesin listimin, shtimin, ndryshimin dhe fshirjen e regjistrimeve. Formularët validohen në server; kërkesat përdorin PDO, parametrizim dhe token CSRF. Fshirja e klientit parandalohet nëse ka transporte të lidhura. Transporti lidhet me klientin, përdoruesin që e regjistroi dhe shoferin/automjetin (opsionalë). Çdo transport ka orë fillimi dhe përfundimi; i njëjti shofer ose automjet nuk mund të caktohet në transporte me intervale që mbivendosen. Transportet e anuluara nuk bllokojnë caktimin; të përfunduara llogariten.

Faqja **Kalendari** paraqet transportet sipas ditës ose javës, me filtra sipas statusit, shoferit dhe automjetit. Zgjedhja e një transporti hap formularin ekzistues për ndryshim; në ekranet e vogla ditët paraqiten si listë vertikale.

Lista e transporteve mund të filtrohet sipas statusit, datës së planifikuar, klientit dhe shoferit; fusha e kërkimit përfshin klientin, adresat, ngarkesën, shoferin dhe targën. Rezultatet e filtruar mund të eksportohen si CSV. Paneli përmbledh transportet sipas statusit për një periudhë të zgjedhur dhe ofron eksport CSV të detajeve për të njëjtën periudhë.

Moduli **Shpenzimet dhe fitimi** është i qasshëm për SUPERADMIN dhe ADMIN. Për çdo transport mund të regjistrohen të hyrat dhe disa shpenzime në EUR (karburant, toll/taksa rruge, servis ose të tjera); fitimi llogaritet si të hyrat minus shpenzimet.

Njoftimet me email dërgohen te klienti dhe shoferi kur caktohet një transport/ndryshohet shoferi ose automjeti dhe kur ndryshon statusi. Kërkohet konfigurim SMTP; për transportet ekzistuese, vendos adresat e shoferëve te moduli Shoferët. Dështimi i email-it nuk e anulon ruajtjen e transportit; gabimi regjistrohet dhe njoftohet në faqe.

Për databazat ekzistuese, pasi të jetë instaluar versioni i ri, ekzekuto migrimin `database/migrations/20261111_add_transport_end_time.sql`. Transportet ekzistuese marrin kohëzgjatje fillestare prej dy orësh; kontrollo dhe rregullo oraret sipas nevojës.

## Hyrja dhe përdoruesit

Hyrja përdor email dhe fjalëkalim me hash `password_hash()`. Përdoruesit joaktivë nuk mund të hyjnë. Regjistrimi publik krijon vetëm llogari `DISPATCHER`. Ekziston një llogari `SUPERADMIN` (`drilon123@gmail.com`) me qasje të plotë dhe menaxhim të llogarive: mund të ndryshojë emailin/fjalëkalimin e llogarisë së vet dhe të administratorëve/dispeçerëve, si dhe të fshijë llogari që nuk kanë transporte të lidhura. Llogaria Superadmin nuk mund të fshihet. Ndryshimi i emailit kërkon verifikimin e adresës së re; llogaria çaktivizohet përkohësisht deri atëherë. Administratorët e zakonshëm kanë qasje në panel dhe të gjitha modulet, mund të krijojnë llogari `ADMIN` si dhe të ndryshojnë emailin/fjalëkalimin ose të fshijnë vetëm llogaritë `DISPATCHER` pa transporte. Ata nuk mund të menaxhojnë llogari administrative. Dispeçerët ruajnë lejet aktuale: shohin klientët, shoferët dhe automjetet dhe mund të menaxhojnë transportet. Krijuesi i një transporti caktohet automatikisht nga llogaria aktive.

Për krijimin e llogarisë së parë, pasi të jetë importuar skema, hape terminalin në direktorinë e projektit dhe nis:

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/create-user.php
```

Skripti kërkon emrin, email-in, rolin (`ADMIN` ose `DISPATCHER`) dhe fjalëkalimin (fjalëkalimi nuk shfaqet në terminal dhe duhet të ketë të paktën 12 karaktere). Për instalim të ri, krijo llogarinë `drilon123@gmail.com` si `ADMIN` dhe ekzekuto migrimin `database/migrations/20261029_add_superadmin_role.sql` për ta promovuar. Në instalimin aktual ky migrim e promovon llogarinë ekzistuese. Mos krijo Superadminë të tjerë përmes skriptit ose formave. Përdor `http://localhost:8000/?page=register` për regjistrimin e dispeçerëve dhe `http://localhost:8000/?page=login` për hyrjen. Mos i ekspozo kredencialet në dokumentacion ose Git.

Sesioni përdor cookie `HttpOnly`, `SameSite=Lax`, rotacion të ID-së pas hyrjes dhe token CSRF për hyrje/dalje dhe veprimet e moduleve. Për përdorim publik duhen gjithashtu HTTPS, konfigurim i sigurt i serverit dhe kontrolle të rregullta të sigurisë.

## Testet automatike

Me MySQL në punë dhe përdoruesin e databazës të lejuar të krijojë/fshijë databaza, ekzekuto nga direktoria kryesore:

```bash
/Applications/XAMPP/xamppfiles/bin/php tests/run.php
```

Testet mbulojnë hyrjen, rolet, modulet CRUD, menaxhimin e llogarive dhe kufizimet e shoferëve në API-në mobile. Çdo ekzekutim krijon databazë prove me emër unik dhe e fshin në fund; nuk përdor databazën e aplikacionit. Dërgimi SMTP çaktivizohet për procesin e serverit testues, ndaj nuk dërgohen email-e reale. Për konfigurim tjetër të MySQL, cakto `DB_HOST`, `DB_PORT`, `DB_USER` dhe `DB_PASSWORD` para ekzekutimit.

## Aplikacioni mobil me Expo Go

Aplikacioni për Android/iOS gjendet te `mobile/` dhe përdor Expo Router. Ai përdor API-në JSON te `public/api.php`; token-i i hyrjes skadon pas 30 ditësh, ruhet në SecureStore dhe në databazë ruhet vetëm hash-i i tij.

Për databazën ekzistuese, ekzekuto një herë migrimet sipas radhës: [database/migrations/20261118_add_mobile_api_tokens.sql](database/migrations/20261118_add_mobile_api_tokens.sql), pastaj [database/migrations/20261126_add_mobile_driver_accounts.sql](database/migrations/20261126_add_mobile_driver_accounts.sql). Instalimet e reja i marrin tabelat dhe lidhjen e shoferëve nga [database/schema.sql](database/schema.sql).

V2 përfshin llogari të veçanta shoferësh (hyrja e tyre lejohet vetëm në aplikacionin mobil), kalendar javor, cache dhe radhë sinkronizimi offline me riprovim/fshirje për veprimet e dështuara, kujtesa lokale një orë para nisjes dhe kyçje biometrike. Shoferët shohin vetëm transportet e caktuara për ta dhe mund të kalojnë statusin nga “Planifikuar” në “Në proces” e pastaj “Përfunduar”. Ruajtja e dëshmisë së dorëzimit nuk përfshihet.

Cache-i dhe veprimet në pritje ruhen lokalisht në SQLite për secilën llogari. Krijimi offline ruhet si veprim i ri; ndryshimet ekzistuese të transportit nuk dërgohen në radhë offline. Për siguri, shqyrto gabimet e radhës te **Cilësimet → Ndryshime për sinkronizim** dhe provo sërish ose fshiji.

1. Sigurohu që MySQL të jetë i ndezur në XAMPP. Serveri PHP do të përdorë portin 8000; nëse porti është i zënë, zgjidh një port tjetër të lirë.
2. Nga direktoria kryesore e projektit, nis serverin PHP në rrjetin lokal:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/php -S 0.0.0.0:8000 -t public
   ```

3. Gjej IP-në lokale të Mac-ut:

   ```bash
   ipconfig getifaddr en0
   ```

   Nëse nuk kthen adresë, kontrollo ndërfaqen aktive me `ifconfig`.
4. Kopjo `mobile/.env.example` si `mobile/.env` dhe vendos IP-në e kompjuterit:

   ```dotenv
   EXPO_PUBLIC_API_URL=http://192.168.1.10:8000/api.php
   ```

   Zëvendëso `192.168.1.10` me IP-në reale. Telefoni dhe kompjuteri duhet të jenë në të njëjtin Wi-Fi; mos përdor `localhost` në telefon.
5. Nga `mobile/`, instalo varësitë një herë me `npm install`, pastaj nis Expo:

   ```bash
   npm start
   ```

   Skano QR-në me Expo Go. Pas ndryshimit të `mobile/.env`, rinis Expo që të ngarkohet adresa e re. Projekti kërkon një version Node.js të mbështetur nga Expo (p.sh. Node 24 LTS); Node 25 nuk mbështetet nga versioni aktual i Expo. Kujtesat lokale mund të testohen në Expo Go; për Face ID/biometrikë në iOS duhet krijuar development build që të përfshihet konfigurimi native i plugin-it.

API-ja dhe aplikacioni janë për testim lokal; ky udhëzim nuk publikon aplikacionin dhe nuk ekspozon serverin në internet. Mos i ruaj kredencialet apo `mobile/.env` në Git.

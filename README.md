# Transportation Company Management

Aplikacion PHP për përgatitjen e sistemit të menaxhimit të një kompanie transporti. Skeleti fillestar përfshin panelin kryesor, navigimin dhe faqet për transportet, klientët, shoferët dhe automjetet. Modulet janë aktualisht ndërfaqe fillestare dhe do të lidhen me databazën në fazat pasuese.

## Databaza

Paneli përdor PDO për t'u lidhur me MySQL dhe lexon numrin e transporteve, klientëve, shoferëve dhe automjeteve. Importo [database/schema.sql](database/schema.sql) dhe, nëse dëshiron të dhëna demo, [database/sample_data.sql](database/sample_data.sql); udhëzimet e hollësishme gjenden te [database/README.md](database/README.md).

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
  Views/
    components/
      navigation.php        Navigimi i përbashkët i aplikacionit
    pages/
      dashboard.php         Paneli kryesor dhe lidhjet te modulet
      module-placeholder.php Pamja fillestare e moduleve
      not-found.php          Pamja për adresa të panjohura
    layout.php              Struktura e përbashkët e faqeve
database/
  schema.sql                Struktura e databazës MySQL
  sample_data.sql           Të dhëna demo për databazën
src/
  Database/
    Connection.php          Lidhja e përbashkët me MySQL përmes PDO
```

## Navigimi

Përdor menynë anësore ose kartat në panelin kryesor për të hapur:

- Panelin kryesor
- Transportet
- Klientët
- Shoferët
- Automjetet

Faqet e moduleve shënohen si “Në përgatitje”; ato nuk lexojnë apo ndryshojnë ende të dhëna. Aktualisht PDO përdoret nga paneli për numërimin e regjistrimeve; listimi, validimi dhe veprimet funksionale të moduleve janë punë për fazat pasuese. Detajet për krijimin e databazës gjenden te [database/README.md](database/README.md).
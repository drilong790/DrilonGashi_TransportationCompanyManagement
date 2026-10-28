# Transportation Company Management

Aplikacion PHP për përgatitjen e sistemit të menaxhimit të një kompanie transporti. Skeleti fillestar përfshin panelin kryesor, navigimin dhe faqet për transportet, klientët, shoferët dhe automjetet. Modulet janë aktualisht ndërfaqe fillestare dhe do të lidhen me databazën në fazat pasuese.

## Nisja lokalisht

Kërkohet PHP 8.0 ose më i ri. Nga direktoria kryesore e projektit, nis serverin e integruar të PHP-së:

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
```

## Navigimi

Përdor menynë anësore ose kartat në panelin kryesor për të hapur:

- Panelin kryesor
- Transportet
- Klientët
- Shoferët
- Automjetet

Faqet e moduleve shënohen si “Në përgatitje”; ato nuk lexojnë apo ndryshojnë ende të dhëna. Lidhja me MySQL, validimi dhe veprimet funksionale janë punë për fazat pasuese. Detajet për krijimin e databazës gjenden te [database/README.md](database/README.md).
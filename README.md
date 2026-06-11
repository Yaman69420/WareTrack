<div align="center">

# WareTrack

**Warehouse Management System — Laravel 13 + Livewire 4**

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tests](https://img.shields.io/badge/tests-220%20passing-22c55e?style=for-the-badge&logo=checkmarx&logoColor=white)

*Eindwerk 2025–2026 — Traject B*

</div>

---

## Overzicht

WareTrack is een volledig uitgewerkt Warehouse Management System (WMS) gebouwd met Laravel 13 en Livewire 4. Het laat bedrijven toe om magazijnen, producten, stockniveaus, leveringen van leveranciers en stockbewegingen over meerdere locaties te beheren — met volledige auditlogging, rol-gebaseerde toegangscontrole en automatische low-stock notificaties.

### Functionaliteiten

| Domein | Wat |
|---|---|
| **Stock** | Incoming, outgoing, transfer en correctie — elk met DB-transactie en concurrent-safe locking |
| **Magazijnen** | Multi-warehouse, multi-locatie; heatmap-visualisatie van stock per locatie |
| **Producten** | CRUD met afbeelding, SKU, categorie en minimum stockniveau |
| **Leveringen** | Supplier → delivery items → automatische stockverhoging; status timeline |
| **Bulk correctie** | Meerdere locaties tegelijk corrigeren via inline tabel |
| **Activity log** | Volledige audit trail van alle stockbewegingen, filterbaar op user/type/datum |
| **Notificaties** | Low-stock e-mailwaarschuwingen via Laravel Queues (ShouldQueue, 24h throttle) |
| **Rapportage** | Stock per locatie + bewegingshistoriek, exporteerbaar als CSV |
| **Dashboard** | Chart.js grafieken: bewegingen per dag en stock per magazijn |
| **Rollen** | Admin (volledig beheer) en Warehouse Worker (stock operaties) |

---

## Tech stack

| Laag | Technologie |
|---|---|
| Framework | Laravel 13 |
| Reactieve UI | Livewire 4 (full-page components) |
| UI componenten | Flux UI (free tier) |
| Styling | Tailwind CSS 4, GSAP (login animatie) |
| Database | MySQL 8.0 |
| Authenticatie | Laravel Fortify |
| Testing | Pest 4 (220 tests, 455 assertions) |
| Queues | Laravel Queue — database driver |
| Audit log | Tweeledig: eigen `stock_movements` tabel voor stockmutaties (user, type, qty, locatie, timestamp) + `spatie/laravel-activitylog` voor masterdata-wijzigingen (dashboard "recente activiteit") |
| Code stijl | Laravel Pint |

---

## Vereisten

- PHP 8.4+
- Composer
- Node.js 20+ en npm
- MySQL 8.0+
- Lokale server (MAMP, Herd, Valet, …)

---

## Installatie

```bash
# 1. Clone de repository
git clone https://github.com/Yaman69420/WareTrack.git
cd WareTrack

# 2. PHP dependencies
composer install

# 3. JS dependencies
npm install

# 4. Environment bestand
cp .env.example .env
php artisan key:generate
```

Pas `.env` aan met jouw database-instellingen:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=waretrack
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log          # e-mails worden gelogd naar storage/logs/laravel.log
QUEUE_CONNECTION=database
```

```bash
# 5. Maak de database aan (in MySQL)
# CREATE DATABASE waretrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 6. Migraties + demo data
php artisan migrate --seed

# 7. Storage symlink
php artisan storage:link

# 8. Assets builden
npm run build
```

De applicatie is beschikbaar op de URL die je hebt ingesteld als `APP_URL` (bv. `http://waretrack.test`).

### Queue worker (voor notificaties)

Low-stock notificaties worden asynchroon verstuurd via de database queue. Start de worker met:

```bash
php artisan queue:work --queue=notifications,default
```

> **Demo-tip:** zonder actieve queue worker worden notificaties niet verstuurd. Met `MAIL_MAILER=log` verschijnen ze in `storage/logs/laravel.log`.

---

## Demo accounts

| Rol | E-mail | Wachtwoord |
|---|---|---|
| **Admin** | admin@waretrack.test | password |
| **Warehouse Worker** | worker@waretrack.test | password |

De seeder laadt automatisch demo-data: 3 magazijnen, 12 locaties, producten, categorieën, leveranciers, leveringen en stockbewegingen.

---

## Demo flow (voor de jury)

1. Login als **Admin** → bekijk het dashboard (grafieken, low-stock widget)
2. Ga naar **Stock** → toon de heatmap op een magazijn (Warehouses → show → Heatmap)
3. Registreer een **stockbeweging** (Register Movement) — toon de warehouse-cascade UX
4. Maak een **bulk correctie** (Stock → Bulk Correction) voor een heel magazijn
5. Open **Activity log** → filter op type/user/datum
6. Maak een **levering** aan en process hem → bekijk de status timeline
7. Login als **Warehouse Worker** → toon beperkte navigatie (geen admin-sectie)
8. Check `storage/logs/laravel.log` voor de low-stock e-mail

---

## Tests uitvoeren

```bash
# Alle tests
./vendor/bin/pest

# Specifieke suite
./vendor/bin/pest tests/Feature/StockServiceTest.php

# Code stijl controleren
./vendor/bin/pint --test
```

Huidig resultaat: **220 tests, 455 assertions — all passing**.

---

## Projectstructuur

```
app/
├── Enums/              # UserRole, StockMovementType, DeliveryStatus
├── Events/             # StockMovementRegistered
├── Exceptions/         # InsufficientStockException
├── Http/Middleware/    # EnsureUserIsAdmin
├── Listeners/          # SendLowStockNotification (ShouldQueue)
├── Livewire/           # Full-page Livewire componenten per domein
│   ├── Activity/       # Audit log pagina
│   ├── Categories/
│   ├── Deliveries/     # Index, Show, Create
│   ├── Locations/
│   ├── Products/       # Index, Show
│   ├── Reports/
│   ├── Stock/          # Index, Movements, CreateMovement, BulkCorrection
│   ├── Suppliers/
│   ├── Users/
│   ├── Warehouses/     # Index, Show (met heatmap)
│   └── Dashboard.php
├── Models/             # Eloquent models met relaties en policies
├── Notifications/      # LowStockAlert (Markdown mail)
├── Policies/           # Autorisatie per model
└── Services/           # StockService, WarehouseService, ReportService

database/
├── migrations/         # Alle migraties in chronologische volgorde
├── factories/          # Model factories voor testen en seeding
└── seeders/            # DatabaseSeeder + domein-seeders voor demo

resources/
├── css/app.css         # Tailwind + WareTrack dark theme overrides
├── views/
│   ├── emails/         # Low-stock Markdown mail template
│   ├── layouts/        # App layout (sidebar) + auth layout
│   └── livewire/       # Blade views per Livewire component

tests/
└── Feature/            # Pest feature tests per domein
```

---

## Licentie

Dit project is ontwikkeld als academisch eindwerk en is niet gelicenseerd voor commercieel gebruik.

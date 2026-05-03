# rules.md — WareTrack WMS

## Doel van dit document

Dit document is de vaste bouw- en architectuurrichtlijn voor WareTrack in PhpStorm. Alles wat gegenereerd, aangepast of voorgesteld wordt, moet compatibel zijn met:

- Laravel 13
- Livewire 4
- De officiële Laravel Livewire starter kit als basis
- Flux UI free tier
- MySQL / MariaDB
- Pest

WareTrack is een Warehouse Management System (WMS) voor het beheren van producten, magazijnen, locaties, stockbewegingen, leveranciers en leveringen. Het is een interne backoffice-applicatie. Er is geen publieke shopfrontend. De enige publieke pagina is de loginpagina.

Dit is een Traject B eindwerk. Dat betekent dat architectuur, datamodel, logging, foutafhandeling en schaalbaarheid op een professioneel niveau worden verwacht — niet alleen dat het werkt, maar ook dat het onderhoudbaar en doordacht is opgebouwd.

---

## Projectcontext

### Startpunt
- Nieuw Laravel 13 project opgezet via de officiële Livewire starter kit
- Flux UI free tier geïnstalleerd als UI component library
- De codebase draait op Laravel 13 en Livewire 4
- We bouwen alles van nul, gestructureerd per domein

### Hoofddoel
Bouw een professioneel WMS met:
- Twee gebruikersrollen: `admin` en `warehouse_worker`
- Volledig productbeheer met afbeeldingen
- Magazijn- en locatiebeheer
- Correcte en veilige stockregistratie per locatie
- Volledige stockbewegingenhistoriek (audit trail)
- Transfers tussen locaties
- Minimum stockniveaus met waarschuwingen
- Leveranciers- en leveringsmodule
- Dashboard met overzichten en statistieken
- Rapportage en filtering

---

## Stack en versieregels

### Verplichte stack
- Laravel 13
- Livewire 4 (single-file components als standaard)
- Flux UI free tier voor UI components
- Tailwind CSS via Vite
- MySQL of MariaDB
- Pest voor alle tests
- PHP 8.3+

### Dev-only packages
- `barryvdh/laravel-debugbar` — enkel in development, nooit in productie, staat in `require-dev`

### Aanbevolen packages (Traject B)
- `spatie/laravel-activitylog` — voor audit trail buiten stockbewegingen (productwijzigingen, leverancierswijzigingen, gebruikersacties)

### Verboden
Gebruik nooit:
- Livewire 2 of Livewire 3 syntax die afwijkt van Livewire 4
- Oude Volt route-syntax (`Volt::route()`)
- Laravel 10/11/12 voorbeelden die niet 100% compatibel zijn met Laravel 13
- Filament als admin framework
- Inertia, Vue, React
- Alpine-first oplossingen voor zaken die in Livewire 4 thuishoren
- `float` voor hoeveelheden of prijzen
- Hardcoded IDs
- Business logic in Blade views
- Business logic in routes
- Business logic in models (behalve scopes, accessors, casts en kleine domeinhulpen)
- Inline queries in views of render-methodes
- Tutorial-stijl hacks in plaats van Laravel-conventies

---

## Domeinstructuur

WareTrack bestaat uit deze domeinen:

- **Auth** — authenticatie, rollen, middleware
- **Products** — producten, categorieën, afbeeldingen
- **Warehouses** — magazijnen en locaties
- **Stock** — stockniveaus, stockbewegingen, audit trail
- **Suppliers** — leveranciers en leveringen
- **Dashboard** — overzichten, statistieken, waarschuwingen
- **Reports** — rapportage en filtering

### Mapstructuur

```
app/
├── Actions/
│   ├── Products/
│   │   ├── CreateProductAction.php
│   │   ├── UpdateProductAction.php
│   │   └── DeleteProductAction.php
│   ├── Stock/
│   │   ├── RegisterIncomingStockAction.php
│   │   ├── RegisterOutgoingStockAction.php
│   │   ├── TransferStockAction.php
│   │   └── AdjustStockAction.php
│   ├── Suppliers/
│   │   ├── CreateDeliveryAction.php
│   │   └── ProcessDeliveryAction.php
│   └── Warehouses/
│       ├── CreateWarehouseAction.php
│       └── CreateLocationAction.php
├── Enums/
│   ├── UserRole.php
│   ├── StockMovementType.php
│   └── DeliveryStatus.php
├── Exceptions/
│   └── InsufficientStockException.php
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   │   └── EnsureUserIsAdmin.php
│   └── Requests/
├── Livewire/
│   ├── Auth/
│   ├── Dashboard/
│   │   └── DashboardPage.php
│   ├── Forms/
│   │   ├── ProductForm.php
│   │   └── DeliveryForm.php
│   ├── Products/
│   │   ├── ProductsIndexPage.php
│   │   ├── ProductCreatePage.php
│   │   └── ProductEditPage.php
│   ├── Categories/
│   │   ├── CategoriesIndexPage.php
│   │   └── CategoryFormPage.php
│   ├── Warehouses/
│   │   ├── WarehousesIndexPage.php
│   │   ├── WarehouseCreatePage.php
│   │   └── WarehouseEditPage.php
│   ├── Locations/
│   │   ├── LocationsIndexPage.php
│   │   └── LocationFormPage.php
│   ├── Stock/
│   │   ├── StockOverviewPage.php
│   │   ├── StockMovementsPage.php
│   │   └── StockMovementCreatePage.php
│   ├── Suppliers/
│   │   ├── SuppliersIndexPage.php
│   │   └── SupplierFormPage.php
│   ├── Deliveries/
│   │   ├── DeliveriesIndexPage.php
│   │   ├── DeliveryCreatePage.php
│   │   └── DeliveryShowPage.php
│   └── Reports/
│       └── ReportsPage.php
├── Models/
├── Policies/
├── Services/
│   ├── StockService.php
│   ├── WarehouseService.php
│   └── ReportService.php
└── Support/
```

---

## Models

### User
```
id, name, email, password, role (enum: admin|warehouse_worker),
email_verified_at, remember_token, timestamps
```
- Cast `role` naar `UserRole` enum
- Scope: `admin()`, `warehouseWorkers()`

### Category
```
id, name, slug, description (nullable), is_active (default true),
timestamps, soft deletes
```
- Slug uniek, gegenereerd uit naam
- Index op slug

### Product
```
id, category_id (nullable FK), name, sku, description (nullable),
min_stock_level (int, default 0), is_active (default true),
timestamps, soft deletes
```
- SKU uniek, index op SKU
- Soft deletes
- `category_id` nullable (product kan zonder categorie bestaan)

### ProductImage
```
id, product_id (FK cascade delete), path, is_primary (default false), timestamps
```
- Één product kan meerdere afbeeldingen hebben
- `is_primary` geeft de hoofdafbeelding aan

### Warehouse
```
id, name, address (nullable), is_active (default true), timestamps, soft deletes
```

### Location
```
id, warehouse_id (FK), name, code (nullable), description (nullable),
is_active (default true), timestamps, soft deletes
```
- Code optioneel maar nuttig (bijv. "A-01-03" voor rek A, rij 1, vak 3)
- Index op warehouse_id

### Stock
```
id, product_id (FK), location_id (FK), quantity (int, default 0),
timestamps
```
- Unieke combinatie van product_id + location_id
- Nooit negatief — afdwingen op service-niveau én via check
- Dit is de **huidige stock**, geen historiek

### StockMovement
```
id, product_id (FK), location_id (FK nullable), from_location_id (FK nullable),
to_location_id (FK nullable), user_id (FK), type (enum),
quantity (int — signed), reference (nullable), notes (nullable), timestamps
```
- `type` = `StockMovementType` enum: `incoming`, `outgoing`, `transfer`, `correction`
- `quantity` is een **signed integer**: positief = stocktoename, negatief = stockafname
  - `incoming`: `location_id` ingesteld, `quantity > 0`
  - `outgoing`: `location_id` ingesteld, `quantity < 0`
  - `transfer`: `from_location_id` + `to_location_id` ingesteld, `location_id = null`, `quantity > 0` (de overgedragen hoeveelheid; vermindering op `from`, verhoging op `to`)
  - `correction`: `location_id` ingesteld, `quantity` = `newQty - oldQty` (kan positief of negatief zijn)
- Nooit wijzigbaar na aanmaak — audit trail is immutable
- Index op product_id, user_id, type, created_at

### Supplier
```
id, name, contact_person (nullable), email (nullable), phone (nullable),
is_active (default true), timestamps, soft deletes
```

### Delivery
```
id, supplier_id (FK), user_id (FK), reference_number (nullable),
status (enum: pending|received|partial), notes (nullable),
delivered_at (nullable date), timestamps, soft deletes
```
- `user_id` = de ingelogde gebruiker die de levering heeft geregistreerd (voor accountability)

### DeliveryItem
```
id, delivery_id (FK cascade), product_id (FK), location_id (FK),
quantity (int unsigned), unit_price (decimal 10,2 nullable), timestamps
```

---

## Enums

### UserRole
```php
enum UserRole: string
{
    case Admin = 'admin';
    case WarehouseWorker = 'warehouse_worker';
}
```

### StockMovementType
```php
enum StockMovementType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Transfer = 'transfer';
    case Correction = 'correction';
}
```

### DeliveryStatus
```php
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Partial = 'partial';
}
```

Alle enums in `app/Enums`. Gebruik casts in models.

---

## Services

### StockService
De kern van het systeem. Bevat alle stockmutaties verpakt in DB transactions.

Verplichte methodes:
- `registerIncoming(Product, Location, int $qty, User, ?string $reference, ?string $notes): StockMovement`
- `registerOutgoing(Product, Location, int $qty, User, ?string $reference, ?string $notes): StockMovement`
- `transfer(Product, Location $from, Location $to, int $qty, User, ?string $notes): StockMovement`
- `adjust(Product, Location, int $newQty, User, ?string $notes): StockMovement` — stelt de absolute stockwaarde in; de service berekent `quantity = newQty - oldQty` en logt dat als een `correction` StockMovement (positief = toename, negatief = afname, beide geldige signed integers)
- `getCurrentStock(Product, Location): int`
- `getTotalStockForProduct(Product): int`

Regels voor StockService:
- Elke mutatie gebeurt in een `DB::transaction()`
- Gooit een exception als negatieve stock het resultaat zou zijn
- Maakt altijd een `StockMovement` aan als audit trail
- Gebruikt `lockForUpdate()` bij het ophalen van stockrecords binnen transactie om race conditions te vermijden
- Nooit rechtstreeks `Stock::update()` aanroepen buiten deze service

### WarehouseService
- `getLocationsForWarehouse(Warehouse): Collection`
- `getStockSummaryForWarehouse(Warehouse): Collection`

### ReportService
- `getLowStockProducts(): Collection`
- `getMovementsForPeriod(Carbon $from, Carbon $to, array $filters): Collection`
- `getStockPerLocation(?Warehouse $warehouse): Collection`

---

## Actions

Één action = één concrete business operatie. Eén publieke methode `handle()`. Geen view rendering.

Actions orchestreren: ze valideren input, roepen de service aan, loggen indien nodig.

Voorbeelden:
- `RegisterIncomingStockAction::handle(array $data, User $user): StockMovement`
- `TransferStockAction::handle(array $data, User $user): StockMovement`
- `ProcessDeliveryAction::handle(Delivery $delivery, User $user): void`
- `CreateProductAction::handle(array $data): Product`

Actions zitten in `app/Actions/` georganiseerd per domein.

---

## Livewire 4 regels

### Componentstrategie
- Standaard: single-file Livewire componenten in Livewire 4 stijl
- Pagina-componenten als full-page component
- Componenten zijn mager: roepen Actions/Services aan, bevatten geen zware logica zelf

### Componentregels
Elke Livewire component:
- Heeft één duidelijke verantwoordelijkheid
- Valideert correct: gebruik `#[Validate]` voor eenvoudige formulieren (1–3 velden), gebruik een Form Object voor complexe formulieren (product aanmaken/bewerken, levering aanmaken)
- Gebruikt `#[Computed]` voor afgeleide waarden
- Gebruikt `WithPagination` waar van toepassing — standaard 25 items per pagina
- Gebruikt `WithFileUploads` voor afbeeldingen
- Gooit geen exceptions die onafgehandeld naar de gebruiker gaan

### State-regels
- UI-state hoort in Livewire
- Domeinstate hoort in database via services
- Livewire componenten roepen Actions aan, niet rechtstreeks de database

### Routingregels
- `Route::get()` met Livewire full-page component
- Named routes altijd in de vorm `domein.actie`:
  - `dashboard` — hoofddashboard
  - `products.index`, `products.create`, `products.edit`
  - `categories.index`, `categories.create`, `categories.edit`
  - `warehouses.index`, `warehouses.create`, `warehouses.edit`
  - `locations.index`, `locations.create`, `locations.edit`
  - `stock.overview`, `stock.movements`, `stock.movements.create`
  - `suppliers.index`, `suppliers.create`, `suppliers.edit`
  - `deliveries.index`, `deliveries.create`, `deliveries.show`
  - `reports.index`
- Route groepen:
  ```php
  // Alle app-routes zitten in de auth middleware groep
  Route::middleware(['auth'])->group(function () {
      // Toegankelijk voor admin én warehouse_worker
      Route::get('/dashboard', ...)->name('dashboard');
      Route::get('/stock/...', ...);
      Route::get('/reports/...', ...);
      Route::get('/suppliers', ...)->name('suppliers.index');
      Route::get('/suppliers/{supplier}', ...)->name('suppliers.show');
      Route::get('/deliveries', ...)->name('deliveries.index');
      Route::get('/deliveries/{delivery}', ...)->name('deliveries.show');

      // Enkel toegankelijk voor admin
      Route::middleware(['admin'])->group(function () {
          Route::get('/products/...', ...);
          Route::get('/warehouses/...', ...);
          Route::get('/locations/...', ...);
          Route::get('/suppliers/create', ...)->name('suppliers.create');
          Route::get('/suppliers/{supplier}/edit', ...)->name('suppliers.edit');
          Route::get('/deliveries/create', ...)->name('deliveries.create');
          // ...
      });
  });
  ```
- Geen onduidelijke mengvormen

---

## Laravel 13 architectuurregels

### Separation of concerns

#### Routes
Doen alleen:
- Endpoint definitie
- Middleware koppelen
- Livewire component aanroepen

#### Controllers
Zo dun mogelijk. Valideren, Action aanroepen, response teruggeven.
Voor WareTrack worden de meeste routes direct naar Livewire full-page componenten gestuurd. Controllers worden gebruikt voor niet-Livewire responses (bijv. file downloads).

#### Livewire componenten
Mogen:
- User input verwerken
- Valideren
- UI-state beheren
- Actions en Services aanroepen

Mogen niet:
- Stockmutaties zelf uitvoeren zonder via StockService te gaan
- Complexe queryketens bevatten
- Business rules implementeren

#### Models
Bevatten alleen:
- Relaties
- Scopes
- Casts
- Accessors / mutators
- Kleine domeinhulpen

---

## Database-regels

### Hoeveelheden en prijzen
- `Stock.quantity`: `integer unsigned` — huidige stockniveau, nooit negatief
- `StockMovement.quantity`: `integer` (signed) — positief = toename, negatief = afname
- `DeliveryItem.quantity`: `integer unsigned` — altijd positief
- Prijzen (bijv. `unit_price` op `DeliveryItem`): `decimal(10,2)` — nooit `float`

### Foreign keys
- Altijd expliciet met `constrained()` en correcte delete-regels
- Denk bewust na over `cascade`, `restrict`, `nullOnDelete`
- StockMovement: product_id en user_id → `restrict` (nooit zomaar deleten)
- DeliveryItem: `cascade` wanneer delivery verwijderd wordt

### Indexes
Verplicht op:
- `sku` (products)
- `slug` (categories)
- `product_id` + `location_id` combinatie (stock — uniek)
- `product_id`, `user_id`, `type`, `created_at` (stock_movements)
- Foreign keys

### Soft deletes
Op: `products`, `categories`, `warehouses`, `locations`, `suppliers`, `deliveries`
Niet op: `stock`, `stock_movements` (die zijn immutable audit data)

**Belangrijk:** `delivery_items` heeft geen soft deletes maar wel `onDelete('cascade')` op de foreign key naar `deliveries`. Dat cascade werkt enkel bij een harde delete. Omdat `deliveries` soft deletes gebruikt, worden `delivery_items` nooit automatisch verwijderd in de praktijk — ze blijven zichtbaar via de soft-deleted delivery indien nodig. Dit is correct gedrag.

### Seeders
Na `php artisan migrate:fresh --seed` moet het systeem demo-klaar zijn:
- 1 admin user + 2 warehouse workers
- Minstens 3 warehouses met locaties
- Minstens 20 producten met stock
- Stockbewegingen historiek
- Minstens 3 leveranciers met leveringen

---

## Policies en Middleware

### Middleware
`EnsureUserIsAdmin`:
- Blokkeert niet-admins van admin-only routes
- Registreren als alias in `bootstrap/app.php`

### Policies

`ProductPolicy`:
- `viewAny`, `view`: iedereen die ingelogd is
- `create`, `update`, `delete`: enkel admin

`CategoryPolicy`:
- `viewAny`, `view`: iedereen die ingelogd is
- `create`, `update`, `delete`: enkel admin

`WarehousePolicy` / `LocationPolicy`:
- Zelfde structuur als ProductPolicy

`StockMovementPolicy`:
- `viewAny`, `view`: iedereen die ingelogd is
- `create`: admin én warehouse_worker
- `update`, `delete`: niemand — stockbewegingen zijn immutable

`SupplierPolicy` / `DeliveryPolicy`:
- `viewAny`, `view`: admin én warehouse_worker (warehouse workers moeten leveringen kunnen raadplegen)
- `create`, `update`, `delete`: enkel admin

---

## Stockintegriteitsregels

Dit zijn de meest kritische regels van het systeem:

1. **Nooit stock aanpassen buiten `StockService`** — geen directe `Stock::update()` of `Stock::create()` in Livewire, Actions of Controllers
2. **Elke stockmutatie zit in een `DB::transaction()`**
3. **Gebruik `lockForUpdate()`** bij het ophalen van stockrecords binnen een transactie
4. **Gooi een noemende exception** als een operatie negatieve stock zou veroorzaken: `InsufficientStockException`
5. **Elke mutatie creëert een `StockMovement`** — deze is na aanmaak nooit wijzigbaar of verwijderbaar
6. **Stock kan nooit negatief zijn** — afdwingen op service-niveau, niet enkel op UI-niveau

---

## Logging

Gebruik Laravel's Log facade voor kritieke operaties:

```php
Log::info('Stock movement registered', [
    'user_id' => $user->id,
    'product_id' => $product->id,
    'type' => $movement->type,
    'quantity' => $movement->quantity,
]);
```

Log verplicht bij:
- Elke stockmutatie
- Elke levering die verwerkt wordt
- Elke poging tot negatieve stock (als warning)
- Login van admin gebruikers

---

## Testing-regels

Gebruik Pest. Geen PHPUnit-stijl tenzij Pest het niet ondersteunt (wat zelden is).

### Minimaal te voorzien

**Feature tests:**
- Auth: login, logout, rolgebaseerde toegang (admin vs worker vs guest)
- Product CRUD: aanmaken, wijzigen, verwijderen, soft delete
- Image upload: geldig bestand, ongeldig bestand
- Stock incoming: correcte stockverhoging, beweging aangemaakt
- Stock outgoing: correcte stockverlaging, beweging aangemaakt, negatieve stock geblokkeerd
- Transfer: correcte verlaging op from, verhoging op to, beweging aangemaakt
- Delivery: verwerking verhoogt stock correct
- Minimum stock: warning correct wanneer stock onder minimum

**Unit tests:**
- `StockService`: transactie-integriteit, negatieve stock exception
- Minstens één model scope
- Minstens één enum methode

### Testregels
- Gebruik factories voor alle testdata
- Gebruik `RefreshDatabase`
- Geen hardcoded IDs
- Beschrijvende test namen in snake_case
- Test zowel happy path als edge cases

---

## Frontend-regels

### Flux UI
- Gebruik Flux UI components als standaard voor alle UI elementen (tabellen, formulieren, modals, buttons, badges, etc.)
- Flux UI free tier — gebruik geen betaalde componenten
- Pas Tailwind utility classes toe voor custom spacing/layout
- Consistent design systeem doorheen hele app

### Image storage
- Gebruik de `public` disk (`storage/app/public`)
- Voer `php artisan storage:link` uit bij installatie
- Sla paden relatief op in de database (bijv. `products/afbeelding.jpg`)
- Valideer bij upload: mimes `jpg,jpeg,png,webp`, max `2048` KB
- Verwijder oude bestanden van disk wanneer een afbeelding vervangen of verwijderd wordt

### Login / landingspagina
- Slanke custom pagina bovenop de starter kit auth
- Kleine GSAP animatie voor professionele eerste indruk
- Tailwind-based, consistent met de rest van de app

### Responsive
- Desktop-first (WMS wordt intern gebruikt, maar mobiel moet werken)
- Minimum: desktop en tablet correct
- Gebruik Flux UI's ingebouwde responsive utilities

### Views
- Blade blijft presentatielaag
- Geen datamanipulatie in Blade
- Gebruik Flux UI components en Blade partials

---

## Codekwaliteitsregels

### Naming
- Duidelijke Engelse namen voor classes, methods en properties
- Consistente WMS-domeinterminologie:
  - `Warehouse` (niet `Storage` of `Depot`)
  - `Location` (niet `Shelf` of `Spot`)
  - `StockMovement` (niet `StockLog` of `Mutation`)
  - `Delivery` (niet `Shipment` of `Receipt` — tenzij inkomend specifiek)
- Geen afkortingen tenzij standaard (bijv. `SKU`, `qty`)

### Methods
- Klein en doelgericht
- Return types altijd vermeld
- Lange methodes opsplitsen

### Classes
- Geen god classes
- Maximaal coherente verantwoordelijkheid per class
- Componenten van meer dan 200 regels: analyseer of opsplitsing nodig is

### Queries
- Eager load relaties bewust (`with()`)
- Geen N+1 problemen
- Query scopes voor herbruikbare filters
- Geen querylogica in views

---

## GitHub-regels

### Branches
```
main    → production-ready, altijd stabiel
dev     → integratiebranch
feature/xxx → per feature
fix/xxx → bugfixes
```

### Commits (Conventional Commits)
```
feat(products): add image upload with validation
feat(stock): implement DB transactions in StockService
fix(dashboard): correct low stock threshold calculation
refactor(services): extract reporting logic into ReportService
test(stock): add feature tests for negative stock prevention
docs(readme): add installation and seeding instructions
chore(deps): install and configure Flux UI
```

### Regels
- Nooit rechtstreeks naar `main` pushen
- Feature branches worden gemerged naar `dev` via pull request
- `.env` nooit committen — wel `.env.example` met lege sensitive values
- `vendor/` en `node_modules/` nooit in repo

---

## Definitie van klaar

Een feature is pas klaar als:
- De code compatibel is met Laravel 13 en Livewire 4
- De architectuur klopt (juiste laag voor juiste logica)
- Routes, authorization en validatie correct zitten
- Stockmutaties via `StockService` gaan met transacties
- Er geen evidente N+1 of securityfouten zijn
- Pest tests aanwezig zijn voor de feature
- Flux UI correct gebruikt wordt
- De code leesbaar is voor een andere developer

---

## Samenvattende hoofdregel

Bouw WareTrack alsof een senior Laravel developer een intern bedrijfssysteem bouwt dat andere medewerkers dagelijks gebruiken. Stockdata is kritisch — behandel het als financiële data. Elke mutatie is traceerbaar, elke bewerking is veilig, en de architectuur is zo opgezet dat een nieuwe developer de codebase in minder dan een uur begrijpt.

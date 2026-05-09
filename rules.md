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
- Livewire 4 (full-page components als standaard)
- Flux UI free tier voor UI components
- Tailwind CSS 4 via Vite
- MySQL of MariaDB
- Pest voor alle tests
- PHP 8.4+

### Aanbevolen packages (Traject B)
- `spatie/laravel-activitylog` — voor audit trail buiten stockbewegingen

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
- **Activity** — stockbewegingenhistoriek en filtering
- **Reports** — rapportage en filtering
- **Dashboard** — overzichten, statistieken, waarschuwingen

### Mapstructuur (zoals gebouwd)

```
app/
├── Enums/
│   ├── UserRole.php
│   ├── StockMovementType.php
│   └── DeliveryStatus.php
├── Events/
│   └── StockMovementRegistered.php
├── Exceptions/
│   └── InsufficientStockException.php
├── Http/
│   └── Middleware/
│       └── EnsureUserIsAdmin.php
├── Listeners/
│   └── SendLowStockNotification.php
├── Livewire/
│   ├── Activity/
│   │   └── Index.php
│   ├── Categories/
│   │   └── Index.php
│   ├── Dashboard.php
│   ├── Deliveries/
│   │   ├── Create.php
│   │   ├── Index.php
│   │   └── Show.php
│   ├── Locations/
│   │   └── Index.php
│   ├── Products/
│   │   ├── Index.php
│   │   └── Show.php
│   ├── Reports/
│   │   └── Index.php
│   ├── Stock/
│   │   ├── BulkCorrection.php
│   │   ├── CreateMovement.php
│   │   ├── Index.php
│   │   └── Movements.php
│   ├── Suppliers/
│   │   └── Index.php
│   ├── Users/
│   │   └── Index.php
│   └── Warehouses/
│       ├── Index.php
│       └── Show.php
├── Models/
│   ├── Category.php
│   ├── Delivery.php
│   ├── DeliveryItem.php
│   ├── Location.php
│   ├── Product.php
│   ├── Stock.php
│   ├── StockMovement.php
│   ├── Supplier.php
│   ├── User.php
│   └── Warehouse.php
├── Notifications/
│   └── LowStockAlert.php
├── Policies/
│   ├── CategoryPolicy.php
│   ├── DeliveryPolicy.php
│   ├── LocationPolicy.php
│   ├── ProductPolicy.php
│   ├── StockMovementPolicy.php
│   ├── SupplierPolicy.php
│   └── WarehousePolicy.php
└── Services/
    ├── ReportService.php
    ├── StockService.php
    └── WarehouseService.php
```

**Architectuurkeuze:** Livewire componenten roepen Services rechtstreeks aan. Er is geen aparte Actions-laag — voor WMS-domeinlogica is StockService de centrale orchestrator.

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
id, name, description (nullable), timestamps, soft deletes
```

### Product
```
id, category_id (nullable FK), name, sku, description (nullable),
image_path (nullable), min_stock (int, default 0),
timestamps, soft deletes
```
- SKU uniek, index op SKU
- Soft deletes
- `category_id` nullable (product kan zonder categorie bestaan)
- `image_path` = relatief pad naar storage public disk

### Warehouse
```
id, name, address (nullable), timestamps
```

### Location
```
id, warehouse_id (FK), name, code, timestamps
```
- Code verplicht — structuuridentificatie (bijv. "A-01-03")
- Index op warehouse_id

### Stock
```
id, product_id (FK), location_id (FK), quantity (int unsigned, default 0),
timestamps
```
- Unieke combinatie van product_id + location_id
- Nooit negatief — afdwingen op service-niveau
- Dit is de **huidige stock**, geen historiek

### StockMovement
```
id, product_id (FK), location_id (FK nullable), from_location_id (FK nullable),
to_location_id (FK nullable), user_id (FK), type (enum),
quantity (int — signed), reference (nullable), notes (nullable), timestamps
```
- `type` = `StockMovementType` enum: `incoming`, `outgoing`, `transfer`, `correction`
- `quantity` is een **signed integer**: positief = toename, negatief = afname
  - `incoming`: `location_id` ingesteld, `quantity > 0`
  - `outgoing`: `location_id` ingesteld, `quantity < 0`
  - `transfer`: `from_location_id` + `to_location_id`, `location_id = null`, `quantity > 0`
  - `correction`: `location_id` ingesteld, `quantity = newQty - oldQty`
- Nooit wijzigbaar na aanmaak — audit trail is immutable
- Index op product_id, user_id, type, created_at

### Supplier
```
id, name, contact_person (nullable), email (nullable), phone (nullable),
timestamps, soft deletes
```

### Delivery
```
id, supplier_id (FK), user_id (FK), reference (nullable),
status (enum: pending|processing|received), notes (nullable),
received_at (nullable timestamp), timestamps
```

### DeliveryItem
```
id, delivery_id (FK cascade), product_id (FK), location_id (FK),
quantity (int unsigned), timestamps
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
    case Partial = 'partial';
    case Received = 'received';
}
```

Alle enums in `app/Enums`. Gebruik casts in models.

---

## Services

### StockService
De kern van het systeem. Bevat alle stockmutaties verpakt in DB transactions.

Methodes:
- `registerIncoming(Product, Location, int $qty, User, ?string $reference, ?string $notes): StockMovement`
- `registerOutgoing(Product, Location, int $qty, User, ?string $reference, ?string $notes): StockMovement`
- `transfer(Product, Location $from, Location $to, int $qty, User, ?string $notes): StockMovement`
- `adjust(Product, Location, int $newQty, User, ?string $notes): StockMovement`

Elke methode:
1. Wrapt de gehele operatie in `DB::transaction()`
2. Gebruikt `lockForUpdate()` bij het ophalen van het Stock record
3. Gooit `InsufficientStockException` als negatieve stock het resultaat zou zijn
4. Maakt altijd een `StockMovement` aan als audit record
5. Dispatcht `StockMovementRegistered` event na de transactie

### WarehouseService
- `getStockSummaryForWarehouse(Warehouse): Collection`

### ReportService
- `getStockPerLocation(?Warehouse $warehouse): Collection`
- `getMovementsForPeriod(Carbon $from, Carbon $to, array $filters): Collection`

---

## Events en Listeners

### StockMovementRegistered
Gedispatcht door `StockService` na elke succesvolle stockmutatie.

```php
class StockMovementRegistered
{
    public function __construct(
        public readonly Product $product,
        public readonly StockMovement $movement,
    ) {}
}
```

### SendLowStockNotification
Geregistreerd in `AppServiceProvider::boot()` via `Event::listen()`.

- Implementeert `ShouldQueue` + `InteractsWithQueue`
- Queue: `notifications`, tries: 3
- Logic: haalt product fresh op, controleert `min_stock`, throttle 24h via cache, notificeert alle admins
- Notification class: `LowStockAlert` (Markdown mail)

---

## Livewire 4 regels

### Componentstrategie
- Standaard: full-page Livewire componenten
- Componenten roepen Services rechtstreeks aan — geen aparte Actions-laag
- Mager: geen zware businesslogica in de component zelf

### Componentregels
Elke Livewire component:
- Heeft één duidelijke verantwoordelijkheid
- Gebruikt `#[Computed]` voor afgeleide waarden (queries, gefilterde data)
- Gebruikt `#[Url]` voor filterstate die in de URL moet staan
- Gebruikt `WithPagination` waar van toepassing — standaard 25 items per pagina
- Gebruikt `#[Layout('layouts.app')]` als decorateur
- Valideert via `$this->validate([...])` in de save/submit methode
- Gooit geen onafgehandelde exceptions naar de gebruiker

### Cascade-patroon (warehouse → location)
Bij formulieren met warehouse → location cascade:
- Aparte `warehouseId` en `locationId` properties
- `updatedWarehouseId()` reset `$this->locationId = null`
- `locations()` computed: `Location::where('warehouse_id', $this->warehouseId)->get()`
- Blade toont location-select pas als `$warehouseId` ingevuld is

### State-regels
- UI-state hoort in Livewire
- Domeinstate hoort in database via Services
- Livewire componenten roepen `StockService` aan, nooit rechtstreeks `Stock::update()`

### Routingregels
Named routes in de vorm `domein.actie`:
- `dashboard`
- `products.index`, `products.show`
- `categories.index`
- `warehouses.index`, `warehouses.show`
- `locations.index`
- `stock.index`, `stock.movements`, `stock.movements.create`, `stock.bulk-correction`
- `suppliers.index`
- `deliveries.index`, `deliveries.create`, `deliveries.show`
- `reports.index`
- `activity.index`
- `users.index`

Route groepen:
```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Toegankelijk voor admin én warehouse_worker
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/stock/...', ...);
    Route::get('/suppliers', ...)->name('suppliers.index');
    Route::get('/deliveries', ...);
    Route::get('/reports', ...)->name('reports.index');
    Route::get('/activity', ...)->name('activity.index');

    // Enkel toegankelijk voor admin
    Route::middleware(['admin'])->group(function () {
        Route::get('/products/...', ...);
        Route::get('/categories', ...);
        Route::get('/warehouses/...', ...);
        Route::get('/locations', ...);
        Route::get('/users', ...);
    });
});
```

---

## Laravel 13 architectuurregels

### Separation of concerns

#### Routes
Doen alleen:
- Endpoint definitie
- Middleware koppelen
- Livewire component aanroepen

#### Livewire componenten
Mogen:
- User input verwerken
- Valideren
- UI-state beheren
- Services aanroepen

Mogen niet:
- Stockmutaties zelf uitvoeren zonder via `StockService` te gaan
- Complexe queryketens bevatten in de `render()` methode
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
- Prijzen: `decimal(10,2)` — nooit `float`

### Foreign keys
- Altijd expliciet met `constrained()` en correcte delete-regels
- StockMovement: product_id en user_id → `restrict`
- DeliveryItem: `cascade` wanneer delivery verwijderd wordt

### Indexes
Verplicht op:
- `sku` (products) — uniek
- `product_id` + `location_id` combinatie (stock — uniek)
- `product_id`, `user_id`, `type`, `created_at` (stock_movements)
- Foreign keys

### Soft deletes
Op: `products`, `categories`, `warehouses`, `locations`, `suppliers`, `deliveries`
Niet op: `stock`, `stock_movements` (immutable audit data), `delivery_items`

### Seeders
Na `php artisan migrate:fresh --seed` moet het systeem demo-klaar zijn:
- 1 admin + 1 warehouse worker (vaste demo-accounts)
- Minstens 2 warehouses met meerdere locaties
- Minstens 15 producten met categorieën
- Stockniveaus per locatie, inclusief een paar producten onder min_stock
- Stockbewegingen historiek
- Minstens 2 leveranciers met leveringen

Demo-accounts:
- Admin: `admin@waretrack.test` / `password`
- Worker: `worker@waretrack.test` / `password`

---

## Policies en Middleware

### Middleware
`EnsureUserIsAdmin` (alias: `admin`):
- Blokkeert niet-admins van admin-only routes
- Geregistreerd in `bootstrap/app.php`

### Policies

`ProductPolicy`:
- `viewAny`, `view`: iedereen die ingelogd is
- `create`, `update`, `delete`: enkel admin

`CategoryPolicy`, `WarehousePolicy`, `LocationPolicy`:
- Zelfde structuur als ProductPolicy

`StockMovementPolicy`:
- `viewAny`, `view`: iedereen die ingelogd is
- `create`: admin én warehouse_worker
- `update`, `delete`: niemand — stockbewegingen zijn immutable

`SupplierPolicy`, `DeliveryPolicy`:
- `viewAny`, `view`: admin én warehouse_worker
- `create`, `update`, `delete`: enkel admin

---

## Stockintegriteitsregels

Dit zijn de meest kritische regels van het systeem:

1. **Nooit stock aanpassen buiten `StockService`** — geen directe `Stock::update()` of `Stock::create()` in Livewire of Controllers
2. **Elke stockmutatie zit in een `DB::transaction()`**
3. **Gebruik `lockForUpdate()`** bij het ophalen van stockrecords binnen een transactie
4. **Gooi `InsufficientStockException`** als een operatie negatieve stock zou veroorzaken
5. **Elke mutatie creëert een `StockMovement`** — deze is na aanmaak nooit wijzigbaar of verwijderbaar
6. **Dispatch `StockMovementRegistered`** na elke succesvolle mutatie (voor low-stock notificaties)

---

## Queues en notificaties

- Queue connection: `database`
- Queue: `notifications` voor `SendLowStockNotification`
- Worker starten: `php artisan queue:work --queue=notifications,default`
- Mail: `MAIL_MAILER=log` in development (emails verschijnen in `storage/logs/laravel.log`)
- Throttle: één notificatie per product per 24 uur (via Laravel Cache)

---

## Testing-regels

Gebruik Pest. Geen PHPUnit-stijl.

### Aanwezige test suites
- Auth: login, logout, rolgebaseerde toegang
- Product CRUD: aanmaken, wijzigen, soft delete, image upload
- Stock: incoming, outgoing, transfer, correction, negatieve stock geblokkeerd
- Low-stock notificaties: event dispatch, queue push, notification logic
- Delivery: verwerking verhoogt stock correct
- Activity log: filtering, audit trail
- Reports: CSV export, filtering
- Warehouse: heatmap data, location CRUD

### Testregels
- Gebruik factories voor alle testdata
- Gebruik `RefreshDatabase`
- Geen hardcoded IDs
- Beschrijvende test namen in snake_case
- Test zowel happy path als edge cases
- Voor gequeued listeners: gebruik `Queue::fake()` + `Queue::assertPushedOn()`
- Voor listener-logica: roep `handle()` rechtstreeks aan in de test

---

## Frontend-regels

### Flux UI
- Gebruik Flux UI components als standaard voor alle UI elementen
- Flux UI free tier — gebruik geen betaalde componenten
- Pas Tailwind utility classes toe voor custom spacing/layout

### Image storage
- Gebruik de `public` disk (`storage/app/public`)
- `php artisan storage:link` bij installatie
- Sla paden relatief op (`products/bestand.jpg`)
- Valideer bij upload: mimes `jpg,jpeg,png,webp`, max `2048` KB

### Dark theme
- De app gebruikt een donker thema (dark mode standaard)
- Gebruik `dark:` Tailwind variants consistent
- Heatmap-visualisatie: inline styles voor dynamische rgba-kleuren (Tailwind purgt dynamische klassen)

### Responsive
- Desktop-first (WMS intern gebruik)
- Minimum: desktop en tablet correct

### Views
- Blade blijft presentatielaag
- Geen datamanipulatie in Blade
- Gebruik Flux UI components en `#[Computed]` properties uit Livewire

---

## Codekwaliteitsregels

### Code stijl
- Laravel Pint als formatter (`./vendor/bin/pint`)
- Voer Pint uit voor elke commit: `./vendor/bin/pint --test` mag geen violations tonen

### Naming
- Duidelijke Engelse namen voor classes, methods en properties
- Consistente WMS-domeinterminologie:
  - `Warehouse` (niet `Storage` of `Depot`)
  - `Location` (niet `Shelf` of `Spot`)
  - `StockMovement` (niet `StockLog` of `Mutation`)
  - `Delivery` (niet `Shipment`)
- Geen afkortingen tenzij standaard (`SKU`, `qty`)

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
docs/xxx → documentatie
```

### Commits (Conventional Commits)
```
feat(stock): implement bulk correction with live diff
feat(warehouse): add heatmap view with stock density coloring
fix(dashboard): correct low stock threshold calculation
refactor(services): extract reporting logic into ReportService
test(stock): add feature tests for negative stock prevention
docs(readme): add installation and seeding instructions
```

### Regels
- Nooit rechtstreeks naar `main` pushen
- Feature branches worden gemerged naar `dev` via pull request
- `.env` nooit committen — wel `.env.example` met lege sensitive values
- `vendor/` en `node_modules/` nooit in repo
- Geen `Co-Authored-By:` lines in commits — enkel de developer als auteur

---

## Definitie van klaar

Een feature is pas klaar als:
- De code compatibel is met Laravel 13 en Livewire 4
- De architectuur klopt (juiste laag voor juiste logica)
- Routes, authorization en validatie correct zitten
- Stockmutaties via `StockService` gaan met transacties
- Er geen evidente N+1 of securityfouten zijn
- Pest tests aanwezig zijn voor de feature
- `./vendor/bin/pint --test` geeft geen violations
- Flux UI correct gebruikt wordt
- De code leesbaar is voor een andere developer

---

## Samenvattende hoofdregel

Bouw WareTrack alsof een senior Laravel developer een intern bedrijfssysteem bouwt dat andere medewerkers dagelijks gebruiken. Stockdata is kritisch — behandel het als financiële data. Elke mutatie is traceerbaar, elke bewerking is veilig, en de architectuur is zo opgezet dat een nieuwe developer de codebase in minder dan een uur begrijpt.

<?php

namespace App\Livewire\Activity;

use App\Enums\StockMovementType;
use App\Livewire\Concerns\WithSorting;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Activity Log: het filterbare venster op de stock_movements audit trail.
 *
 * Deze component schrijft zelf niets — elke rij ontstaat in StockService, binnen
 * dezelfde transactie als de stockmutatie. De #[Url]-attributen spiegelen elke
 * filter naar de querystring: een gefilterde weergave is zo een deelbare URL
 * en overleeft een refresh.
 */
class Index extends Component
{
    use WithPagination;
    use WithSorting;

    /** Kolommen waarop gesorteerd mag worden (whitelist voor orderBy). */
    protected array $sortable = ['type', 'quantity', 'reference', 'created_at'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $user = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    /** Terug naar pagina 1 bij een nieuwe zoekterm, anders kan de gebruiker op een lege pagina landen. */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Reset de paginering wanneer de typefilter wijzigt. */
    public function updatedType(): void
    {
        $this->resetPage();
    }

    /** Reset de paginering wanneer de gebruikersfilter wijzigt. */
    public function updatedUser(): void
    {
        $this->resetPage();
    }

    /** Reset de paginering wanneer de begindatum wijzigt. */
    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    /** Reset de paginering wanneer de einddatum wijzigt. */
    public function updatedTo(): void
    {
        $this->resetPage();
    }

    /**
     * De gefilterde, gepagineerde lijst stockbewegingen — de kern van deze pagina.
     * Elke actieve filter wordt via when() alleen toegevoegd als hij ook ingevuld is.
     */
    #[Computed]
    public function movements()
    {
        // Vijf relaties eager geladen: zonder with() zou elke rij in de tabel vijf
        // extra queries doen (N+1). De zoekterm matcht op productnaam, SKU én referentie.
        return StockMovement::with(['product', 'user', 'location', 'fromLocation', 'toLocation'])
            // De hele zoekconditie (product-match OR referentie-match) in een
            // geneste groep: zonder die groep zou de OR de type-/gebruiker-/
            // datumfilters hieronder omzeilen (AND/OR-precedentie).
            ->when($this->search, function ($q) {
                $q->where(fn ($q) => $q
                    ->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%"))
                    ->orWhere('reference', 'like', "%{$this->search}%"));
            })
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->user, fn ($q) => $q->where('user_id', $this->user))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            // Klikbare kolomkoppen; zonder keuze blijft nieuwste-eerst de default.
            ->tap(fn ($q) => $this->applySort($q))
            ->paginate(25);
    }

    /** Alle gebruikers (enkel id en naam) als opties voor de gebruikersfilter-dropdown. */
    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    /** Alle bewegingstypes uit de enum, zodat de typefilter automatisch meegroeit met nieuwe types. */
    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    /**
     * Tellers per bewegingstype voor de samenvattingskaarten bovenaan.
     * Volgt bewust enkel de datumfilters: de kaarten tonen het totaalbeeld van de
     * gekozen periode, ongeacht zoekterm, type- of gebruikersselectie.
     */
    #[Computed]
    public function stats(): array
    {
        $base = StockMovement::query()
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to));

        // Elke teller kloont de basisquery: where() muteert een builder, dus zonder
        // clone zou elke volgende count de type-conditie van de vorige meeslepen.
        return [
            'total' => (clone $base)->count(),
            'incoming' => (clone $base)->where('type', StockMovementType::Incoming)->count(),
            'outgoing' => (clone $base)->where('type', StockMovementType::Outgoing)->count(),
            'transfers' => (clone $base)->where('type', StockMovementType::Transfer)->count(),
            'corrections' => (clone $base)->where('type', StockMovementType::Correction)->count(),
        ];
    }

    /** Wist alle filters in één klik (terug naar de defaults) en springt naar pagina 1. */
    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'user', 'from', 'to']);
        $this->resetPage();
    }

    /** Rendert de activity-log view; alle data komt lazy binnen via de computed properties. */
    public function render()
    {
        return view('livewire.activity.index');
    }
}

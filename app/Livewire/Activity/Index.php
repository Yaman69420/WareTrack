<?php

namespace App\Livewire\Activity;

use App\Enums\StockMovementType;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedUser(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function movements()
    {
        // Vijf relaties eager geladen: zonder with() zou elke rij in de tabel vijf
        // extra queries doen (N+1). De zoekterm matcht op productnaam, SKU én referentie.
        return StockMovement::with(['product', 'user', 'location', 'fromLocation', 'toLocation'])
            ->when($this->search, function ($q) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"))
                    ->orWhere('reference', 'like', "%{$this->search}%");
            })
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->user, fn ($q) => $q->where('user_id', $this->user))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    #[Computed]
    public function stats(): array
    {
        $base = StockMovement::query()
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to));

        return [
            'total' => (clone $base)->count(),
            'incoming' => (clone $base)->where('type', StockMovementType::Incoming)->count(),
            'outgoing' => (clone $base)->where('type', StockMovementType::Outgoing)->count(),
            'transfers' => (clone $base)->where('type', StockMovementType::Transfer)->count(),
            'corrections' => (clone $base)->where('type', StockMovementType::Correction)->count(),
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'user', 'from', 'to']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.activity.index');
    }
}

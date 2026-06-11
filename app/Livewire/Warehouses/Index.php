<?php

namespace App\Livewire\Warehouses;

use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Beheerscherm voor magazijnen: zoeken, CRUD via een modal en per magazijn
 * het aantal locaties plus de totale voorraad in het overzicht.
 *
 * De voorraadtotalen worden met een SQL-subquery berekend zodat de lijst
 * met één query gevuld blijft, ook bij veel magazijnen en locaties.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Rule('required|string|max:100')]
    public string $name = '';

    #[Rule('required|string|max:100')]
    public string $location = '';

    #[Rule('nullable|string|max:500')]
    public string $description = '';

    /**
     * Terug naar pagina 1 bij een nieuwe zoekterm, anders kan de gebruiker op
     * een lege pagina van het gefilterde resultaat belanden.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        // Subquery i.p.v. een query per rij: de DB telt de voorraad per magazijn
        // in één keer. COALESCE geeft 0 voor magazijnen zonder stock-rijen, en de
        // deleted_at-check sluit soft-deleted locaties expliciet uit (een raw
        // join past de SoftDeletes-scope van Eloquent immers niet zelf toe).
        $totalStockSub = DB::raw('(SELECT COALESCE(SUM(s.quantity), 0) FROM stock s INNER JOIN locations l ON s.location_id = l.id WHERE l.warehouse_id = warehouses.id AND l.deleted_at IS NULL) as total_stock');

        return Warehouse::query()
            ->addSelect(['warehouses.*', $totalStockSub])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('location', 'like', "%{$this->search}%"))
            ->withCount('locations')
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'location', 'description', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Warehouse $warehouse): void
    {
        $this->editingId = $warehouse->id;
        $this->name = $warehouse->name;
        $this->location = $warehouse->location;
        $this->description = $warehouse->description ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Eén save-methode voor create én update; $editingId bepaalt de modus.
     */
    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $warehouse = Warehouse::findOrFail($this->editingId);
            $warehouse->update([
                'name' => $this->name,
                'location' => $this->location,
                'description' => $this->description ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($warehouse)->log('updated');
            Flux::toast(__('Warehouse updated.'), variant: 'success');
        } else {
            $warehouse = Warehouse::create([
                'name' => $this->name,
                'location' => $this->location,
                'description' => $this->description ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($warehouse)->log('created');
            Flux::toast(__('Warehouse created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'location', 'description', 'editingId']);
        // Computed-cache legen zodat de lijst de wijziging meteen toont.
        unset($this->warehouses);
    }

    /**
     * Soft delete: het magazijn verdwijnt uit de lijsten, maar de historiek
     * van bewegingen en audit-records blijft raadpleegbaar.
     */
    public function delete(Warehouse $warehouse): void
    {
        $warehouse->delete();
        activity()->causedBy(auth()->user())->performedOn($warehouse)->log('deleted');
        Flux::toast(__('Warehouse deleted.'), variant: 'success');
        unset($this->warehouses);
    }

    public function render()
    {
        return view('livewire.warehouses.index');
    }
}

<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Globaal beheerscherm voor magazijnlocaties, over alle magazijnen heen
 * (Warehouses\Show beheert dezelfde locaties binnen één magazijn).
 *
 * Bevat de delete-bescherming: een locatie met actieve voorraad mag nooit
 * verdwijnen, anders zou er fysieke stock zonder registratie bestaan.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterWarehouse = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Rule('required|exists:warehouses,id')]
    public ?int $warehouseId = null;

    #[Rule('required|string|max:20')]
    public string $code = '';

    #[Rule('nullable|string|max:100')]
    public string $name = '';

    /**
     * Terug naar pagina 1 zodra een filter of zoekterm wijzigt, anders kan de
     * paginator op een lege pagina van het nieuwe resultaat blijven staan.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Zelfde paginareset bij een wijziging van de magazijnfilter.
     */
    public function updatedFilterWarehouse(): void
    {
        $this->resetPage();
    }

    /**
     * Alle magazijnen, alfabetisch, voor de filter-dropdown en het formulier.
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    /**
     * Doorzoekbare, gefilterde en gepagineerde locatielijst, met magazijn en
     * productaantal per rij.
     */
    #[Computed]
    public function locations()
    {
        return Location::query()
            ->with('warehouse')
            // Geneste groep rond de OR-zoekvoorwaarden, anders omzeilt een
            // code-match de magazijnfilter (AND/OR-precedentie).
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->when($this->filterWarehouse, fn ($q) => $q->where('warehouse_id', $this->filterWarehouse))
            ->withCount('products')
            ->latest()
            ->paginate(10);
    }

    /**
     * Opent de modal in create-modus met een leeg formulier en zonder oude
     * validatiefouten.
     */
    public function openCreate(): void
    {
        $this->reset(['warehouseId', 'code', 'name', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de modal in edit-modus, voorgevuld met de bestaande gegevens.
     */
    public function openEdit(Location $location): void
    {
        $this->editingId = $location->id;
        $this->warehouseId = $location->warehouse_id;
        $this->code = $location->code;
        $this->name = $location->name ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Eén save-methode voor create én update; $editingId bepaalt de modus.
     */
    public function save(): void
    {
        $this->validate();

        $data = [
            'warehouse_id' => $this->warehouseId,
            // Locatiecodes altijd in hoofdletters: zo blijft de notatie uniform
            // ('a-01-2' en 'A-01-2' zijn dezelfde fysieke plek).
            'code' => strtoupper($this->code),
            'name' => $this->name ?: null,
        ];

        if ($this->editingId) {
            $location = Location::findOrFail($this->editingId);
            $location->update($data);
            activity()->causedBy(auth()->user())->performedOn($location)->log('updated');
            Flux::toast(__('Location updated.'), variant: 'success');
        } else {
            $location = Location::create($data);
            activity()->causedBy(auth()->user())->performedOn($location)->log('created');
            Flux::toast(__('Location created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['warehouseId', 'code', 'name', 'editingId']);
        // Computed-cache legen zodat de lijst de wijziging meteen toont.
        unset($this->locations);
    }

    /**
     * Verwijdert een locatie, maar enkel als er geen actieve voorraad ligt.
     * Eerst alles wegboeken (outgoing of transfer), dan pas verwijderen —
     * anders zou voorraad spoorloos uit het systeem verdwijnen.
     */
    public function delete(Location $location): void
    {
        // Enkel rijen met quantity > 0 tellen: een leeg stock-record (alles
        // ooit weggeboekt) mag het verwijderen niet blokkeren.
        $activeStock = $location->stock()->where('quantity', '>', 0)->count();

        if ($activeStock > 0) {
            // Danger-toast met het aantal producten: de gebruiker ziet meteen
            // wat er eerst weggeboekt of verplaatst moet worden.
            Flux::toast(
                __('Cannot delete: :count product(s) still have stock at this location.', ['count' => $activeStock]),
                variant: 'danger'
            );

            return;
        }

        $location->delete();
        activity()->causedBy(auth()->user())->performedOn($location)->log('deleted');
        Flux::toast(__('Location deleted.'), variant: 'success');
        unset($this->locations);
    }

    /**
     * Rendert de locatielijst-view.
     */
    public function render()
    {
        return view('livewire.locations.index');
    }
}

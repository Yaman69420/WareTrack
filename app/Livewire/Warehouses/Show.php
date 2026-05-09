<?php

namespace App\Livewire\Warehouses;

use App\Models\Location;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Warehouse $warehouse;

    // View mode: 'grid' or 'heatmap'
    public string $viewMode = 'grid';

    // Add location modal
    public bool $showModal = false;

    public ?int $editingLocationId = null;

    public string $code = '';

    public string $locationName = '';

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    #[Computed]
    public function locations()
    {
        $totalStockSub = DB::raw('(SELECT COALESCE(SUM(s.quantity), 0) FROM stock s WHERE s.location_id = locations.id) as total_stock');
        $productCountSub = DB::raw('(SELECT COUNT(DISTINCT s.product_id) FROM stock s WHERE s.location_id = locations.id AND s.quantity > 0) as product_count');

        return $this->warehouse->locations()
            ->addSelect(['locations.*', $totalStockSub, $productCountSub])
            ->orderBy('code')
            ->get();
    }

    /**
     * Max stock across all locations in this warehouse (used to normalise heatmap).
     */
    #[Computed]
    public function maxLocationStock(): int
    {
        return (int) $this->locations->max('total_stock') ?: 1;
    }

    #[Computed]
    public function stats(): array
    {
        $locations = $this->locations;

        return [
            'location_count' => $locations->count(),
            'total_stock' => $locations->sum('total_stock'),
            'product_count' => DB::table('stock')
                ->join('locations', 'stock.location_id', '=', 'locations.id')
                ->where('locations.warehouse_id', $this->warehouse->id)
                ->whereNull('locations.deleted_at')
                ->distinct('stock.product_id')
                ->count('stock.product_id'),
        ];
    }

    public function openCreateLocation(): void
    {
        $this->reset(['code', 'locationName', 'editingLocationId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditLocation(Location $location): void
    {
        $this->editingLocationId = $location->id;
        $this->code = $location->code;
        $this->locationName = $location->name ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function saveLocation(): void
    {
        $this->validate([
            'code' => 'required|string|max:20',
            'locationName' => 'nullable|string|max:100',
        ]);

        if ($this->editingLocationId) {
            $loc = Location::findOrFail($this->editingLocationId);
            $loc->update([
                'code' => strtoupper($this->code),
                'name' => $this->locationName ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($loc)->log('updated');
            Flux::toast(__('Location updated.'), variant: 'success');
        } else {
            $loc = $this->warehouse->locations()->create([
                'code' => strtoupper($this->code),
                'name' => $this->locationName ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($loc)->log('created');
            Flux::toast(__('Location created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['code', 'locationName', 'editingLocationId']);
        unset($this->locations, $this->stats, $this->maxLocationStock);
    }

    public function deleteLocation(Location $location): void
    {
        $location->delete();
        activity()->causedBy(auth()->user())->performedOn($location)->log('deleted');
        Flux::toast(__('Location deleted.'), variant: 'success');
        unset($this->locations, $this->stats, $this->maxLocationStock);
    }

    public function render()
    {
        return view('livewire.warehouses.show');
    }
}

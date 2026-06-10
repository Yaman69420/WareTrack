<?php

namespace App\Livewire\Stock;

use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BulkCorrection extends Component
{
    public ?int $warehouseId = null;

    /** @var array<int, string> locationId → new quantity (string so empty field = '') */
    public array $quantities = [];

    public string $notes = '';

    /**
     * Pre-fill the quantities array with current stock values when a warehouse is selected.
     */
    public function updatedWarehouseId(): void
    {
        unset($this->stockLines, $this->changedLines);
        $this->prefillQuantities();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function stockLines()
    {
        if (! $this->warehouseId) {
            return collect();
        }

        return Stock::with(['product.category', 'location'])
            ->whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId))
            ->get()
            ->sortBy([
                ['location.code', 'asc'],
                ['product.name', 'asc'],
            ]);
    }

    public function prefillQuantities(): void
    {
        $this->quantities = $this->stockLines
            ->mapWithKeys(fn ($line) => [$line->id => (string) $line->quantity])
            ->toArray();
    }

    public function mount(): void
    {
        // Pre-fill once if warehouseId already set (e.g. from URL)
        if ($this->warehouseId) {
            $this->prefillQuantities();
        }
    }

    /**
     * Returns only lines whose quantity was actually changed.
     */
    #[Computed]
    public function changedLines()
    {
        return $this->stockLines->filter(function ($line) {
            $newQty = $this->quantities[$line->id] ?? null;

            return $newQty !== null && $newQty !== '' && (int) $newQty !== $line->quantity;
        });
    }

    public function save(StockService $stockService): void
    {
        $this->validate([
            'warehouseId' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string|max:500',
            'quantities' => 'array',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $changed = $this->changedLines;

        if ($changed->isEmpty()) {
            Flux::toast(__('No changes to save.'), variant: 'warning');

            return;
        }

        $user = auth()->user();

        foreach ($changed as $line) {
            $newQty = (int) $this->quantities[$line->id];

            $stockService->adjust(
                $line->product,
                $line->location,
                $newQty,
                $user,
                $this->notes ?: null,
            );
        }

        Flux::toast(
            trans_choice(
                '{1} 1 location corrected.|[2,*] :count locations corrected.',
                $changed->count(),
                ['count' => $changed->count()]
            ),
            variant: 'success'
        );

        $this->notes = '';
        unset($this->stockLines, $this->changedLines);
        $this->prefillQuantities();
    }

    public function render()
    {
        return view('livewire.stock.bulk-correction');
    }
}

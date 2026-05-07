<?php

namespace App\Livewire\Reports;

use App\Enums\StockMovementType;
use App\Models\Warehouse;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $tab = 'low-stock';

    // Stock per location filters
    public ?int $filterWarehouse = null;

    // Movements per period filters
    public string $filterFrom = '';

    public string $filterTo = '';

    public string $filterType = '';

    public function mount(): void
    {
        $this->filterFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    #[Computed]
    public function lowStockProducts()
    {
        return app(ReportService::class)->getLowStockProducts();
    }

    #[Computed]
    public function stockPerLocation()
    {
        return app(ReportService::class)->getStockPerLocation($this->filterWarehouse ?: null);
    }

    #[Computed]
    public function movements()
    {
        $from = $this->filterFrom ? Carbon::parse($this->filterFrom) : now()->startOfMonth();
        $to = $this->filterTo ? Carbon::parse($this->filterTo) : now();

        return app(ReportService::class)->getMovementsForPeriod(
            $from,
            $to,
            $this->filterType ?: null,
        );
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        unset($this->lowStockProducts, $this->stockPerLocation, $this->movements);
    }

    public function updatedFilterWarehouse(): void
    {
        unset($this->stockPerLocation);
    }

    public function updatedFilterFrom(): void
    {
        unset($this->movements);
    }

    public function updatedFilterTo(): void
    {
        unset($this->movements);
    }

    public function updatedFilterType(): void
    {
        unset($this->movements);
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}

<?php

namespace App\Livewire\Reports;

use App\Enums\StockMovementType;
use App\Models\Warehouse;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapportage met drie tabs (low stock, stock per locatie, bewegingen per periode)
 * en CSV-export. Alle data komt uit de read-only ReportService — deze component
 * bevat enkel UI-state (tab, filters) en de vertaling naar een download.
 */
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
        // #[Computed]-properties worden per request gecachet; unset() gooit die cache
        // weg zodat de nieuwe tab met verse data rendert. Zelfde patroon hieronder
        // wanneer een filter wijzigt.
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

    public function exportStockPerLocation(): StreamedResponse
    {
        $lines = app(ReportService::class)->getStockPerLocation($this->filterWarehouse ?: null);

        // streamDownload schrijft de CSV regel per regel rechtstreeks naar de response
        // (php://output) — er wordt nooit een volledig bestand in het geheugen of op
        // schijf opgebouwd, dus ook een groot rapport blijft licht.
        return response()->streamDownload(function () use ($lines) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Warehouse', 'Location Code', 'Location Name', 'Product', 'SKU', 'Category', 'Quantity']);
            foreach ($lines as $line) {
                fputcsv($handle, [
                    $line->location->warehouse->name,
                    $line->location->code,
                    $line->location->name ?? '',
                    $line->product->name,
                    $line->product->sku,
                    $line->product->category?->name ?? '',
                    $line->quantity,
                ]);
            }
            fclose($handle);
        }, 'stock-per-location-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportMovements(): StreamedResponse
    {
        $from = $this->filterFrom ? Carbon::parse($this->filterFrom) : now()->startOfMonth();
        $to = $this->filterTo ? Carbon::parse($this->filterTo) : now();

        $movements = app(ReportService::class)->getMovementsForPeriod(
            $from,
            $to,
            $this->filterType ?: null,
        );

        return response()->streamDownload(function () use ($movements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Product', 'SKU', 'Location', 'Quantity', 'Reference', 'Notes', 'User']);
            foreach ($movements as $m) {
                $location = $m->type->value === 'transfer'
                    ? ($m->fromLocation?->code ?? '?').' → '.($m->toLocation?->code ?? '?')
                    : ($m->location?->code ?? '—');

                fputcsv($handle, [
                    $m->created_at->format('Y-m-d H:i:s'),
                    $m->type->value,
                    $m->product->name,
                    $m->product->sku,
                    $location,
                    $m->quantity,
                    $m->reference ?? '',
                    $m->notes ?? '',
                    $m->user?->name ?? '',
                ]);
            }
            fclose($handle);
        }, 'movements-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}

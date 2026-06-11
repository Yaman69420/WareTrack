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

    /** Zet de periodefilter standaard op begin deze maand tot vandaag — het meest gevraagde rapport. */
    public function mount(): void
    {
        $this->filterFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterTo = now()->format('Y-m-d');
    }

    /** Alle magazijnen, alfabetisch, als opties voor de filter op de tab "stock per locatie". */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    /** Alle bewegingstypes uit de enum, als opties voor de typefilter op de bewegingen-tab. */
    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    /** Producten onder hun minimumvoorraad; de berekening zelf zit volledig in ReportService. */
    #[Computed]
    public function lowStockProducts()
    {
        return app(ReportService::class)->getLowStockProducts();
    }

    /**
     * Voorraadregels per locatie, optioneel beperkt tot één magazijn.
     * De ?: normaliseert een lege selectie (null of 0) naar null = "geen filter".
     */
    #[Computed]
    public function stockPerLocation()
    {
        return app(ReportService::class)->getStockPerLocation($this->filterWarehouse ?: null);
    }

    /**
     * Bewegingen binnen de gekozen periode, optioneel beperkt tot één type.
     * Leeggemaakte datumvelden vallen terug op dezelfde defaults als mount(),
     * zodat het rapport nooit zonder periode draait.
     */
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

    /** Wisselt van tab en invalideert de computed-cache zodat de nieuwe tab verse data toont. */
    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        // #[Computed]-properties worden per request gecachet; unset() gooit die cache
        // weg zodat de nieuwe tab met verse data rendert. Zelfde patroon hieronder
        // wanneer een filter wijzigt.
        unset($this->lowStockProducts, $this->stockPerLocation, $this->movements);
    }

    /** Invalideert het locatierapport wanneer de magazijnfilter wijzigt. */
    public function updatedFilterWarehouse(): void
    {
        unset($this->stockPerLocation);
    }

    /** Invalideert het bewegingenrapport wanneer de begindatum wijzigt. */
    public function updatedFilterFrom(): void
    {
        unset($this->movements);
    }

    /** Invalideert het bewegingenrapport wanneer de einddatum wijzigt. */
    public function updatedFilterTo(): void
    {
        unset($this->movements);
    }

    /** Invalideert het bewegingenrapport wanneer de typefilter wijzigt. */
    public function updatedFilterType(): void
    {
        unset($this->movements);
    }

    /**
     * Exporteert het locatierapport als CSV-download, met dezelfde magazijnfilter
     * als de tab — de gebruiker krijgt exact wat op het scherm staat.
     */
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

    /**
     * Exporteert het bewegingenrapport als CSV-download. Periode en type worden op
     * dezelfde manier genormaliseerd als in movements(), zodat scherm en export
     * gegarandeerd dezelfde dataset bevatten.
     */
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
                // Een transfer heeft geen enkele locatie maar een van→naar-paar; via de
                // nullsafe operator wordt een verwijderde locatie '?' i.p.v. een crash.
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

    /** Rendert de rapportenpagina; $tab bepaalt in de view welke rapporttab zichtbaar is. */
    public function render()
    {
        return view('livewire.reports.index');
    }
}

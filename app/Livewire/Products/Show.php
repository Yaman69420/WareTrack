<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detailpagina van één product: stock per locatie, recente bewegingen en
 * de minimumvoorraad-indicator.
 *
 * Puur leesweergave — alle gegevens komen via computed properties zodat ze
 * per request maar één keer worden opgevraagd, ook al gebruikt de view ze
 * op meerdere plaatsen.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public Product $product;

    /**
     * Ontvangt het product via route model binding en laadt de relaties die
     * de view nodig heeft in één keer mee.
     */
    public function mount(Product $product): void
    {
        // Relaties in één keer mee-laden: de view leest stock, locatie en
        // magazijn per lijn — zonder load() wordt dat een reeks losse queries.
        $this->product = $product->load(['category', 'stock.location.warehouse']);
    }

    /**
     * Stockoverzicht per locatie, gesorteerd op magazijn en dan locatiecode.
     * Sorteren gebeurt in PHP omdat beide sleutels uit geneste relaties komen;
     * voor de paar locaties van één product is dat goedkoper dan een join.
     */
    #[Computed]
    public function stockLines()
    {
        return $this->product->stock()
            ->with('location.warehouse')
            ->get()
            ->sortBy([
                fn ($a, $b) => strcmp($a->location->warehouse->name ?? '', $b->location->warehouse->name ?? ''),
                fn ($a, $b) => strcmp($a->location->code, $b->location->code),
            ]);
    }

    /**
     * Recentste bewegingen van dit product, gecapt op 25: de detailpagina is
     * een snapshot, de volledige historiek staat op de Activity-pagina.
     */
    #[Computed]
    public function movements()
    {
        return StockMovement::where('product_id', $this->product->id)
            ->with(['location.warehouse', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->latest()
            ->limit(25)
            ->get();
    }

    /**
     * Totale voorraad over alle locaties heen, gesommeerd uit de al geladen
     * stock-relatie (geen extra query).
     */
    #[Computed]
    public function totalStock(): int
    {
        return $this->product->stock->sum('quantity');
    }

    /**
     * Onder minimumvoorraad? min_stock = 0 betekent "geen minimum ingesteld"
     * en mag dus nooit een waarschuwing tonen — vandaar de eerste check.
     */
    #[Computed]
    public function isBelowMinStock(): bool
    {
        return $this->product->min_stock > 0 && $this->totalStock < $this->product->min_stock;
    }

    /**
     * Publieke URL van de productafbeelding, of null als er geen geüpload is.
     */
    public function imageUrl(): ?string
    {
        return $this->product->image_path
            ? Storage::url($this->product->image_path)
            : null;
    }

    /**
     * Rendert de detailview; de productnaam dient meteen als paginatitel.
     */
    public function render()
    {
        return view('livewire.products.show', ['title' => $this->product->name]);
    }
}

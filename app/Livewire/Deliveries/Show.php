<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Delivery;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detailpagina én verwerkingsflow van een levering: hier wordt de stock écht verhoogd.
 *
 * De gebruiker geeft per item op hoeveel er effectief binnenkwam; process() boekt dat
 * via de StockService (transactie + audit) en zet de status op Partial of Received.
 * Deelleveringen zijn dus een eersteklas scenario: dezelfde pagina kan meermaals
 * verwerkt worden tot alles binnen is.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public Delivery $delivery;

    // Per delivery_item-id: het aantal dat de gebruiker nú wil ontvangen
    public array $receivedQuantities = [];

    /**
     * Laadt de levering met alle relaties in één keer (route model binding geeft enkel
     * het kale model) en vult de ontvangstvelden vooraf in met de openstaande aantallen.
     */
    public function mount(Delivery $delivery): void
    {
        $this->delivery = $delivery->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);
        $this->initReceivedQuantities();
    }

    /**
     * Vult de invoervelden vooraf in met het nog openstaande aantal per item
     * (besteld min al ontvangen): het meest waarschijnlijke scenario is dat
     * de rest in één keer binnenkomt. max(0, ...) vangt historische data af
     * waarbij er ooit meer ontvangen dan besteld werd.
     */
    private function initReceivedQuantities(): void
    {
        $this->receivedQuantities = $this->delivery->items
            ->mapWithKeys(fn ($item) => [$item->id => max(0, $item->quantity_ordered - $item->quantity_received)])
            ->toArray();
    }

    /**
     * Verwerkt de opgegeven ontvangsten. De spelregels:
     * - aantallen boven het nog openstaande worden gecapt op maxReceivable (met warning-toast),
     *   zodat quantity_received nooit boven quantity_ordered uitkomt;
     * - elke boeking loopt via StockService::registerIncoming, nooit rechtstreeks op de
     *   stock-tabel — daar zitten transactie, lock en audit-record;
     * - status wordt afgeleid uit de items: alles binnen → Received, anders Partial.
     * De StockService komt via method injection binnen: Livewire resolvet die uit de
     * container, zodat de component zelf geen service hoeft te construeren.
     */
    public function process(StockService $stockService): void
    {
        // 'process' is een aparte policy-ability: workers mogen verwerken,
        // ook al mogen ze geen leveringen aanmaken of verwijderen.
        $this->authorize('process', $this->delivery);

        // Idempotentie-guard: een volledig ontvangen levering nogmaals verwerken
        // zou de stock dubbel verhogen.
        if ($this->delivery->status === DeliveryStatus::Received) {
            Flux::toast(__('This delivery has already been fully received.'), variant: 'warning');

            return;
        }

        $this->validate([
            'receivedQuantities.*' => 'required|integer|min:0',
        ]);

        try {
            $cappedItems = [];

            foreach ($this->delivery->items as $item) {
                $qty = (int) ($this->receivedQuantities[$item->id] ?? 0);

                // Items met 0 overslaan: zo kan de gebruiker een deellevering boeken
                // door enkel de geleverde rijen in te vullen.
                if ($qty <= 0) {
                    continue;
                }

                $maxReceivable = $item->quantity_ordered - $item->quantity_received;

                // Cappen i.p.v. weigeren: te veel intikken blokkeert de verwerking niet,
                // we boeken het maximum en melden het achteraf via een warning-toast.
                if ($qty > $maxReceivable) {
                    $cappedItems[] = $item->product->name;
                    $qty = $maxReceivable;
                }

                $stockService->registerIncoming(
                    $item->product,
                    $item->location,
                    $qty,
                    auth()->user(),
                    $this->delivery->reference,
                    "Delivery #{$this->delivery->id}",
                );

                $item->increment('quantity_received', $qty);
            }

            // Eerst herladen: de increments hierboven gebeurden op DB-niveau, de modellen
            // in geheugen lopen achter. De statusbeslissing hieronder moet op verse data.
            $this->delivery->refresh()->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);

            $allReceived = $this->delivery->items->every(
                fn ($item) => $item->quantity_received >= $item->quantity_ordered
            );

            $this->delivery->update([
                'status' => $allReceived ? DeliveryStatus::Received : DeliveryStatus::Partial,
                // received_at markeert de vólledige ontvangst; bij een deellevering
                // blijft een eventueel eerdere waarde ongemoeid.
                'received_at' => $allReceived ? now() : $this->delivery->received_at,
            ]);

            // Tweede refresh zodat de render direct de nieuwe status en aantallen toont
            $this->delivery->refresh()->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);

            activity()->causedBy(auth()->user())->performedOn($this->delivery)->log('processed');

            if (! empty($cappedItems)) {
                Flux::toast(
                    __('Quantity capped to maximum receivable for: ').implode(', ', $cappedItems),
                    variant: 'warning'
                );
            }

            Flux::toast(
                $allReceived ? __('Delivery fully received.') : __('Delivery partially received.'),
                variant: 'success'
            );

            // Velden opnieuw vullen met het resterende saldo, klaar voor een volgende deellevering
            $this->initReceivedQuantities();
        } catch (InsufficientStockException $e) {
            // Domeinfout uit de StockService wordt als toast getoond i.p.v. een 500-pagina;
            // de service heeft zijn transactie dan al teruggerold.
            Flux::toast(__('Stock error: ').$e->getMessage(), variant: 'danger');
        }
    }

    /**
     * Rendert de detail- en verwerkingspagina van de levering.
     */
    public function render()
    {
        return view('livewire.deliveries.show');
    }
}

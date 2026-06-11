<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\StockMovementRegistered;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Centrale service voor álle stockmutaties (incoming, outgoing, transfer, correctie).
 *
 * Elke wijziging aan de stock-tabel loopt via deze klasse — nooit rechtstreeks vanuit
 * een Livewire-component. Zo zit de transactie- en locklogica op één plek en wordt elke
 * mutatie gegarandeerd vastgelegd als StockMovement (audit trail) én als event
 * gedispatcht voor de low-stock-bewaking.
 */
class StockService
{
    /**
     * Registreert binnenkomende stock voor een product op een locatie.
     *
     * Loopt volledig in één transactie met een rij-lock, zodat gelijktijdige mutaties
     * op dezelfde product/locatie-combinatie elkaar nooit kunnen overschrijven.
     */
    public function registerIncoming(
        Product $product,
        Location $location,
        int $quantity,
        User $user,
        ?string $reference = null,
        ?string $notes = null,
    ): StockMovement {
        // Guard clause vóór de transactie: ongeldige input verdient geen lock of
        // DB-rondreis. Hetzelfde patroon keert terug in elke mutatiemethode.
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $location, $quantity, $user, $reference, $notes) {
            // firstOrCreate mag hier wél (anders dan bij outgoing): een eerste levering op
            // een nieuwe product/locatie-combinatie is een normaal scenario. Bestaat de rij,
            // dan lockt lockForUpdate ze; zo niet, dan is de nieuwe rij sowieso exclusief
            // voor deze transactie.
            $stock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );

            $stock->increment('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Incoming,
                'quantity' => $quantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            Log::info('Stock incoming registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            // Het event ontkoppelt de mutatie van haar gevolgen: de low-stock-check hangt
            // als listener aan dit event, de service zelf kent geen notificatielogica.
            StockMovementRegistered::dispatch($product, $movement);

            return $movement;
        });
    }

    /**
     * Registreert uitgaande stock voor een product op een locatie.
     *
     * Kerncontract van de applicatie: voorraad kan nooit negatief worden. De controle
     * daarop gebeurt daarom binnen de transactie, ná het locken van de stock-rij.
     *
     * @throws InsufficientStockException
     */
    public function registerOutgoing(
        Product $product,
        Location $location,
        int $quantity,
        User $user,
        ?string $reference = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $location, $quantity, $user, $reference, $notes) {
            // Géén firstOrCreate zoals bij incoming: een lege rij aanmaken om eruit te
            // leveren heeft geen zin. Bestaat er geen rij, dan is de voorraad gewoon nul.
            $stock = Stock::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->first();

            // De voorraadcheck komt bewust ná de lock: een gelijktijdige uitgifte wacht tot
            // deze transactie commit en rekent daarna met de bijgewerkte stand. Checken vóór
            // de lock zou een race geven waarbij twee requests dezelfde voorraad uitleveren.
            $available = $stock?->quantity ?? 0;

            if ($available < $quantity) {
                Log::warning('Insufficient stock attempt blocked', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'requested' => $quantity,
                    'available' => $available,
                ]);

                throw new InsufficientStockException($quantity, $available);
            }

            $stock->decrement('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Outgoing,
                // Negatief opgeslagen: de som van alle movements per product/locatie blijft
                // zo gelijk aan de actuele stand, en het teken toont meteen de richting.
                'quantity' => -$quantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            Log::info('Stock outgoing registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            StockMovementRegistered::dispatch($product, $movement);

            return $movement;
        });
    }

    /**
     * Verplaatst stock van de ene locatie naar de andere, atomair in één transactie.
     *
     * Decrement op de bron en increment op de bestemming slagen samen of helemaal niet:
     * een rollback halverwege kan dus nooit voorraad doen "verdwijnen".
     *
     * @throws InsufficientStockException
     */
    public function transfer(
        Product $product,
        Location $from,
        Location $to,
        int $quantity,
        User $user,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        // Transfer naar dezelfde locatie zou de stand niet wijzigen maar wél een
        // betekenisloze regel in de audit trail zetten — dus blokkeren.
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('From and to locations must be different.');
        }

        return DB::transaction(function () use ($product, $from, $to, $quantity, $user, $notes) {
            // Zelfde lock-dan-check-patroon als registerOutgoing, hier op de bronlocatie:
            // de bron moet bestaan en genoeg voorraad hebben, dus geen firstOrCreate.
            $fromStock = Stock::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('location_id', $from->id)
                ->first();

            $available = $fromStock?->quantity ?? 0;

            if ($available < $quantity) {
                Log::warning('Insufficient stock for transfer', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'from_location_id' => $from->id,
                    'requested' => $quantity,
                    'available' => $available,
                ]);

                throw new InsufficientStockException($quantity, $available);
            }

            $fromStock->decrement('quantity', $quantity);

            // De bestemming mag wél nieuw zijn: de eerste transfer van een product naar
            // een locatie maakt de stock-rij daar gewoon aan.
            $toStock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $to->id],
                ['quantity' => 0]
            );

            $toStock->increment('quantity', $quantity);

            // Eén movement met from én to, geen apart out/in-paar: een transfer is logisch
            // één handeling en hoort als één regel in de audit. Quantity blijft positief
            // omdat de totale voorraad van het product niet wijzigt.
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Transfer,
                'quantity' => $quantity,
                'notes' => $notes,
            ]);

            Log::info('Stock transfer registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'quantity' => $quantity,
            ]);

            StockMovementRegistered::dispatch($product, $movement);

            return $movement;
        });
    }

    /**
     * Zet de stock op een absolute waarde (telcorrectie na inventarisatie).
     *
     * De gebruiker geeft de getelde waarde in; de service berekent zelf het verschil
     * en legt dát vast als movement, zodat de audit trail sluitend blijft.
     */
    public function adjust(
        Product $product,
        Location $location,
        int $newQuantity,
        User $user,
        ?string $notes = null,
    ): StockMovement {
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('New quantity cannot be negative.');
        }

        return DB::transaction(function () use ($product, $location, $newQuantity, $user, $notes) {
            // firstOrCreate: ook een telling op een combinatie zonder bestaande rij moet
            // geregistreerd kunnen worden (van "geen rij" naar de getelde waarde).
            $stock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );

            // Niet de absolute waarde maar het verschil (kan negatief zijn) gaat de audit
            // in: zo blijft "som van movements = actuele stand" ook na correcties kloppen.
            $diff = $newQuantity - $stock->quantity;
            $stock->update(['quantity' => $newQuantity]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Correction,
                'quantity' => $diff,
                'notes' => $notes,
            ]);

            Log::info('Stock correction registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                // update() heeft het model in geheugen al aangepast; de oude waarde wordt
                // hier teruggerekend voor de logregel.
                'old_quantity' => $stock->quantity + ($diff * -1),
                'new_quantity' => $newQuantity,
                'diff' => $diff,
            ]);

            StockMovementRegistered::dispatch($product, $movement);

            return $movement;
        });
    }

    /**
     * Actuele voorraad voor één product/locatie-combinatie; geen rij betekent nul.
     */
    public function getCurrentStock(Product $product, Location $location): int
    {
        return Stock::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->value('quantity') ?? 0;
    }

    /**
     * Totale voorraad van een product, opgeteld over alle locaties heen.
     */
    public function getTotalStockForProduct(Product $product): int
    {
        return Stock::where('product_id', $product->id)->sum('quantity');
    }
}

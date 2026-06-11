<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * De audit trail van het systeem: één rij per stockmutatie, nooit gewijzigd of
     * verwijderd (afgedwongen via StockMovementPolicy). Bewust géén soft deletes —
     * een logboek dat aangepast kan worden, bewijst niets.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: een product of gebruiker met historiek mag nooit hard
            // verdwijnen — anders verliezen audit-records hun betekenis. (Verwijderen in
            // de app is sowieso een soft delete; dit is het vangnet op databankniveau.)
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Locaties op nullOnDelete: verdwijnt een locatie definitief, dan blijft de
            // beweging zelf bestaan — alleen de locatieverwijzing wordt leeg.
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            // Bron en doel voor transfers; bij andere types blijven deze leeg.
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Gecast naar het StockMovementType-enum in het model.
            $table->string('type');
            // Mét teken: outgoing wordt negatief opgeslagen, zodat de som van alle
            // bewegingen per product/locatie gelijk blijft aan de actuele stand.
            $table->integer('quantity');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

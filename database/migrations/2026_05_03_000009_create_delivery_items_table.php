<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leveringsregels — één rij per product binnen een levering, met doellocatie.
 *
 * Het verschil tussen quantity_ordered en quantity_received maakt deelleveringen
 * mogelijk: de status van de levering (partial/received) wordt hieruit afgeleid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            // Cascade: een regel zonder leveringskop is betekenisloos en mag mee verdwijnen.
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            // Restrict op product en locatie: een leveringsbon is een document — zolang er
            // regels naar verwijzen mag het product of de locatie niet hard verdwijnen.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_ordered');
            // Default 0: bij aanmaak is nog niets ontvangen; ontvangst vult dit later in.
            $table->unsignedInteger('quantity_received')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock — de actuele voorraadstand per product per locatie (één rij per combinatie).
 *
 * Dit is een momentopname, geen historiek: de geschiedenis zit in stock_movements.
 * Daarom geen soft deletes — een verdwenen voorraadrij is gewoon "0 stuks hier".
 * Tabelnaam bewust enkelvoud 'stock' (ontelbaar begrip), het model wijst hier expliciet naar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            // Cascade is hier veilig: de rij is afgeleide data. De audit-historiek blijft
            // bewaard in stock_movements, dat zelf restrictOnDelete gebruikt.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            // Unsigned: negatieve voorraad is fysiek onmogelijk. De databank is zo
            // het laatste vangnet als de voorraadcheck in StockService ooit omzeild wordt.
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            // Composite unique: maximaal één voorraadrij per product/locatie-combinatie.
            // Mutaties kunnen zo veilig increment/decrement doen op die ene rij,
            // en firstOrCreate kan nooit stille duplicaten aanmaken.
            $table->unique(['product_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};

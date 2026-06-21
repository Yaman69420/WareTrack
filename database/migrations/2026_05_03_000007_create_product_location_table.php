<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot voor de many-to-many tussen producten en locaties ("dit product ligt/lag hier").
 *
 * Verschilt van de stock-tabel: stock zegt hoevéél er ligt, deze pivot enkel dát er
 * een koppeling is. Pure koppeltabel, dus geen eigen id en geen timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_location', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            // Composite primary key i.p.v. surrogate id: dwingt op DB-niveau af dat
            // dezelfde koppeling geen twee keer kan bestaan, en bespaart een overbodige kolom.
            $table->primary(['product_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_location');
    }
};

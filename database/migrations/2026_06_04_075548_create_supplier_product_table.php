<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: welke producten levert welke leverancier. Bepaalt de productkeuze bij
     * het aanmaken van een levering. Let op de tabelnaam: bewust 'supplier_product'
     * — afwijkend van Laravels alfabetische conventie ('product_supplier'), dus de
     * BelongsToMany-relaties geven de naam expliciet mee.
     */
    public function up(): void
    {
        Schema::create('supplier_product', function (Blueprint $table) {
            // cascadeOnDelete aan beide kanten: verdwijnt de leverancier of het product
            // definitief, dan heeft de koppeling geen bestaansreden meer.
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // De combinatie ís de identiteit: composite primary key i.p.v. een aparte
            // id-kolom, geen timestamps — een koppeling bestaat of bestaat niet.
            $table->primary(['supplier_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_product');
    }
};

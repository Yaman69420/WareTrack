<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Producten — de kern van het WMS; alles (stock, leveringen, bewegingen) verwijst hiernaar.
 *
 * Soft deletes zijn essentieel: een product met historiek mag nooit hard verdwijnen,
 * anders breken de audit trail (stock_movements) en oude leveringsbonnen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete: een categorie is metadata, geen vereiste. Verdwijnt de
            // categorie hard, dan blijft het product bestaan zonder categorie i.p.v. mee te sneuvelen.
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            // SKU is de functionele sleutel waarop magazijniers zoeken en scannen;
            // uniek op DB-niveau zodat dubbele artikelcodes onmogelijk zijn, ook bij imports.
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            // Drempel voor de lagevoorraad-melding op het dashboard; unsigned want
            // een negatieve minimumvoorraad is conceptueel onzin.
            $table->unsignedInteger('min_stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

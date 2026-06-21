<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Productcategorieën — eenvoudige lookup-tabel waar producten optioneel naar verwijzen.
 *
 * Soft deletes: een categorie kan historisch nog aan producten en rapporten hangen.
 * Verwijderen archiveert ze dus, zodat bestaande data leesbaar blijft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Magazijnen — de top van de hiërarchie magazijn > locatie > stock.
 *
 * Soft deletes: een magazijn verdwijnt nooit hard, want stockbewegingen en
 * leveringen uit het verleden moeten erop kunnen blijven verwijzen in rapporten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

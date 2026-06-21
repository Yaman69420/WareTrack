<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Locaties (rekken/zones) binnen een magazijn — het fijnste niveau waarop stock wordt bijgehouden.
 *
 * Elke locatie hoort bij exact één magazijn; de unieke code geldt daarom
 * per magazijn en niet globaal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            // Cascade: een locatie zonder magazijn is betekenisloos, dus ze verdwijnt mee.
            // Magazijnen gebruiken soft deletes, dus deze cascade vuurt enkel bij een harde delete.
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite unique: code 'A1' mag in elk magazijn bestaan, maar binnen
            // één magazijn slechts één keer. Een globale unique op 'code' zou te streng zijn.
            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

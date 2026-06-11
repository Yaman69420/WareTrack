<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leveringen — de kop van een inkomende zending; de regels staan in delivery_items.
 *
 * Soft deletes: een geannuleerde levering blijft zichtbaar in de historiek,
 * want de gekoppelde stockbewegingen verwijzen naar haar referentie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            // Restrict: de aanmaker van een levering moet traceerbaar blijven.
            // Een user met leveringen op zijn naam kan dus niet hard verwijderd worden.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // String i.p.v. DB-enum: de DeliveryStatus-enum in PHP bewaakt de waarden,
            // zodat een nieuwe status geen schema-wijziging vraagt.
            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // Nullable: blijft leeg zolang de levering 'pending' is; wordt pas gezet bij ontvangst.
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};

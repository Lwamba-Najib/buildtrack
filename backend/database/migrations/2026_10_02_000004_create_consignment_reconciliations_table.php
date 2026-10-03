<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_item_id')->constrained()->cascadeOnDelete();

            $table->integer('quantity_sold');
            $table->integer('quantity_returned');
            $table->integer('quantity_lost');
            $table->integer('cash_collected');

            $table->date('reconciled_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_reconciliations');
    }
};

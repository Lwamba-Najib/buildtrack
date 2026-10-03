<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->foreignId('measurement_id')->constrained();
            $table->string('batch_number');

            $table->integer('unit_price'); // price the agent sells at
            $table->integer('quantity_issued');

            // Running totals, updated as reconciliations come in over multiple visits
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_returned')->default(0);
            $table->integer('quantity_lost')->default(0); // unaccounted / damaged, explicitly tracked

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
    }
};

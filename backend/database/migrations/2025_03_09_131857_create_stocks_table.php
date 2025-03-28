<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('measurement_id')->constrained('measurements')->cascadeOnDelete();
            $table->string('batch_number');
            $table->integer('quantity');
            $table->integer('unit_price');
            $table->integer('total_cost');
            $table->integer('sale_price');
            $table->integer('min_stock_level');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('stock_date')->default(now()); // Default to current date
            $table->enum('environment', ['PRODUCTION', 'TEST', 'DEVELOPMENT']);
            $table->unsignedBigInteger('created_by')->nullable(); // Define column without constraint
            $table->unsignedBigInteger('updated_by')->nullable(); // Define column without constraint
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['batch_number', 'product_id', 'brand_id', 'measurement_id', 'unit_price', 'sale_price'],'stock_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};

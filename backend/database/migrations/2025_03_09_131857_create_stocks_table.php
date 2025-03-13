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

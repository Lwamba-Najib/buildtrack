<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('batch_number',32)->unique();
            $table->integer('total_amount');
            $table->integer('discount')->default(0);
            $table->string('payment_method')->default('CASH');
            $table->unsignedBigInteger('created_by')->nullable(); // Define column without constraint
            $table->unsignedBigInteger('updated_by')->nullable(); // Define column without constraint
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

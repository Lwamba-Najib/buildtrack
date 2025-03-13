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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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
        Schema::dropIfExists('measurements');
    }
};

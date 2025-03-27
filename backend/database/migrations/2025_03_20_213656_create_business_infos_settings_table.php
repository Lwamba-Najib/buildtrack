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
        Schema::create('business_info_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('business_reg_number')->unique();
            $table->string('business_tin')->unique();
            $table->string('business_slogan');
            $table->string('business_address');
            $table->string('business_email')->unique();
            $table->string('business_contact')->unique();
            $table->string('business_website');
            $table->string('business_legal_disclaimer');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_info_settings');
    }
};

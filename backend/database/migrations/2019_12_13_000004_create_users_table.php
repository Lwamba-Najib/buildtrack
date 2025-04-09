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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_number',32)->unique();
            $table->string('name');
            $table->string('gender');
            $table->string('nin')->unique();
            $table->string('country',55)->default('Uganda');
            $table->string('code',5)->default('+256');
            $table->string('phone_number')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedBigInteger('role_id')->nullable(); // Define column without constraint
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('last_failed_attempt')->nullable();
            $table->timestamp('lockout_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_locked')->default(false); // New column to track account lock status
            $table->enum('environment', ['PRODUCTION', 'TEST', 'DEVELOPMENT']);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('session_id')->nullable();
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
        Schema::dropIfExists('users');
    }
};

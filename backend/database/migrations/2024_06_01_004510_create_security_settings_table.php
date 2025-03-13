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
        Schema::create('security_settings', function (Blueprint $table) {
            $table->id();
            $table->string('security_settings_2fa')->default('No'); // 2FA option
            $table->string('security_settings_lowercase')->default('No'); // Lowercase option
            $table->string('security_settings_uppercase')->default('No'); // Uppercase option
            $table->string('security_settings_numbers')->default('No'); // Numbers option
            $table->string('security_settings_symbols')->default('No'); // Symbols option
            $table->bigInteger('security_settings_length')->default(8); // Minimum password length
            $table->bigInteger('security_settings_expiry')->default(6); // Password expiry in months
            $table->bigInteger('dormant_account_expiry')->default(90); // Dormant account expiry in days
            $table->bigInteger('security_settings_login_attempt')->default(5); // Password login attempts before lock
            $table->bigInteger('security_settings_history_counts')->default(5); // Password history counts
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
        Schema::dropIfExists('security_settings');
    }
};

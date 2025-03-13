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
        Schema::table('clients', function (Blueprint $table) {
            // Add foreign key for created_by
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');  // or use 'cascade' if needed

            // Add foreign key for updated_by
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');  // or use 'cascade' if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};


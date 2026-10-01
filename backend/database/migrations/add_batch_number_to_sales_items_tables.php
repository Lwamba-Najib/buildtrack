<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('measurement_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });
    }
};
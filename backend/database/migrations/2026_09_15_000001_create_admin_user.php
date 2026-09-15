<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Check if user already exists
        $exists = DB::table('users')->where('email', 'admin@example.com')->exists();
        
        if (!$exists) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "Admin user created successfully!\n";
        } else {
            echo " Admin user already exists.\n";
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@example.com')->delete();
    }
};
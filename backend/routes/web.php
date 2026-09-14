<?php

use Illuminate\Support\Facades\Route;



// Catch-all route to serve the Vue.js Single Page Application
Route::get('/{any}', function () {
    $path = public_path('index.html');
    
    if (file_exists($path)) {
        return response()->file($path);
    }

    abort(404);
})->where('any', '^(?!api\/).*'); 

// TEMPORARY ROUTE TO CREATE ADMIN USER - DELETE THIS AFTER USE
Route::get('/setup-admin', function () {
    // Check if user already exists to avoid errors
    $existingUser = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if ($existingUser) {
        return 'Admin user already exists!';
    }

    $user = new \App\Models\User();
    $user->name = 'Admin';
    $user->email = 'admin@example.com';
    $user->password = bcrypt('password');
    $user->save();

    return 'Success! Admin user created. You can now delete this route.';
});
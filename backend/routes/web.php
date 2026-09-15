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


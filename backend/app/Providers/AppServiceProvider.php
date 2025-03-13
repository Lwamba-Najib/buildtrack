<?php

namespace App\Providers;

use App\Services\OTPService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\Paginator as PaginationPaginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OTPService::class, function ($app) {
            return new OTPService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PaginationPaginator::useBootstrapFour();
    }
}

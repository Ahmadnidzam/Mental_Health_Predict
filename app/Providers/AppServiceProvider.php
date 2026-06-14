<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // App pakai Bootstrap 5 (CDN), bukan Tailwind — paksa paginator
        // pakai markup Bootstrap agar tombol prev/next & ikon rapi.
        Paginator::useBootstrapFive();
    }
}

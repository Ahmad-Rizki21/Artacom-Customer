<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MapboxServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Publikasikan konfigurasi Mapbox
        $this->mergeConfigFrom(__DIR__ . '/../../config/mapbox.php', 'mapbox');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publikasikan konfigurasi
        $this->publishes([
            __DIR__ . '/../../config/mapbox.php' => config_path('mapbox.php'),
        ], 'mapbox-config');
    }
}
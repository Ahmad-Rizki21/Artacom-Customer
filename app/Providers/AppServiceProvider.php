<?php

namespace App\Providers;

use App\Providers\Filament\AlfaPanelProvider;
use App\Providers\Filament\FtthPanelProvider;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Livewire\Livewire;
use App\Livewire\TicketTimeline;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(AlfaPanelProvider::class);
        $this->app->register(FtthPanelProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
            date_default_timezone_set(config('app.timezone'));
            Livewire::component('ticket-timeline', TicketTimeline::class);

    }
}

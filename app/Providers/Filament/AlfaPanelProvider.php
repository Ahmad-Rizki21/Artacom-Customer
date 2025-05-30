<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use App\Filament\AlfaLawson\Widgets\AlfaLawsonDCMapWidget;
use App\Filament\AlfaLawson\Widgets\MonthlyTicketChart;
use App\Filament\AlfaLawson\Widgets\StatsAlfaLawsonRemoteOverview;
use Filament\Facades\Filament;

use App\Filament\AlfaLawson\Widgets\DCMapWidget;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;

class AlfaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('alfa')
            ->path('alfa')
            ->login()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            

            // ->brandLogo(fn () => view('components.brand-alfa-logo'))

            ->brandLogo(asset('images/Logo Light.png'))
            ->darkModeBrandLogo(asset('images/Logo Dark.png'))
            ->brandLogoHeight(fn () => \Illuminate\Support\Facades\Auth::check() ? '3.5rem' : '7rem')
            ->renderHook(
                'panels::auth.login.before-heading',
                fn () => '<style>.filament-login-page .filament-brand { margin-bottom: 1rem; } .filament-login-page .filament-form { margin-top: 0.5rem; }</style>'
            )
            ->favicon(asset('images/favicon-opened-svgrepo-com.svg'))
            

            // ->renderHook(
            //     'panels::auth.login.before-heading',
            //     fn
            //      () => view('components.login-branding')
            // )

            ->resources([
            config('filament-logger.activity_resource')
            ])

            
           
           
            ->navigationGroups([
                'Panel Switcher',
                'Support',
                'Network Management'
            ])
            ->navigationItems([
                // Dashboard
                NavigationItem::make()
                    ->label('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url('/alfa')
                    ->isActiveWhen(fn() => request()->is('alfa'))
                    ->sort(-2),
    
                // Panel Switcher Group
                NavigationItem::make()
                    ->label('Panel Switcher')
                    ->icon('heroicon-o-squares-2x2')
                    ->url('/alfa')
                    ->sort(-1)
                    ->group('Panel Switcher')
                    ->childItems([
                        NavigationItem::make()
                            ->label('SDWAN SERVICE')
                            ->url('/alfa')
                            ->icon('heroicon-o-check-circle')
                            ->isActiveWhen(fn() => request()->is('alfa*')),
                        NavigationItem::make()
                        ->label('FTTH CUSTOMER')
                        ->url('http://192.168.200.120:8001')
                        ->icon('heroicon-o-arrow-right-circle'),
                        //  NavigationItem::make()
                        // ->label('BSI CUSTOMER')
                        // ->url('/ftth')
                        // ->icon('heroicon-o-arrow-right-circle'),
                    ]),
            ])

            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverResources(in: app_path('Filament/AlfaLawson/Resources'), for: 'App\\Filament\\AlfaLawson\\Resources')

            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                // Pages\UserResource\Pages\ListUsers::class,
            ])
            ->discoverWidgets(in: app_path('Filament/AlfaLawson/Widgets'), for: 'App\\Filament\\AlfaLawson\\Widgets')
            ->widgets([
                // Widgets\AlfaLawsonDCMapWidget::class,
                AlfaLawsonDCMapWidget::class,
                StatsAlfaLawsonRemoteOverview::class,
                MonthlyTicketChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
             ->databaseNotifications()
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn () => '
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
                '
            )
            ->plugins([
                
                EasyFooterPlugin::make()
                    ->withBorder()
                    ->withLogo(
                        'https://ajnusa.com/images/artacom.png',
                        'https://ajnusa.com/'
                    )
                    ->withLinks([
                        ['title' => 'Ahmad Rizki', 'url' => 'https://www.instagram.com/amad.dyk/'],
                    ])
                    ->withLoadTime('This page loaded in'),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentBackgroundsPlugin::make()
                ->showAttribution(false),
                FilamentApexChartsPlugin::make(),
                FilamentEditProfilePlugin::make()
        
            ]);
            
    }
}
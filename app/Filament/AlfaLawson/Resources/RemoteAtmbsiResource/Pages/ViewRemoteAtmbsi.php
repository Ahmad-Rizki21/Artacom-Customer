<?php

namespace App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource\Pages;

use App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class ViewRemoteAtmbsi extends ViewRecord
{
    protected static string $resource = RemoteAtmbsiResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('📍 Site Information')
                    ->description('Details about the site location and ownership')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('Site_ID')
                                    ->label('Site ID')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->copyable()
                                    ->copyMessage('Site ID copied!')
                                    ->copyMessageDuration(1500)
                                    ->icon('heroicon-o-hashtag')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('primary')
                                    ->badge(),

                                TextEntry::make('Site_Name')
                                    ->label('Site Name')
                                    ->weight(FontWeight::SemiBold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->icon('heroicon-o-building-storefront')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('success'),

                                TextEntry::make('Customer')
                                    ->label('Customer / Brand')
                                    ->badge()
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color(fn (string $state): string => match (strtoupper($state)) {
                                        'BSI' => 'success',
                                        'OTHER' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match (strtoupper($state)) {
                                        'BSI' => 'heroicon-o-banknotes',
                                        'OTHER' => 'heroicon-o-question-mark-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('Branch')
                                    ->label('Branch')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-building-office')
                                    ->iconPosition(IconPosition::Before),
                            ]),
                    ])
                    ->compact()
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-900 border-l-4 border-blue-500 dark:border-blue-400 rounded-r-lg px-6 py-4 mb-6 text-gray-900 dark:text-gray-100',
                    ]),

                Section::make('🌐 Network Configuration')
                    ->description('Network connectivity and configuration details')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('IP_Address')
                                    ->label('IP Address')
                                    ->copyable()
                                    ->copyMessage('IP Address copied!')
                                    ->copyMessageDuration(1500)
                                    ->url(fn (string $state): string => "http://{$state}:8090", true)
                                    ->color('primary')
                                    ->icon('heroicon-o-globe-alt')
                                    ->iconPosition(IconPosition::Before)
                                    ->badge()
                                    ->tooltip('Click to access device interface')
                                    ->extraAttributes(['class' => 'font-mono text-gray-900 dark:text-gray-100']),

                                TextEntry::make('Vlan')
                                    ->label('VLAN ID')
                                    ->badge()
                                    ->color('amber')
                                    ->icon('heroicon-o-squares-2x2')
                                    ->iconPosition(IconPosition::Before)
                                    ->tooltip('Virtual LAN configuration')
                                    ->prefix('VLAN-')
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),

                                TextEntry::make('Link')
                                    ->label('Connection Type')
                                    ->badge()
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color(fn (string $state): string => match (strtoupper($state)) {
                                        'FO-GSM' => 'success',
                                        'SINGLE-GSM' => 'info',
                                        'DUAL-GSM' => 'warning',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match (strtoupper($state)) {
                                        'FO-GSM' => 'heroicon-o-signal',
                                        'SINGLE-GSM' => 'heroicon-o-device-phone-mobile',
                                        'DUAL-GSM' => 'heroicon-o-arrows-right-left',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->iconPosition(IconPosition::Before)
                                    ->tooltip(fn (string $state): string => match (strtoupper($state)) {
                                        'FO-GSM' => 'Fiber Optic with GSM backup',
                                        'SINGLE-GSM' => 'Single GSM connection',
                                        'DUAL-GSM' => 'Dual GSM connection for redundancy',
                                        default => 'Unknown connection type',
                                    })
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                            ]),
                    ])
                    ->compact()
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-900 border-l-4 border-green-500 dark:border-green-400 rounded-r-lg px-6 py-4 mb-6 text-gray-900 dark:text-gray-100',
                    ]),

                Section::make('🔌 Fiber Optic Infrastructure')
                    ->description('Fiber optic connection details')
                    ->icon('heroicon-o-server')
                    ->visible(fn ($record) => str_contains(strtoupper($record->Link ?? ''), 'FO') || str_contains(strtoupper($record->Link ?? ''), 'FIBER'))
                    ->schema(function ($record) {
                        $foDetails = $record->fos()->first();

                        if (!$foDetails) {
                            return [
                                TextEntry::make('no_fo_data')
                                    ->label('Status')
                                    ->default('No fiber optic data available')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->color('warning')
                                    ->badge()
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                            ];
                        }

                        return [
                            Fieldset::make('Connection Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextEntry::make('fo.CID')
                                                ->label('Connection ID (CID)')
                                                ->default($foDetails->CID ?? 'Not Available')
                                                ->icon('heroicon-o-identification')
                                                ->iconPosition(IconPosition::Before)
                                                ->color('info')
                                                ->copyable()
                                                ->copyMessage('CID copied!')
                                                ->badge()
                                                ->tooltip('Unique connection identifier')
                                                ->extraAttributes(['class' => 'font-mono text-gray-900 dark:text-gray-100']),

                                            TextEntry::make('fo.Provider')
                                                ->label('Service Provider')
                                                ->default($foDetails->Provider ?? 'Unknown Provider')
                                                ->icon('heroicon-o-building-office-2')
                                                ->iconPosition(IconPosition::Before)
                                                ->color('success')
                                                ->badge()
                                                ->weight(FontWeight::SemiBold)
                                                ->tooltip('Fiber optic service provider')
                                                ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                                        ]),

                                    TextEntry::make('fo.Register_Name')
                                        ->label('Registered Account Name')
                                        ->default($foDetails->Register_Name ?? 'Not Registered')
                                        ->icon('heroicon-o-user-circle')
                                        ->iconPosition(IconPosition::Before)
                                        ->color('primary')
                                        ->weight(FontWeight::Medium)
                                        ->tooltip('Account name registered with the service provider')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                                ])
                                ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-gray-900 dark:text-gray-100']),
                        ];
                    })
                    ->compact()
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-900 border-l-4 border-purple-500 dark:border-purple-400 rounded-r-lg px-4 py-3 mb-6 text-gray-900 dark:text-gray-100',
                    ]),

                Section::make('📱 SIM Card Information')
                    ->description('SIM card details for GSM connections')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->visible(fn ($record) => str_contains(strtoupper($record->Link ?? ''), 'GSM'))
                    ->schema(function ($record) {
                        $simCards = $record->simcards()->get();

                        if ($simCards->isEmpty()) {
                            return [
                                TextEntry::make('no_sim_data')
                                    ->label('Status')
                                    ->default('No SIM card data available')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->color('warning')
                                    ->badge()
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                            ];
                        }

                        return [
                            RepeatableEntry::make('simcards')
                                ->schema([
                                    Fieldset::make('SIM Card Details')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextEntry::make('Sim_Number')
                                                        ->label('SIM Number')
                                                        ->icon('heroicon-o-identification')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->color('primary')
                                                        ->weight(FontWeight::Bold)
                                                        ->copyable()
                                                        ->copyMessage('SIM Number copied!')
                                                        ->badge()
                                                        ->extraAttributes(['class' => 'font-mono text-gray-900 dark:text-gray-100']),

                                                    TextEntry::make('Provider')
                                                        ->label('Provider')
                                                        ->icon('heroicon-o-building-office-2')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->color(fn (string $state): string => match (strtoupper($state)) {
                                                            'TELKOMSEL' => 'danger',
                                                            'INDOSAT' => 'warning',
                                                            'XL' => 'info',
                                                            'TRI' => 'success',
                                                            default => 'gray',
                                                        })
                                                        ->badge()
                                                        ->weight(FontWeight::SemiBold)
                                                        ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),

                                                    TextEntry::make('Status')
                                                        ->label('Status')
                                                        ->icon(fn (string $state): string => match (strtoupper($state)) {
                                                            'ACTIVE' => 'heroicon-o-check-circle',
                                                            'INACTIVE' => 'heroicon-o-x-circle',
                                                            'PENDING' => 'heroicon-o-clock',
                                                            default => 'heroicon-o-question-mark-circle',
                                                        })
                                                        ->iconPosition(IconPosition::Before)
                                                        ->color(fn (string $state): string => match (strtoupper($state)) {
                                                            'ACTIVE' => 'success',
                                                            'INACTIVE' => 'danger',
                                                            'PENDING' => 'warning',
                                                            default => 'gray',
                                                        })
                                                        ->badge()
                                                        ->weight(FontWeight::Bold)
                                                        ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                                                ]),

                                            Grid::make(2)
                                                ->schema([
                                                    TextEntry::make('SN_Card')
                                                        ->label('Serial Number')
                                                        ->icon('heroicon-o-qr-code')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->color('gray')
                                                        ->copyable()
                                                        ->copyMessage('Serial Number copied!')
                                                        ->extraAttributes(['class' => 'font-mono text-gray-900 dark:text-gray-100'])
                                                        ->placeholder('Not Available'),

                                                    TextEntry::make('Informasi_Tambahan')
                                                        ->label('Additional Information')
                                                        ->icon('heroicon-o-information-circle')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->color('info')
                                                        ->placeholder('No additional information')
                                                        ->tooltip('Extra notes or information about this SIM card')
                                                        ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                                                ])
                                                ->columnSpanFull(),
                                        ])
                                        ->extraAttributes([
                                            'class' => 'bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100',
                                        ]),
                                ])
                                ->contained(false),
                        ];
                    })
                    ->compact()
                    ->collapsible()
                    ->collapsed(fn ($record) => !str_contains(strtoupper($record->Link ?? ''), 'DUAL-GSM'))
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-900 border-l-4 border-orange-500 dark:border-orange-400 rounded-r-lg px-4 py-3 mb-6 text-gray-900 dark:text-gray-100',
                    ]),

                Section::make('ℹ️ System Information')
                    ->description('Additional system and operational details')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar-days')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray')
                                    ->tooltip('When this record was created')
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray')
                                    ->tooltip('When this record was last modified')
                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100']),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-900 border-l-4 border-gray-400 dark:border-gray-500 rounded-r-lg px-4 py-3 text-gray-900 dark:text-gray-100',
                    ]),
            ])
            ->extraAttributes([
                'class' => 'max-w-7xl mx-auto p-4 w-full text-gray-900 dark:text-gray-100',
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tambahkan header actions jika perlu
        ];
    }
}

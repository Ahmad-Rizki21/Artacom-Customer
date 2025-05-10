<?php

namespace App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;

use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;

class ViewTableRemote extends ViewRecord
{
    protected static string $resource = TableRemoteResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Site Information')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('Site_ID')
                            ->label('Site ID')
                            ->weight(FontWeight::Bold)
                            ->copyable()
                            ->icon('heroicon-o-building-library'),

                        TextEntry::make('Nama_Toko')
                            ->label('Nama Toko')
                            ->icon('heroicon-o-shopping-bag'),

                        TextEntry::make('DC')
                            ->label('Distribution Center')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-map-pin'),

                        TextEntry::make('Customer')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ALFAMART' => 'success',
                                'LAWSON' => 'info',
                                default => 'gray',
                            })
                            ->icon('heroicon-o-user'),
                    ]),

                Section::make('Network Configuration')
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('IP_Address')
                            ->label('IP Address')
                            ->copyable()
                            ->url(fn (string $state): string => "http://{$state}:8090", true)
                            ->color('primary')
                            ->icon('heroicon-o-globe-alt'),

                        TextEntry::make('Vlan')
                            ->label('VLAN')
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-o-squares-2x2'),

                        TextEntry::make('Link')
                            ->label('Connection Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'FO-GSM' => 'success',
                                'SINGLE-GSM' => 'info',
                                'DUAL-GSM' => 'warning',
                                default => 'gray',
                            })
                            ->icon('heroicon-o-signal'),
                    ]),
            ]);
    }
}
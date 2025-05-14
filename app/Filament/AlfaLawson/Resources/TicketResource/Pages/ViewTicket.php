<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section with Ticket Info
                Section::make('Ticket Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextEntry::make('No_Ticket')
                                    ->label('Ticket Number')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->copyable()
                                    ->copyMessage('Ticket number copied!')
                                    ->copyMessageDuration(1500)
                                    ->icon('heroicon-o-ticket'),

                                TextEntry::make('Status')
                                    ->badge()
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color(fn (string $state): string => match ($state) {
                                        'OPEN' => 'warning',
                                        'PENDING' => 'info',
                                        'CLOSED' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'OPEN' => 'heroicon-o-exclamation-circle',
                                        'PENDING' => 'heroicon-o-clock',
                                        'CLOSED' => 'heroicon-o-check-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                // Customer & Site Information
                Section::make('Customer Information')
                    ->description('Details about the customer and site')
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('Customer')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ALFAMART' => 'success',
                                'LAWSON' => 'info',
                                default => 'gray',
                            })
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('Site_ID')
                            ->label('Remote Site')
                            ->copyable()
                            ->copyMessage('Site ID copied!')
                            ->copyMessageDuration(1500)
                            ->icon('heroicon-o-computer-desktop')
                            ->color('info'),
                            
                        TextEntry::make('Open_Level')
                            ->label('Priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Level 1' => 'success',
                                'Level 2' => 'warning',
                                'Level 3' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('Catagory')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Internal' => 'info',
                                'Komplain' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                // Problem Information
                Section::make('Problem Details')
                    ->description('Detailed information about the reported issue')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('Problem')
                            ->label('Issue Description')
                            ->columnSpanFull()
                            ->html()
                            ->prose()
                            ->weight(FontWeight::Medium),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('Reported_By')
                                    ->label('Reported By')
                                    ->icon('heroicon-m-user')
                                    ->weight(FontWeight::Medium),

                                TextEntry::make('pic')
                                    ->label('PIC')
                                    ->icon('heroicon-m-user-circle')
                                    ->weight(FontWeight::Medium),

                                TextEntry::make('tlp_pic')
                                    ->label('Contact Number')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->weight(FontWeight::Medium),
                            ]),

                        TextEntry::make('Problem_Summary')
                            ->label('Analysis Summary')
                            ->columnSpanFull()
                            ->html()
                            ->prose()
                            ->hidden(fn ($state) => empty($state)),

                        TextEntry::make('Classification')
                            ->columnSpanFull()
                            ->html()
                            ->prose()
                            ->hidden(fn ($state) => empty($state)),
                    ]),

                // Timeline Information
                Section::make('Ticket Progress')
                    ->description('Timeline and progress information')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('ticket_duration')
                                    ->label('Time Elapsed')
                                    ->state(function ($record) {
                                        if (!$record->Open_Time) return 'Not started';
                                        
                                        $start = Carbon::parse($record->Open_Time);
                                        $end = match ($record->Status) {
                                            'CLOSED' => Carbon::parse($record->Closed_Time),
                                            'PENDING' => Carbon::parse($record->Pending_Start),
                                            default => now(),
                                        };
                                        
                                        return $start->diffForHumans($end, true);
                                    })
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-m-clock'),

                                TextEntry::make('Open_Time')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('openedBy.name')
                                    ->label('Created By')
                                    ->icon('heroicon-m-user'),
                            ]),

                        // Pending Information
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('Pending_Start')
                                    ->label('Pending Since')
                                    ->dateTime()
                                    ->icon('heroicon-m-pause-circle')
                                    ->hidden(fn ($record) => $record->Status !== 'PENDING'),

                                TextEntry::make('Pending_Reason')
                                    ->label('Pending Reason')
                                    ->html()
                                    ->prose()
                                    ->hidden(fn ($record) => $record->Status !== 'PENDING'),
                            ]),

                        // Resolution Information
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('Closed_Time')
                                    ->label('Resolved At')
                                    ->dateTime()
                                    ->icon('heroicon-m-check-circle')
                                    ->hidden(fn ($record) => $record->Status !== 'CLOSED'),

                                TextEntry::make('Action_Summry')
                                    ->label('Action Summary')
                                    ->html()
                                    ->prose()
                                    ->hidden(fn ($record) => $record->Status !== 'CLOSED'),
                            ]),
                    ]),
            ]);
    }
}
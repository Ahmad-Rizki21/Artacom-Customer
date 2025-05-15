<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TicketResource\Pages;
use App\Models\AlfaLawson\Ticket;
use App\Models\AlfaLawson\TableRemote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Ticket';
    protected static ?string $modelLabel = 'Tickets';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $isCreate = $form->getOperation() === 'create';

        if ($isCreate) {
            return $form->schema([
                Grid::make(12)->schema([
                    Section::make('Ticket Information')
                        ->description('Basic ticket information')
                        ->icon('heroicon-o-ticket')
                        ->collapsible()
                        ->columnSpan(8)
                        ->schema([
                            Grid::make(2)->schema([
                                Forms\Components\TextInput::make('No_Ticket')
                                    ->label('No Ticket')
                                    ->default(fn () => Ticket::generateTicketNumber())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->prefixIcon('heroicon-m-ticket'),

                                Forms\Components\Select::make('Customer')
                                    ->options(TableRemote::pluck('Customer', 'Customer'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->prefixIcon('heroicon-m-building-office')
                                    ->native(false),

                                Forms\Components\Select::make('Site_ID')
                                    ->label('Remote')
                                    ->options(function (Forms\Get $get) {
                                        $customer = $get('Customer');
                                        return TableRemote::when($customer, fn ($query) => $query->where('Customer', $customer))
                                            ->get()
                                            ->mapWithKeys(fn ($remote) => [
                                                $remote->Site_ID => "{$remote->Site_ID} - {$remote->Nama_Toko} - {$remote->IP_Address}",
                                            ]);
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->prefixIcon('heroicon-m-computer-desktop')
                                    ->native(false),

                                Forms\Components\Select::make('Catagory')
                                    ->options([
                                        'Internal' => 'Internal',
                                        'Komplain' => 'Komplain',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-tag'),
                            ]),
                        ]),

                    Section::make('Problem Details')
                        ->description('Detailed information about the problem')
                        ->icon('heroicon-o-exclamation-circle')
                        ->collapsible()
                        ->columnSpan(4)
                        ->schema([
                            Forms\Components\Textarea::make('Problem')
                                ->label('Problem Description')
                                ->required()
                                ->maxLength(255)
                                ->rows(3),

                            Forms\Components\TextInput::make('Reported_By')
                                ->label('Reported By')
                                ->placeholder('Name of reporter (optional)')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-user'),

                            Forms\Components\TextInput::make('pic')
                                ->label('PIC Name')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-user-circle'),

                            Forms\Components\TextInput::make('tlp_pic')
                                ->label('PIC Phone')
                                ->maxLength(20)
                                ->prefixIcon('heroicon-m-phone'),
                        ]),
                ]),
            ]);
        }

        // Form Edit
        return $form->schema([
            Grid::make(12)->schema([
                Section::make('Ticket Management')
                    ->columnSpan(12)
                    ->schema([
                        Tabs::make('Ticket Management')
                            ->tabs([
                                Tabs\Tab::make('Basic Information')
                                    ->icon('heroicon-o-information-circle')
                                    ->badge(fn ($record) => $record->Status)
                                    ->badgeColor(fn ($record) => match ($record->Status) {
                                        'OPEN' => 'warning',
                                        'PENDING' => 'info',
                                        'CLOSED' => 'success',
                                        default => 'secondary',
                                    })
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('No_Ticket')
                                                ->disabled()
                                                ->dehydrated()
                                                ->prefixIcon('heroicon-m-ticket'),

                                            Forms\Components\Select::make('Customer')
                                                ->options(TableRemote::pluck('Customer', 'Customer'))
                                                ->required()
                                                ->searchable()
                                                ->prefixIcon('heroicon-m-building-office')
                                                ->native(false),

                                            Forms\Components\Select::make('Site_ID')
                                                ->label('Remote')
                                                ->options(function (Forms\Get $get) {
                                                    $customer = $get('Customer');
                                                    return TableRemote::when($customer, fn ($q) => $q->where('Customer', $customer))
                                                        ->get()
                                                        ->mapWithKeys(fn ($remote) => [
                                                            $remote->Site_ID => "{$remote->Site_ID} - {$remote->Nama_Toko} - {$remote->IP_Address}",
                                                        ]);
                                                })
                                                ->required()
                                                ->searchable()
                                                ->prefixIcon('heroicon-m-computer-desktop')
                                                ->native(false),

                                            Forms\Components\Select::make('Status')
                                                ->options([
                                                    'OPEN' => 'OPEN',
                                                    'PENDING' => 'PENDING',
                                                    'CLOSED' => 'CLOSED',
                                                ])
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                                                    if ($state === 'PENDING') {
                                                        $set('Pending_Start', now());
                                                        if (!$record || $record->Status !== 'PENDING') {
                                                            $set('Pending_Reason', null);
                                                        }
                                                    } elseif ($state === 'CLOSED') {
                                                        $set('Closed_Time', now());
                                                    } elseif ($state === 'OPEN' && $record && $record->Status === 'PENDING') {
                                                        $set('Pending_Stop', now());
                                                    }
                                                })
                                                ->disabled(fn ($record) => $record?->Status === 'CLOSED')
                                                ->dehydrated()
                                                ->prefix(fn ($state) => match ($state) {
                                                    'OPEN' => '🟡',
                                                    'PENDING' => '🔵',
                                                    'CLOSED' => '🟢',
                                                    default => '⚪',
                                                }),

                                            Forms\Components\Select::make('Open_Level')
                                                ->options([
                                                    'Level 1' => 'Level 1',
                                                    'Level 2' => 'Level 2',
                                                    'Level 3' => 'Level 3',
                                                ])
                                                ->required()
                                                ->native(false)
                                                ->prefixIcon('heroicon-m-adjustments-vertical'),

                                            Forms\Components\Select::make('Catagory')
                                                ->options([
                                                    'Internal' => 'Internal',
                                                    'Komplain' => 'Komplain',
                                                ])
                                                ->required()
                                                ->native(false)
                                                ->prefixIcon('heroicon-m-tag'),
                                        ]),
                                    ]),

                                Tabs\Tab::make('Problem Information')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\Textarea::make('Problem')
                                                ->columnSpan(2)
                                                ->required()
                                                ->rows(3),

                                            Forms\Components\TextInput::make('Reported_By')
                                                ->prefixIcon('heroicon-m-user'),

                                            Forms\Components\TextInput::make('pic')
                                                ->label('PIC Name')
                                                ->prefixIcon('heroicon-m-user-circle'),

                                            Forms\Components\TextInput::make('tlp_pic')
                                                ->label('PIC Phone')
                                                ->prefixIcon('heroicon-m-phone'),

                                            Forms\Components\Textarea::make('Problem_Summry')
                                                ->label('Problem Description')
                                                ->columnSpan(2)
                                                ->rows(3),

                                            Forms\Components\Textarea::make('Classification')
                                                ->columnSpan(2)
                                                ->rows(3),
                                        ]),
                                    ]),

                                Tabs\Tab::make('Status Updates')
    ->icon('heroicon-o-clock')
    ->schema([
        Grid::make(2)->schema([
            // Gunakan Livewire component dengan properti record yang dievaluasi
            \Filament\Forms\Components\Livewire::make(\App\Filament\Components\TicketTimer::class, function ($livewire) {
                // Akses record dari konteks Livewire atau form
                $record = $livewire->getRecord();
                return ['record' => $record];
            })
                ->columnSpan(2),

            // Open Time Information
            Group::make([
                Forms\Components\DateTimePicker::make('Open_Time')
                    ->label('Opened At')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('openedBy.name')
                    ->label('Opened By')
                    ->formatStateUsing(fn ($record) => $record?->openedBy?->name ?? 'Unknown User')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2),

            // Pending Information
            Group::make([
                Forms\Components\DateTimePicker::make('Pending_Start')
                    ->label('Pending Since')
                    ->disabled()
                    ->hidden(fn (Forms\Get $get) => $get('Status') !== 'PENDING' && $get('Pending_Start') === null),

                Forms\Components\Textarea::make('Pending_Reason')
                    ->label('Reason for Pending')
                    ->required(fn (Forms\Get $get) => $get('Status') === 'PENDING')
                    ->hidden(fn (Forms\Get $get) => $get('Status') !== 'PENDING' && $get('Pending_Reason') === null)
                    ->live()
                    ->debounce(500)
                    ->rows(2)
                    ->placeholder('Explain why this ticket is being set to pending')
                    ->columnSpanFull(),
            ])->visible(fn (Forms\Get $get) => $get('Status') === 'PENDING' || $get('Pending_Reason') !== null || $get('Pending_Start') !== null),

            // Closing Information
            Group::make([
                Forms\Components\Textarea::make('Action_Summry')
                    ->label('Action Summary')
                    ->required(fn (Forms\Get $get) => $get('Status') === 'CLOSED')
                    ->visible(fn (Forms\Get $get) => $get('Status') === 'CLOSED')
                    ->rows(3)
                    ->minLength(10)
                    ->placeholder('Describe the actions taken to resolve this ticket')
                    ->helperText('Required before closing the ticket. Minimum 10 characters.')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('Closed_Time')
                    ->label('Closed At')
                    ->disabled()
                    ->visible(fn (Forms\Get $get) => $get('Status') === 'CLOSED'),
            ])->visible(fn (Forms\Get $get) => $get('Status') === 'CLOSED'),
        ]),
    ]),

                                Tabs\Tab::make('Closed')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Forms\Components\Textarea::make('Action_Summry')
                                            ->label('Action Summary')
                                            ->required(fn (Forms\Get $get) => $get('Status') === 'CLOSED')
                                            ->rows(5),

                                        Forms\Components\Placeholder::make('ticket_duration')
                                            ->label('Ticket Duration')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return '-';
                                                }

                                                $start = Carbon::parse($record->Open_Time);
                                                $end = match ($record->Status) {
                                                    'CLOSED' => Carbon::parse($record->Closed_Time),
                                                    'PENDING' => Carbon::parse($record->Pending_Start),
                                                    default => now(),
                                                };

                                                return $start->diffForHumans($end, true);
                                            }),
                                    ]),
                            ]),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No_Ticket')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Site_ID')
                    ->label('Remote')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Problem')
                    ->label('Problem Description')
                    ->searchable()
                    ->wrap()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Reported_By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'PENDING' => 'info',
                        'CLOSED' => 'success',
                        default => 'secondary',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Open_Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('openedBy.name')
                    ->label('Opened By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('Created_By')
                    ->getStateUsing(fn ($record) => optional($record->openedBy)->name)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_today')
                    ->label('Created Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', Carbon::today())),

                SelectFilter::make('Status')
                    ->options([
                        'OPEN' => 'OPEN',
                        'PENDING' => 'PENDING',
                        'CLOSED' => 'CLOSED',
                    ])
                    ->multiple(),

                SelectFilter::make('Catagory')
                    ->options([
                        'Internal' => 'Internal',
                        'Komplain' => 'Komplain',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('Status', 'OPEN')->count();
    }
}
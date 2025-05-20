<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\Exports\TicketExport;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Ticket';
    protected static ?string $navigationGroup = 'Support';
    protected static ?string $modelLabel = 'Tickets';
    protected static ?int $navigationSort = 0;

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
                                ->prefixIcon('heroicon-m-user')
                                ->default('-'),
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
                                                        $set('Pending_End', now());
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
                                            ->prefixIcon('heroicon-m-user')
                                            ->formatStateUsing(fn ($state) => $state ?? '-')
                                            ->default('-'),

                                            Forms\Components\TextInput::make('pic')
                                                ->label('PIC Name')
                                                ->prefixIcon('heroicon-m-user-circle'),

                                            Forms\Components\TextInput::make('tlp_pic')
                                                ->label('PIC Phone')
                                                ->prefixIcon('heroicon-m-phone'),

                                            Forms\Components\Textarea::make('Problem_Summary')
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
                                                    ->hidden(fn (Forms\Get $get) => $get('Status') !== 'PENDING'),

                                                Forms\Components\Textarea::make('Pending_Reason')
                                                    ->label('Reason for Pending')
                                                    ->required(fn (Forms\Get $get) => $get('Status') === 'PENDING')
                                                    ->visible(fn (Forms\Get $get) => $get('Status') === 'PENDING')
                                                    ->rows(2),
                                            ])->visible(fn (Forms\Get $get) => $get('Status') === 'PENDING'),

                                            // Closing Information
                                            Group::make([
                                                Forms\Components\Textarea::make('Action_Summry')
                                                    ->label('Action Summary')
                                                    ->required(fn (Forms\Get $get) => $get('Status') === 'CLOSED')
                                                    ->visible(fn (Forms\Get $get) => $get('Status') === 'CLOSED')
                                                    ->rows(3)
                                                    ->disabled(fn ($record) => $record?->Status !== 'CLOSED'),

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

                // Add Closed_Time column to make closed ticket info more visible
                TextColumn::make('Closed_Time')
                    ->label('Closed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not Closed')
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
                SelectFilter::make('Status')
                    ->options([
                        'OPEN' => 'OPEN',
                        'PENDING' => 'PENDING',
                        'CLOSED' => 'CLOSED',
                    ])
                    ->multiple(),

                // Add Date Range Filter (Dari Tanggal & Sampai Tanggal)
                Filter::make('periode')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Dari Tanggal')
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->maxDate(now())
                                    ->placeholder('Pilih Tanggal'),
                                    
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Sampai Tanggal')
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->maxDate(now())
                                    ->placeholder('Pilih Tanggal'),
                            ])
                            ->columns(2),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['start_date'])->format('d M Y');
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['end_date'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                // Add Problem Type Filter
                
                SelectFilter::make('Catagory')
                    ->options([
                        'Internal' => 'Internal',
                        'Komplain' => 'Komplain',
                    ])
                    ->multiple(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('closeTicket')
                    ->label('CLOSED')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->Status !== 'CLOSED')
                    ->form([
                        Forms\Components\Textarea::make('action_summary')
                            ->label('Action Summary')
                            ->required()
                            ->rows(3)
                            ->helperText('Provide a summary of the actions taken.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'Status' => 'CLOSED',
                            'Action_Summry' => $data['action_summary'],
                            'Closed_Time' => now(),
                            'Closed_By' => Auth::id(),
                        ]);
                    })
                    ->modalHeading('Close Ticket')
                    ->modalSubmitActionLabel('Confirm Close')
                    ->modalWidth('lg'),
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


    public static function getNavigationBadgeColor(): ?string
    {
        $openCount = static::getModel()::where('Status', 'OPEN')->count();
        $pendingCount = static::getModel()::where('Status', 'PENDING')->count();

        if ($openCount > 0) {
            return 'danger';
        }
        if ($pendingCount > 0) {
            return 'warning';
        }
        return 'success';
    }
}
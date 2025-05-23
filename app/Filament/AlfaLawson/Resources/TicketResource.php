<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\Exports\TicketExport;
use App\Filament\AlfaLawson\Resources\TicketResource\Pages;
use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use App\Models\AlfaLawson\Ticket;
use App\Models\AlfaLawson\TableRemote;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $navigationGroup = 'Support';
    protected static ?string $modelLabel = 'Ticket';
    protected static ?string $pluralModelLabel = 'Tickets';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        // Shared schema for both create and edit modes
        $mainSchema = fn (string $operation) => [
            // Main Information Section
            Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('No_Ticket')
                                ->label('Ticket Number')
                                ->default(fn () => Ticket::generateTicketNumber())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->prefixIcon('heroicon-m-hashtag')
                                ->extraInputAttributes([
                                    'class' => 'bg-gray-100 font-mono text-sm border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            Select::make('Open_Level')
                                ->label('Priority Level')
                                ->options([
                                    'Level 1' => 'Level 1 - Low',
                                    'Level 2' => 'Level 2 - Medium', 
                                    'Level 3' => 'Level 3 - High',
                                ])
                                ->required()
                                ->default('Level 1')
                                ->prefixIcon('heroicon-m-exclamation-triangle')
                                ->native(false)
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),
                        ])
                        ->visible(fn () => $operation === 'create'),

                    Grid::make(2)
                        ->schema([
                            Select::make('Customer')
                                ->label('Customer')
                                ->options(TableRemote::pluck('Customer', 'Customer'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live(debounce: 500)
                                ->prefixIcon('heroicon-m-building-office-2')
                                ->native(false)
                                ->placeholder('Select a customer')
                                ->helperText('Select the customer associated with this ticket')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            Select::make('Site_ID')
                                ->label('Remote Site')
                                ->options(function (Forms\Get $get) {
                                    $customer = $get('Customer');
                                    if (!$customer) {
                                        return [];
                                    }
                                    return TableRemote::where('Customer', $customer)
                                        ->get()
                                        ->mapWithKeys(fn ($remote) => [
                                            $remote->Site_ID => "{$remote->Site_ID} - {$remote->Nama_Toko}",
                                        ]);
                                })
                                ->required()
                                ->searchable()
                                ->live()
                                ->prefixIcon('heroicon-m-computer-desktop')
                                ->native(false)
                                ->placeholder('Select a site')
                                ->helperText('Choose a site after selecting a customer')
                                ->disabled(fn (Forms\Get $get): bool => empty($get('Customer')))
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            Select::make('Catagory')
                                ->label('Category')
                                ->options([
                                    'Internal' => 'Internal Issue',
                                    'Komplain' => 'Customer Complaint',
                                ])
                                ->required()
                                ->default('Internal')
                                ->native(false)
                                ->prefixIcon('heroicon-m-tag')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1)
                                ->visible(fn () => $operation === 'create'),

                            Select::make('Status')
                                ->options([
                                    'OPEN' => 'Open',
                                    'CLOSED' => 'Closed',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, ?Model $record) {
                                    if ($state === 'CLOSED') {
                                        $set('Closed_Time', $record?->Closed_Time ?? now());
                                    } elseif ($state === 'OPEN') {
                                        $set('Closed_Time', null);
                                        $set('Action_Summry', null);
                                    }
                                })
                                ->disabled(fn (?Model $record) => $record?->Status === 'CLOSED')
                                ->dehydrated()
                                ->native(false)
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1)
                                ->visible(fn () => $operation === 'edit'),
                        ]),
                ])
                ->heading(fn () => $operation === 'create' ? 'Create New Ticket' : 'Ticket Details')
                ->description(fn () => $operation === 'create' ? 'Enter the details to create a new support ticket' : 'View and update ticket information')
                ->icon('heroicon-m-ticket')
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6',
                ])
                ->id('basic-info'),

            // Problem Details Section
            Section::make('Problem Details')
                ->schema([
                    Textarea::make('Problem')
                        ->label('Problem Description')
                        ->required()
                        ->rows(5)
                        ->maxLength(500)
                        ->placeholder('Describe the issue in detail...')
                        ->helperText('Provide a clear and detailed description of the problem (max 500 characters)')
                        ->extraAttributes([
                            'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg resize-none',
                        ])
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('Reported_By')
                                ->label('Reported By')
                                ->placeholder('Enter the name of the person reporting the issue')
                                ->helperText('Optional: Name of the person who reported the issue')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-user')
                                ->default('-')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            TextInput::make('pic')
                                ->label('PIC Name')
                                ->placeholder('Enter the name of the person in charge')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-user-circle')
                                ->helperText('Contact person at the site')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            TextInput::make('tlp_pic')
                                ->label('PIC Phone')
                                ->placeholder('+62 xxx-xxxx-xxxx')
                                ->maxLength(20)
                                ->prefixIcon('heroicon-m-phone')
                                ->helperText('Contact number for the PIC')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                                ])
                                ->columnSpan(1),

                            Textarea::make('Problem_Summary')
                                ->label('Internal Summary')
                                ->rows(3)
                                ->placeholder('Enter internal analysis of the problem...')
                                ->helperText('For internal use - technical summary of the issue')
                                ->extraAttributes([
                                    'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg resize-none',
                                ])
                                ->columnSpan(1)
                                ->visible(fn () => $operation === 'edit'),
                        ]),
                ])
                ->description('Provide detailed information about the reported issue')
                ->icon('heroicon-m-exclamation-circle')
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6',
                ])
                ->id('problem-details'),

            // Status Management Section (Edit Mode)
            Section::make('Status Management')
                ->schema([
                    Group::make([
                        Textarea::make('Action_Summry')
                            ->label('Resolution Summary')
                            ->required()
                            ->rows(5)
                            ->placeholder('Detail the steps taken to resolve the issue...')
                            ->helperText('Provide comprehensive details of the resolution')
                            ->extraAttributes([
                                'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg resize-none',
                            ])
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('Closed_Time')
                                    ->label('Closed At')
                                    ->content(fn (?Model $record): string => 
                                        $record?->Closed_Time 
                                            ? Carbon::parse($record->Closed_Time)->format('d/m/Y H:i:s') 
                                            : 'Will be set automatically'
                                    )
                                    ->extraAttributes([
                                        'class' => 'text-gray-600',
                                    ]),

                                Placeholder::make('closedBy.name')
                                    ->label('Closed By')
                                    ->content(fn (?Model $record): string => 
                                        $record?->closedBy?->name ?? Auth::user()?->name ?? 'Current User'
                                    )
                                    ->extraAttributes([
                                        'class' => 'text-gray-600',
                                    ]),
                            ]),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('Status') === 'CLOSED'),
                ])
                ->description('Manage ticket status and resolution details')
                ->icon('heroicon-m-clock')
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6',
                ])
                ->id('status-management')
                ->visible(fn () => $operation === 'edit'),

            // Ticket Timeline Section (Edit Mode)
            Section::make('Ticket Timeline')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('Open_Time')
                                ->label('Opened')
                                ->content(fn (?Model $record): string => 
                                    $record?->Open_Time 
                                        ? Carbon::parse($record->Open_Time)->format('d/m/Y H:i') 
                                        : '-'
                                )
                                ->extraAttributes([
                                    'class' => 'text-gray-600',
                                ]),

                            Placeholder::make('openedBy.name')
                                ->label('Opened By')
                                ->content(fn (?Model $record): string => 
                                    $record?->openedBy?->name ?? 'Unknown'
                                )
                                ->extraAttributes([
                                    'class' => 'text-gray-600',
                                ]),

                        
                        ]),
                ])
                ->description('Track the lifecycle and timing of the ticket')
                ->icon('heroicon-m-calendar-days')
                ->collapsible()
                ->collapsed()
                ->persistCollapsed()
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6',
                ])
                ->id('ticket-timeline')
                ->visible(fn () => $operation === 'edit'),
        ];

        // Sidebar schema for edit mode
        $sidebarSchema = [
            Section::make('Quick Actions')
                ->schema([
                    Actions::make([
                        Action::make('viewSite')
                            ->label('View Site Details')
                            ->icon('heroicon-m-map-pin')
                            ->color('info')
                             ->url(function (?Model $record) {
                                if (!$record || !$record->Site_ID) {
                                    return null;
                                }
                                // Find the TableRemote record by Site_ID
                                $site = TableRemote::where('Site_ID', $record->Site_ID)->first();
                                if (!$site) {
                                    return null;
                                }
                                // Pass the TableRemote model instance directly to getUrl
                                return TableRemoteResource::getUrl('view', ['record' => $site]);
                            })
                            ->extraAttributes([
                                'class' => 'w-full bg-info-600 hover:bg-info-700 text-white font-medium py-2 px-4 rounded-lg transition-colors',
                            ])
                            ->visible(fn (?Model $record) => (bool) $record),

                        Action::make('contactPIC')
                            ->label('Contact PIC')
                            ->icon('heroicon-m-phone')
                            ->color('success')
                            ->action(fn (?Model $record) => null) // Add contact logic
                            ->extraAttributes([
                                'class' => 'w-full bg-success-600 hover:bg-success-700 text-white font-medium py-2 px-4 rounded-lg transition-colors mt-2',
                            ])
                            ->visible(fn (?Model $record) => (bool) $record?->tlp_pic),
                    ])
                    ->fullWidth(),
                ])
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6',
                ])
                ->compact(),

            Section::make('System Info')
                ->schema([
                    Placeholder::make('created_info')
                        ->label('Created')
                        ->content(fn (?Model $record): string => 
                            $record?->created_at 
                                ? $record->created_at->format('d/m/Y H:i') 
                                : 'New ticket'
                        )
                        ->extraAttributes([
                            'class' => 'text-gray-600',
                        ]),

                    Placeholder::make('updated_info')
                        ->label('Last Updated')
                        ->content(fn (?Model $record): string => 
                            $record?->updated_at 
                                ? $record->updated_at->diffForHumans() 
                                : 'Not saved yet'
                        )
                        ->extraAttributes([
                            'class' => 'text-gray-600',
                        ]),
                ])
                ->extraAttributes([
                    'class' => 'bg-white shadow-sm border border-gray-200 rounded-lg p-6',
                ])
                ->compact(),
        ];

        return $form->schema([
            Group::make()
                ->schema($mainSchema('create'))
                ->columnSpan(['lg' => 12])
                ->visible(fn (string $operation): bool => $operation === 'create'),

            Group::make()
                ->schema($mainSchema('edit'))
                ->columnSpan(['lg' => 8])
                ->visible(fn (string $operation): bool => $operation === 'edit'),

            Group::make()
                ->schema($sidebarSchema)
                ->columnSpan(['lg' => 4])
                ->visible(fn (string $operation): bool => $operation === 'edit'),
        ])
        ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No_Ticket')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),
                    
                TextColumn::make('Customer')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                    
                TextColumn::make('Site_ID')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                TextColumn::make('Problem')
                    ->label('Problem')
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                TextColumn::make('Reported_By')
                    ->label('Reported By')
                    ->searchable()
                    ->placeholder('-'),
                    
                TextColumn::make('pic')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('-'),
                    
                TextColumn::make('tlp_pic')
                    ->label('PIC Phone')
                    ->searchable()
                    ->placeholder('-'),
                    
                TextColumn::make('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'CLOSED' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('Open_Level')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Level 3' => 'danger',
                        'Level 2' => 'warning',
                        'Level 1' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('Open_Time')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record): string => 
                        $record->Open_Time ? Carbon::parse($record->Open_Time)->format('d/m/Y') : ''
                    ),
                    
                TextColumn::make('openedBy.name')
                    ->label('Opened By')
                    ->searchable()
                    ->placeholder('Unknown'),
                    
                TextColumn::make('Closed_Time')
                    ->label('Closed')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record): string => 
                        $record->Closed_Time ? Carbon::parse($record->Closed_Time)->format('d/m/Y') : ''
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('closedBy.name')
                    ->label('Closed By')
                    ->searchable()
                    ->placeholder('Not closed')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // TextColumn::make('ticket_duration')
                //     ->label('Duration')
                //     ->getStateUsing(function ($record) {
                //         if (!$record->Open_Time) {
                //             return '-';
                //         }
                //         $start = Carbon::parse($record->Open_Time);
                //         $end = $record->Closed_Time ? Carbon::parse($record->Closed_Time) : now();
                //         $diffInSeconds = $start->diffInSeconds($end);
                //         return sprintf(
                //             '%02d:%02d:%02d',
                //             floor($diffInSeconds / 3600),
                //             floor(($diffInSeconds % 3600) / 60),
                //             $diffInSeconds % 60
                //         );
                //     })
                //     ->sortable(query: fn (Builder $query, string $direction) => 
                //         $query->orderByRaw("TIMESTAMPDIFF(SECOND, Open_Time, COALESCE(Closed_Time, NOW())) {$direction}")
                //     ),
            ])
            ->defaultSort('Open_Time', 'desc')
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
                    ->label('Close')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->Status !== 'CLOSED')
                    ->form([
                        Textarea::make('action_summary')
                            ->label('Resolution Summary')
                            ->required()
                            ->rows(4)
                            ->placeholder('Describe how the issue was resolved...')
                            ->helperText('Provide detailed resolution steps')
                            ->extraAttributes([
                                'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg resize-none',
                            ]),
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
                    ->modalDescription('Mark this ticket as resolved')
                    ->modalSubmitActionLabel('Close Ticket')
                    ->modalWidth('lg'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No tickets found')
            ->emptyStateDescription('Create your first ticket to get started')
            ->emptyStateIcon('heroicon-o-ticket');
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
        $openCount = static::getModel()::where('Status', 'OPEN')->count();
        
        return $openCount > 0 ? (string) $openCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $openCount = static::getModel()::where('Status', 'OPEN')->count();

        if ($openCount > 0) {
            return 'danger';
        }
        return 'success';
    }
}
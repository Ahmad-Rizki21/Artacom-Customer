<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TableFoResource\Pages;
use App\Models\AlfaLawson\TableFo;
use App\Models\AlfaLawson\TableRemote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Exports\TableFoExporter;
use App\Filament\Imports\TableFoImporter;

class TableFoResource extends Resource
{
    protected static ?string $model = TableFo::class;
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Fiber Optic';
    protected static ?string $pluralModelLabel = 'Fiber Optic Connections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Section::make('Fiber Optic Connection Details')
                            ->description('Manage fiber optic connection information for specific locations.')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\TextInput::make('CID')
                                    ->label('Connection ID')
                                    ->required()
                                    ->maxLength(32)
                                    ->placeholder('Enter Connection ID')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->prefixIconColor('primary')
                                    ->autofocus()
                                    ->reactive()
                                    ->helperText('Unique identifier for the fiber optic connection.')
                                    ->validationMessages([
                                        'required' => 'Connection ID is required.',
                                        'max' => 'Connection ID cannot exceed 32 characters.',
                                    ])
                                    ->extraAttributes(['class' => 'text-lg'])
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('Provider')
                                            ->label('Provider')
                                            ->required()
                                            ->placeholder('Enter Provider')
                                            ->prefixIcon('heroicon-o-building-office')
                                            ->prefixIconColor('primary')
                                            ->helperText('E.g., ICON+, Telkom, Lintasarta.')
                                            ->validationMessages([
                                                'required' => 'Provider is required.',
                                            ])
                                            ->extraAttributes(['class' => 'text-lg']),

                                        Forms\Components\TextInput::make('Register_Name')
                                            ->label('Register Name')
                                            ->maxLength(32)
                                            ->placeholder('Enter Register Name')
                                            ->prefixIcon('heroicon-o-user')
                                            ->prefixIconColor('primary')
                                            ->helperText('Name associated with the connection.')
                                            ->validationMessages([
                                                'max' => 'Register Name cannot exceed 32 characters.',
                                            ])
                                            ->extraAttributes(['class' => 'text-lg']),
                                            
                                    ])
                                    ->columns([
                                        'sm' => 1,
                                        'lg' => 2,
                                    ]),

                                Forms\Components\Select::make('Site_ID')
                                    ->label('Location')
                                    ->relationship(
                                        'remote',
                                        'Site_ID',
                                        fn (Builder $query) => $query->orderBy('DC')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (TableRemote $record) =>
                                        "[{$record->DC}] {$record->Site_ID} - {$record->Nama_Toko}"
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-building-storefront')
                                    ->prefixIconColor('primary')
                                    ->searchPrompt('Search for a location...')
                                    ->noSearchResultsMessage('No locations found.')
                                    ->loadingMessage('Loading locations...')
                                    ->helperText('Select the store location for this connection.')
                                    ->validationMessages([
                                        'required' => 'Location is required.',
                                    ])
                                    ->extraAttributes(['class' => 'text-lg'])
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('Status')
                                    ->label('Status')
                                    ->options([
                                        'Active' => 'Active',
                                        'Dismantle' => 'Dismantle',
                                        'Suspend' => 'Suspend',
                                        'Not Active' => 'Not Active',
                                    ])
                                    ->default('Active')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-signal')
                                    ->prefixIconColor('primary')
                                    ->helperText('Current status of the fiber optic connection.')
                                    ->validationMessages([
                                        'required' => 'Status is required.',
                                    ])
                                    ->extraAttributes(['class' => 'text-lg'])
                                    ->columnSpanFull(),
                            ])
                            ->columns([
                                'sm' => 1,
                                'lg' => 2,
                            ]),
                    ])
                    ->extraAttributes(['class' => 'p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->description('Daftar semua Remote FO dengan status OPERATIONAL.')
        ->query(function () {
            return parent::getModel()::query()
                ->whereHas('remote', function ($query) {
                    $query->where('Link', 'FO-GSM');
                });
        })
            ->columns([
                Tables\Columns\TextColumn::make('CID')
                    ->label('Connection ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Connection BUD copied')
                    ->icon('heroicon-o-identification'),

                Tables\Columns\TextColumn::make('Provider')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office'),

                Tables\Columns\TextColumn::make('remote.Nama_Toko')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('Site_ID')
                    ->label('Site ID')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map'),

                Tables\Columns\TextColumn::make('remote.DC')
                    ->label('Distribution Center')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin'),

                Tables\Columns\BadgeColumn::make('Status')
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Dismantle',
                        'warning' => 'Suspend',
                        'gray' => 'Not Active',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
            Tables\Filters\SelectFilter::make('Status')
                ->options([
                    'Active' => 'Active',
                    'Dismantle' => 'Dismantle',
                    'Suspend' => 'Suspend',
                    'Not Active' => 'Not Active',
                ])
                ->indicator('Status'),

            Tables\Filters\SelectFilter::make('Provider')
                ->options(function () {
                    // Get all unique providers from the table_fo
                    return TableFo::query()
                        ->distinct()
                        ->pluck('Provider', 'Provider')
                        ->toArray();
                })
                ->searchable()
                ->indicator('Provider'),

            Tables\Filters\SelectFilter::make('DC')
                ->options(function () {
                    return TableRemote::query()
                        ->where('Link', 'FO-GSM')
                        ->distinct()
                        ->pluck('DC', 'DC')
                        ->toArray();
                })
                ->searchable()
                ->indicator('Distribution Center'),
        ])

            ->filtersFormColumns(3)
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(TableFoExporter::class)
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->fileName(fn () => 'TableFo_Export_' . now()->format('Ymd_His') . '.xlsx')
                    ->chunkSize(1000),
                Tables\Actions\ImportAction::make()
                    ->importer(TableFoImporter::class)
                    ->label('Import from Excel')
                    ->icon('heroicon-o-arrow-up-on-square-stack')
                    ->color('info')
                    ->chunkSize(1000),
                Tables\Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->action(function () {
                    $headers = [
                        'CID',
                        'Provider',
                        'Register_Name',
                        'Site_ID',
                        'Status',
                    ];

                    $filePath = storage_path('app/public/TableFO_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                    $writer = new \OpenSpout\Writer\XLSX\Writer();
                    $writer->openToFile($filePath);

                    $sheet = $writer->getCurrentSheet();
                    $row = \OpenSpout\Common\Entity\Row::fromValues($headers);
                    $writer->addRow($row);

                    $sampleRow = [
                        'FO123456',
                        'ICON+',
                        'ALFAMART CIKARANG',
                        'CD27',  // This should be a valid Site_ID from table_remote
                        'Active',
                    ];
                    $row = \OpenSpout\Common\Entity\Row::fromValues($sampleRow);
                    $writer->addRow($row);

                    $writer->close();

                    return response()->download($filePath, 'TableFO_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend(true);
                })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->modalWidth('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('FO Connection Updated')
                            ->body('The fiber optic connection has been updated successfully.')
                            ->duration(5000)
                    ),
                
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
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
            'index' => Pages\ListTableFos::route('/'),
            'create' => Pages\CreateTableFo::route('/create'),
            'edit' => Pages\EditTableFo::route('/{record}/edit'),
        ];
    }
}
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
use App\Filament\Exports\TableFoExcelExport;
use App\Filament\Imports\TableFoImporter;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Placeholder;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;

class TableFoResource extends Resource
{
    protected static ?string $model = TableFo::class;
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Fiber Optic';
    protected static ?string $pluralModelLabel = 'Fiber Optic Connections';
    protected static ?string $recordTitleAttribute = 'ID';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Details')
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
                            ]),
                        Tab::make('History')
                            ->schema([
                                Placeholder::make('HistoryList')
                                    ->content(function ($record) {
                                        if (!$record) {
                                            return view('filament.pages.fo-history', ['histories' => []]);
                                        }
                                        $record->load('histories');
                                        return view('filament.pages.fo-history', ['histories' => $record->histories ?? []]);
                                    })
                                    ->columnSpan('full'),
                            ]),
                    ])
                    ->columnSpanFull(),
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
                    ->copyMessage('Connection ID copied'),

                Tables\Columns\TextColumn::make('Provider')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('Register_Name')
                    ->label('Register Name')
                    ->searchable()
                    ->sortable(),

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
            ->defaultSort('remote.DC', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('DC')
                    ->label('Distribution Center')
                    ->options(function () {
                        return TableRemote::distinct()
                            ->pluck('DC', 'DC')
                            ->filter()
                            ->sort()
                            ->mapWithKeys(fn ($dc) => [$dc => ucfirst(strtolower($dc))])
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->placeholder('Select Distribution Center'),

                Tables\Filters\SelectFilter::make('remote.Customer')
                    ->label('Customer')
                    ->options(function () {
                        return TableFo::query()
                            ->join('table_remote', 'table_fo.Site_ID', '=', 'table_remote.Site_ID')
                            ->where('table_remote.Link', 'FO-GSM')
                            ->distinct()
                            ->pluck('table_remote.Customer', 'table_remote.Customer')
                            ->toArray();
                    })
                    ->searchable()
                    ->indicator('Customer')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('Status')
                    ->options([
                        'Active' => 'Aktif',
                        'Dismantle' => 'Dibongkar',
                        'Suspend' => 'Tertunda',
                        'Not Active' => 'Tidak Aktif',
                    ])
                    ->indicator('Status')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('Provider')
                    ->label('Provider')
                    ->options(function () {
                        return TableFo::query()
                            ->distinct()
                            ->pluck('Provider', 'Provider')
                            ->toArray();
                    })
                    ->searchable()
                    ->indicator('Provider')
                    ->multiple(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-o-funnel')
                    ->color('primary')
            )
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $query = TableFo::query()
                            ->with('remote')
                            ->whereHas('remote', function ($query) {
                                $query->where('Link', 'FO-GSM');
                            });

                        // Apply all active filters
                        foreach ($livewire->tableFilters as $filter => $value) {
                            if (!empty($value['values'])) {
                                if ($filter === 'DC') {
                                    $query->whereHas('remote', function (Builder $query) use ($value) {
                                        $query->whereIn('DC', (array)$value['values']);
                                    });
                                } elseif ($filter === 'remote.Customer') {
                                    $query->whereHas('remote', function (Builder $query) use ($value) {
                                        $query->whereIn('Customer', (array)$value['values']);
                                    });
                                } elseif ($filter === 'Status') {
                                    $query->whereIn('Status', (array)$value['values']);
                                } elseif ($filter === 'Provider') {
                                    $query->whereIn('Provider', (array)$value['values']);
                                }
                            }
                        }

                        // Apply search query if present
                        if ($livewire->tableSearch) {
                            $query->where(function ($q) use ($livewire) {
                                $q->where('CID', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Provider', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Register_Name', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Site_ID', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhereHas('remote', function ($q) use ($livewire) {
                                      $q->where('Nama_Toko', 'like', '%' . $livewire->tableSearch . '%')
                                        ->orWhere('DC', 'like', '%' . $livewire->tableSearch . '%');
                                  });
                            });
                        }

                        // Apply sorting if present
                        if ($livewire->tableSortColumn) {
                            if ($livewire->tableSortColumn === 'remote.Nama_Toko' || $livewire->tableSortColumn === 'remote.DC') {
                                $query->orderBy($livewire->tableSortColumn, $livewire->tableSortDirection);
                            } else {
                                $query->orderBy($livewire->tableSortColumn, $livewire->tableSortDirection);
                            }
                        }

                        return Excel::download(
                            new TableFoExcelExport($query),
                            'fo_export_' . now()->format('Ymd_His') . '.xlsx'
                        );
                    })
                    ->tooltip('Export filtered data to Excel'),

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

                        $filePath = storage_path('app/public/TableFo_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->fromArray($headers, null, 'A1');

                        $sampleRow = [
                            'FO123456',
                            'ICON+',
                            'ALFAMART CIKARANG',
                            'CD27',
                            'Active',
                        ];
                        $sheet->fromArray([$sampleRow], null, 'A2');

                        // Apply basic styles
                        $sheet->getStyle('A1:E1')->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save($filePath);

                        return response()->download($filePath, 'TableFo_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
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
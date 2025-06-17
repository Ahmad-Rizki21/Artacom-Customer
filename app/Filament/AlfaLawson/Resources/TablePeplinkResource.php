<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;
use App\Models\AlfaLawson\TablePeplink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Imports\TablePeplinkImportImporter;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Exports\TablePeplinkExcelExport;
use Filament\Tables\Actions\Action;

class TablePeplinkResource extends Resource
{
    protected static ?string $model = TablePeplink::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Peplink Device';
    protected static ?string $modelLabel = 'Peplink Device';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $mainSchema = fn (string $operation) => [
            Forms\Components\Tabs::make('Tabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Details')
                        ->schema([
                            Section::make('Device Information')
                                ->description('Provide general information about the Peplink device.')
                                ->icon('heroicon-o-device-phone-mobile')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('SN')
                                                ->label('Serial Number')
                                                ->required()
                                                ->maxLength(24)
                                                ->placeholder('Masukkan serial number (contoh: 192D33680BEE atau 192D-3368-0BEE)')
                                                ->unique(ignoreRecord: true)
                                                ->prefixIcon('heroicon-o-hashtag')
                                                ->helperText('Serial Number harus unik dan akan disimpan dengan tanda -.')
                                                ->rules(['regex:/^[A-Za-z0-9-]+$/', 'max:24'])
                                                ->dehydrated(true)
                                                ->afterStateHydrated(function ($component, $state, $record) {
                                                    if ($record) {
                                                        $component->state($record->SN);
                                                    }
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if (strlen($state) === 12 && strpos($state, '-') === false) {
                                                        $state = substr($state, 0, 4) . '-' . substr($state, 4, 4) . '-' . substr($state, 8, 4);
                                                    }
                                                    $set('SN', strtoupper($state));
                                                }),
                                            Forms\Components\TextInput::make('Model')
                                                ->required()
                                                ->prefixIcon('heroicon-o-cube')
                                                ->placeholder('Pilih tipe/model perangkat')
                                                ->helperText('Pilih model perangkat Peplink dari daftar.'),
                                            Forms\Components\DatePicker::make('tgl_beli')
                                                ->label('Purchase Date')
                                                ->required()
                                                ->prefixIcon('heroicon-o-calendar')
                                                ->helperText('Tanggal pembelian perangkat.')
                                                ->default(now()),
                                            Forms\Components\TextInput::make('garansi')
                                                ->label('Warranty')
                                                ->prefixIcon('heroicon-o-shield-check')
                                                ->helperText('Masukkan masa garansi (contoh: 12 Bulan atau N/A)')
                                                ->default('N/A'),
                                            Forms\Components\Select::make('Kepemilikan')
                                                ->label('Ownership')
                                                ->options([
                                                    'ORIX' => 'ORIX',
                                                    'TRANSTEL' => 'TRANSTEL',
                                                    'JEDI' => 'JEDI',
                                                    'ARTACOM' => 'ARTACOM',
                                                ])
                                                ->required()
                                                ->prefixIcon('heroicon-o-user-group')
                                                ->helperText('Pilih kepemilikan perangkat.'),
                                            Forms\Components\TextInput::make('Site_ID')
                                                ->label('Site ID')
                                                ->nullable()
                                                ->prefixIcon('heroicon-m-building-storefront')
                                                ->helperText('Masukkan ID lokasi toko terkait perangkat (kosongkan jika belum terpasang).')
                                                ->maxLength(8),
                                        ]),
                                ]),
                            Section::make('Status and Description')
                                ->description('Status and additional notes.')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('Status')
                                                ->label('Status')
                                                ->options([
                                                    'Operasional' => 'Operasional',
                                                    'Rusak' => 'Rusak',
                                                    'Sparepart' => 'Sparepart',
                                                    'Perbaikan' => 'Perbaikan',
                                                    'Hilang' => 'Hilang',
                                                    'Tidak Bisa Diperbaiki' => 'Tidak Bisa Diperbaiki',
                                                ])
                                                ->default('Operasional')
                                                ->prefixIcon('heroicon-o-light-bulb')
                                                ->helperText('Status penggunaan perangkat.'),
                                            Forms\Components\Placeholder::make('placeholder')
                                                ->content('')
                                                ->hiddenLabel(),
                                        ]),
                                    Forms\Components\Textarea::make('Deskripsi')
                                        ->label('Description')
                                        ->nullable()
                                        ->rows(3)
                                        ->helperText('Tambahkan deskripsi tambahan untuk perangkat.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('History')
                        ->schema([
                            Forms\Components\Placeholder::make('HistoryList')
                                ->content(function ($record) {
                                    if ($record) {
                                        $record->load('histories');
                                        return view('filament.pages.peplink-history', ['histories' => $record->histories ?? []]);
                                    }
                                    return view('filament.pages.peplink-history', ['histories' => []]);
                                })
                                ->columnSpan('full'),
                        ]),
                ])
                ->columnSpanFull(),
        ];

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema($mainSchema('create'))
                    ->columnSpan(['lg' => 12])
                    ->visible(fn (string $operation): bool => $operation === 'create'),
                Forms\Components\Group::make()
                    ->schema($mainSchema('edit'))
                    ->columnSpan(['lg' => 12])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ])
            ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('SN')
                    ->label('Serial Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-hashtag')
                    ->toggleable(),
                TextColumn::make('Model')
                    ->label('Model')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-cube')
                    ->toggleable(),
                TextColumn::make('Kepemilikan')
                    ->label('Ownership')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ORIX' => 'success',
                        'TRANSTEL' => 'warning',
                        'JEDI' => 'primary',
                        'ARTACOM' => 'secondary',
                        default => 'secondary',
                    })
                    ->toggleable(),
                TextColumn::make('Site_ID')
                    ->label('Lokasi Toko')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->icon('heroicon-m-building-storefront')
                    ->toggleable(),
                TextColumn::make('tgl_beli')
                    ->label('Purchase Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('garansi')
                    ->label('Warranty')
                    ->icon('heroicon-o-shield-check')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('Status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Operasional' => 'success',
                        'Rusak' => 'danger',
                        'Sparepart' => 'warning',
                        'Perbaikan' => 'secondary',
                        'Tidak Bisa Diperbaiki' => 'info',
                        default => 'secondary',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Operasional' => 'heroicon-o-check-circle',
                        'Rusak' => 'heroicon-o-x-circle',
                        'Sparepart' => 'heroicon-o-wrench-screwdriver',
                        'Perbaikan' => 'heroicon-o-archive-box',
                        'Tidak Bisa Diperbaiki' => 'heroicon-o-trash',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->toggleable(),
                TextColumn::make('Deskripsi')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->Deskripsi)
                    ->icon('heroicon-o-document-text')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->description('Daftar semua Perangkat Peplink yang terdaftar di sistem.')
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('Status')
                    ->label('Filter Status')
                    ->options([
                        'Operasional' => 'Operasional',
                        'Rusak' => 'Rusak',
                        'Sparepart' => 'Sparepart',
                        'Perbaikan' => 'Perbaikan',
                        'Hilang' => 'Hilang',
                        'Tidak Bisa Diperbaiki' => 'Tidak Bisa Diperbaiki',
                    ])
                    ->multiple()
                    ->searchable()
                    ->placeholder('All'),
                SelectFilter::make('Kepemilikan')
                    ->label('Filter Ownership')
                    ->options([
                        'ARTACOM' => 'ARTACOM',
                        'JEDI' => 'JEDI',
                        'TRANSTEL' => 'TRANSTEL',
                        'ORIX' => 'ORIX',
                    ])
                    ->multiple()
                    ->searchable()
                    ->placeholder('All'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filters')
                    ->icon('heroicon-o-funnel')
                    ->color('primary')
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(TablePeplinkImportImporter::class)
                    ->label('Import Peplink Devices')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->chunkSize(1000),
                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->action(function () {
                        $headers = [
                            'SN',
                            'Model',
                            'Kepemilikan',
                            'tgl_beli',
                            'garansi',
                            'Site_ID',
                            'Status',
                            'Deskripsi',
                        ];

                        $filePath = storage_path('app/public/Peplink_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->fromArray($headers, null, 'A1');

                        $sampleRow = [
                            '192D-3ED1-BD71',
                            'PEPLINK BALANCE 20X',
                            'JEDI',
                            '2023-01-15',
                            '12 Bulan',
                            'TG50',
                            'Operasional',
                            'Perangkat utama toko TG50',
                        ];
                        $sheet->fromArray([$sampleRow], null, 'A2');

                        $sheet->getStyle('A1:H1')->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save($filePath);

                        return response()->download($filePath, 'Peplink_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->action(function ($livewire) {
                        $query = TablePeplink::query();

                        // Apply all active filters
                        foreach ($livewire->tableFilters as $filter => $value) {
                            if (!empty($value['values'])) {
                                $query->whereIn($filter, (array)$value['values']);
                            }
                        }

                        // Apply search query if present
                        if ($livewire->tableSearch) {
                            $query->where(function ($q) use ($livewire) {
                                $q->where('SN', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Model', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Kepemilikan', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Site_ID', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Status', 'like', '%' . $livewire->tableSearch . '%')
                                  ->orWhere('Deskripsi', 'like', '%' . $livewire->tableSearch . '%');
                            });
                        }

                        // Apply sorting if present
                        if ($livewire->tableSortColumn) {
                            $query->orderBy($livewire->tableSortColumn, $livewire->tableSortDirection);
                        }

                        return Excel::download(
                            new TablePeplinkExcelExport($query),
                            'peplink_export_' . now()->format('Ymd_His') . '.xlsx'
                        );
                    })
                    ->tooltip('Export filtered data to Excel'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data Peplink')
            ->emptyStateDescription('Klik tombol "Tambah" atau "Import Peplink Devices" untuk menambahkan perangkat Peplink baru');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTablePeplinks::route('/'),
            'create' => Pages\CreateTablePeplink::route('/create'),
            'edit' => Pages\EditTablePeplink::route('/{record}/edit'),
        ];
    }
}
<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\MaipuResource\Pages;
use App\Models\AlfaLawson\Maipu;
use App\Models\AlfaLawson\RemoteAtmbsi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Imports\MaipuImportImporter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Exports\MaipuExcelExport;

class MaipuResource extends Resource
{
    protected static ?string $model = Maipu::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Maipu Device';
    protected static ?string $modelLabel = 'Maipu Device';
    protected static ?string $navigationGroup = 'Sdwan Network Atm';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $mainSchema = fn (string $operation) => [
            Forms\Components\Tabs::make('Tabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Details')
                        ->schema([
                            Section::make('Device Information')
                        
                                ->description('Informasi umum perangkat Maipu.')
                                ->icon('heroicon-o-device-phone-mobile')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('SN')
                                                ->label('Serial Number')
                                                ->required()
                                                ->maxLength(32)
                                                ->unique(ignoreRecord: true)
                                                ->prefixIcon('heroicon-o-hashtag')
                                                ->helperText('Serial Number harus unik.')
                                                ->rules(['max:32']),
                                            Forms\Components\TextInput::make('Model')
                                                ->required()
                                                ->maxLength(32)
                                                ->prefixIcon('heroicon-o-cube')
                                                ->placeholder('Masukkan tipe/model perangkat'),
                                            Forms\Components\Select::make('Kepemilikan')
                                                ->required()
                                                ->options([
                                                    'ORIX' => 'ORIX',
                                                    'TRANSTEL' => 'TRANSTEL',
                                                    'JEDI' => 'JEDI',
                                                    'ARTACOM' => 'ARTACOM',
                                                ])
                                                ->prefixIcon('heroicon-o-user-group')
                                                ->helperText('Pilih kepemilikan perangkat.'),
                                            Forms\Components\DatePicker::make('tgl_beli')
                                                ->label('Tanggal Pembelian')
                                                ->required()
                                                ->prefixIcon('heroicon-o-calendar'),
                                            Forms\Components\TextInput::make('garansi')
                                                ->label('Garansi')
                                                ->required()
                                                ->maxLength(16)
                                                ->prefixIcon('heroicon-o-shield-check')
                                                ->helperText('Masukkan masa garansi (contoh: 12 Bulan atau N/A)'),
                                            Forms\Components\Select::make('Site_ID')
                                                ->label('Remote ATM BSI')
                                                ->options(RemoteAtmbsi::pluck('Site_Name', 'Site_ID'))
                                                ->searchable()
                                                ->nullable()
                                                ->prefixIcon('heroicon-m-building-storefront')
                                                ->helperText('Pilih lokasi toko terkait perangkat'),
                                            Forms\Components\TextInput::make('Status')
                                                ->required()
                                                ->maxLength(32)
                                                ->prefixIcon('heroicon-o-light-bulb')
                                                ->placeholder('Masukkan status perangkat'),
                                        ]),
                                ]),
                            Section::make('Keterangan')
                                ->schema([
                                    Forms\Components\Textarea::make('Deskripsi')
                                        ->label('Deskripsi')
                                        ->nullable()
                                        ->rows(3)
                                        ->helperText('Tambahkan keterangan tambahan (opsional)')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('History')
                        ->schema([
                            Forms\Components\Placeholder::make('history')
                                ->content(function ($record) {
                                    if ($record) {
                                        $record->load('histories.user');
                                        return view('filament.pages.maipu-history', [
                                            'histories' => $record->histories ?? [],
                                        ]);
                                    }
                                    return view('filament.pages.maipu-history', ['histories' => []]);
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
                    ->label('Kepemilikan')
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
                TextColumn::make('remoteAtmbsi.Site_Name')
                    ->label('Remote ATM BSI')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-storefront')
                    ->toggleable(),
                TextColumn::make('tgl_beli')
                    ->label('Tanggal Pembelian')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('garansi')
                    ->label('Garansi')
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
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->Deskripsi)
                    ->icon('heroicon-o-document-text')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->description('Daftar perangkat Maipu yang terdaftar di sistem.')
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
                    ->placeholder('Semua'),
                SelectFilter::make('Kepemilikan')
                    ->label('Filter Kepemilikan')
                    ->options([
                        'ARTACOM' => 'ARTACOM',
                        'JEDI' => 'JEDI',
                        'TRANSTEL' => 'TRANSTEL',
                        'ORIX' => 'ORIX',
                    ])
                    ->multiple()
                    ->searchable()
                    ->placeholder('Semua'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-o-funnel')
                    ->color('primary')
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                ->importer(MaipuImportImporter::class)
                ->label('Import Maipu Devices')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->chunkSize(1000),
                Action::make('downloadTemplate')
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

                    $filePath = storage_path('app/public/Maipu_Import_Template_' . now()->format('Ymd_His') . '.xlsx');

                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->fromArray($headers, null, 'A1');

                    $sampleRow = [
                        'SN1234-5678-ABCD',
                        'MAIPU MODEL X',
                        'ARTACOM',
                        '2024-01-01',
                        '12 Bulan',
                        'SITE001',
                        'Operasional',
                        'Perangkat Maipu utama di site SITE001',
                    ];
                    $sheet->fromArray([$sampleRow], null, 'A2');

                    $sheet->getStyle('A1:H1')->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ]);

                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save($filePath);

                    return response()->download($filePath, 'Maipu_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend(true);
                }),

            Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('success')
                ->action(function ($livewire) {
                    $query = Maipu::query();

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
                        new MaipuExcelExport($query),
                        'maipu_export_' . now()->format('Ymd_His') . '.xlsx'
                    );
                })
                ->tooltip('Export filtered data to Excel'),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data Maipu')
            ->emptyStateDescription('Klik tombol "Tambah" untuk menambahkan perangkat Maipu baru');
    }

    public static function getRelations(): array
    {
        return [
            // Tambahkan relasi jika ada, misal history
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaipus::route('/'),
            'create' => Pages\CreateMaipu::route('/create'),
            'edit' => Pages\EditMaipu::route('/{record}/edit'),
        ];
    }
}

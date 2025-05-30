<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;
use App\Models\AlfaLawson\TablePeplink;
use App\Models\AlfaLawson\TableRemote;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;

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
            Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Details')
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
                                                ->placeholder('Masukkan serial number (contoh: 192D33680BEE tanpa -)')
                                                ->unique(ignoreRecord: true)
                                                ->prefixIcon('heroicon-o-hashtag')
                                                ->helperText('Serial Number harus unik untuk setiap perangkat dan disimpan tanpa tanda -.')
                                                ->dehydrated(false)
                                                ->afterStateHydrated(function ($component, $state, $record) {
                                                    if ($record) {
                                                        $component->state($record->getRawOriginal('SN'));
                                                    }
                                                }),
                                            Forms\Components\TextInput::make('Model')
                                                ->required()
                                                ->prefixIcon('heroicon-o-cube')
                                                ->placeholder('Pilih tipe/model perangkat')
                                                ->helperText('Pilih model perangkat Peplink dari daftar.'),
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
                    Tabs\Tab::make('History')
                        ->schema([
                            Forms\Components\Placeholder::make('HistoryList')
                                ->content(function ($record) {
                                    $record->load('histories');
                                    return view('filament.pages.peplink-history', ['histories' => $record->histories ?? []]);
                                })
                                ->columnSpan('full'),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    
        return $form
            ->schema([
                Group::make()
                    ->schema($mainSchema('create'))
                    ->columnSpan(['lg' => 12])
                    ->visible(fn (string $operation): bool => $operation === 'create'),
    
                Group::make()
                    ->schema($mainSchema('edit'))
                    ->columnSpan(['lg' => 12])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ])
            ->columns(12); // Removed maxWidth('6xl')
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
                        'ALFA' => 'success',
                        'SEWA' => 'warning',
                        'CUSTOMER' => 'info',
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
                    ->tooltip(fn ($record) => $record->remote
                        ? "Toko: {$record->remote->Nama_Toko} " . ($record->Lokasi_Tambahan ? "- {$record->Lokasi_Tambahan}" : '')
                        : 'Tidak ada lokasi')
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
                    ->options([
                        'Operasional' => 'Operasional',
                        'Rusak' => 'Rusak',
                        'Sparepart' => 'Sparepart',
                        'Perbaikan' => 'Perbaikan',
                        'Tidak Bisa Diperbaiki' => 'Tidak Bisa Diperbaiki',
                    ])
                    ->label('Filter Status'),
                SelectFilter::make('Kepemilikan')
                    ->label('Filter Ownership')
                    ->options([
                        'ALFA' => 'ALFA',
                        'SEWA' => 'SEWA',
                        'CUSTOMER' => 'CUSTOMER',
                        'JEDI' => 'JEDI',
                        'ARTACOM' => 'ARTACOM',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(TablePeplinkImportImporter::class)
                    ->label('Import Peplink Devices')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info'),
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

                        $filePath = storage_path('app/public/TablePeplink_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                        $writer = new XLSXWriter();
                        $writer->openToFile($filePath);

                        $sheet = $writer->getCurrentSheet();
                        $row = Row::fromValues($headers);
                        $writer->addRow($row);

                        $sampleRow = [
                            'PLK123456789012345', // Without dashes
                            'Balance 20X',
                            'ALFA',
                            '2023-05-15',
                            '12 Bulan',
                            'SITE001',
                            'Operasional',
                            'Contoh deskripsi perangkat',
                        ];
                        $row = Row::fromValues($sampleRow);
                        $writer->addRow($row);

                        $writer->close();

                        return response()->download($filePath, 'TablePeplink_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->action(function () {
                        Log::info('TablePeplinkResource: Starting export action with PhpSpreadsheet');

                        try {
                            $spreadsheet = new Spreadsheet();
                            $sheet = $spreadsheet->getActiveSheet();
                            $sheet->setTitle('Peplink Data');

                            $headers = [
                                'Serial Number',
                                'Model',
                                'Ownership',
                                'Purchase Date',
                                'Warranty',
                                'Site ID',
                                'Store Name',
                                'Status',
                                'Description',
                            ];
                            $sheet->fromArray($headers, null, 'A1');

                            $data = TablePeplink::with('remote')->get()->map(function ($row) {
                                return [
                                    $row->SN ?? '-', // Raw SN without accessor interference
                                    $row->Model ?? '-',
                                    $row->Kepemilikan ?? '-',
                                    $row->tgl_beli ? \Carbon\Carbon::parse($row->tgl_beli)->format('d/m/Y') : '-',
                                    $row->garansi ?? '-',
                                    $row->Site_ID ?? '-',
                                    $row->remote ? strtoupper($row->remote->Nama_Toko ?? '-') : '-',
                                    match ($row->Status) {
                                        'Operasional' => '✅ Operasional',
                                        'Rusak' => '❌ Rusak',
                                        'Sparepart' => '🔧 Sparepart',
                                        'Perbaikan' => '🛠️ Perbaikan',
                                        'Tidak Bisa Diperbaiki' => '🚫 Tidak Bisa Diperbaiki',
                                        default => $row->Status ?? '-'
                                    },
                                    $row->Deskripsi ?? '-',
                                ];
                            })->toArray();
                            $sheet->fromArray($data, null, 'A2');

                            $highestRow = $sheet->getHighestRow();

                            $sheet->getStyle('A1:I1')->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE], 'size' => 12],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF006400']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
                            ]);

                            for ($row = 2; $row <= $highestRow; $row++) {
                                $fillColor = ($row % 2 === 0) ? 'FFDFECDB' : 'FFFFFFFF';
                                if (strpos($sheet->getCell("H{$row}")->getValue(), 'Operasional') !== false) {
                                    $fillColor = 'FFFFF5D7';
                                }
                                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fillColor]],
                                ]);
                            }

                            $chartData = TablePeplink::select('Kepemilikan', DB::raw('COUNT(*) as count'))
                                ->groupBy('Kepemilikan')
                                ->get()
                                ->map(function ($item) {
                                    return [$item->Kepemilikan ?? 'Unknown', $item->count];
                                })
                                ->toArray();

                            $chartStartRow = $highestRow + 3;
                            $sheet->setCellValue('A' . $chartStartRow, 'Ownership');
                            $sheet->setCellValue('B' . $chartStartRow, 'Number of Devices');
                            $sheet->fromArray($chartData, null, 'A' . ($chartStartRow + 1));

                            $chartEndRow = $chartStartRow + count($chartData);
                            $sheet->getStyle('A' . $chartStartRow . ':B' . $chartEndRow)->applyFromArray([
                                'font' => ['bold' => true],
                                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);

                            $label = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Sheet1!$B$' . $chartStartRow, null, 1)];
                            $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Sheet1!$A$' . ($chartStartRow + 1) . ':$A$' . $chartEndRow, null, count($chartData))];
                            $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Sheet1!$B$' . ($chartStartRow + 1) . ':$B$' . $chartEndRow, null, count($chartData))];

                            $series = new DataSeries(
                                DataSeries::TYPE_BARCHART,
                                DataSeries::GROUPING_STANDARD,
                                range(0, count($values) - 1),
                                $label,
                                $categories,
                                $values
                            );

                            $plotArea = new PlotArea(null, [$series]);
                            $legend = new Legend(Legend::POSITION_RIGHT, null, false);
                            $title = new Title('Number of Devices per Ownership');

                            $chart = new Chart('DevicePerOwnership', $title, $legend, $plotArea);
                            $chart->setTopLeftPosition('D' . ($chartStartRow + 2));
                            $chart->setBottomRightPosition('I' . ($chartStartRow + 15));
                            $sheet->addChart($chart);

                            foreach (range('A', 'I') as $col) {
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }

                            $filePath = storage_path('app/public/TablePeplink_Export_' . now()->format('Ymd_His') . '.xlsx');
                            $writer = new Xlsx($spreadsheet);
                            $writer->setIncludeCharts(true);
                            $writer->save($filePath);

                            return response()->download($filePath, 'TablePeplink_Export_' . now()->format('Ymd_His') . '.xlsx', [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])->deleteFileAfterSend(true);
                        } catch (\Exception $e) {
                            Log::error('TablePeplinkResource: Export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            Notification::make()
                                ->title('Export Failed')
                                ->body('An error occurred while exporting: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data Peplink')
            ->emptyStateDescription('Klik tombol "Tambah" atau "Import Peplink Devices" untuk menambahkan perangkat Peplink baru.');
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
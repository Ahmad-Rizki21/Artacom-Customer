<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;
use App\Models\AlfaLawson\TableRemote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Imports\TableRemoteImportImporter;
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
use OpenSpout\Writer\XLSX\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TableRemoteResource extends Resource
{
    protected static ?string $model = TableRemote::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Remote';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Information')
                    ->description('Provide general information about the site.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('Site_ID')
                            ->label('Site ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Enter unique Site ID')
                            ->helperText('This ID must be unique for each site.'),

                        Forms\Components\TextInput::make('Nama_Toko')
                            ->label('Nama Toko')
                            ->required()
                            ->placeholder('Enter store name'),

                        Forms\Components\TextInput::make('DC')
                            ->label('Distribution Center')
                            ->placeholder('Enter DC (e.g., Marunda, Cikarang)'),

                        Forms\Components\DatePicker::make('Online_Date')
                            ->label('Online Date')
                            ->placeholder('Select the date the site went online'),
                    ]),

                Forms\Components\Section::make('Network Configuration')
                    ->description('Details about the site\'s network configuration.')
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('IP_Address')
                            ->label('IP Address')
                            ->required()
                            ->rule('ip')
                            ->placeholder('Enter valid IP address')
                            ->helperText('Example: 192.168.1.1'),

                        Forms\Components\TextInput::make('Vlan')
                            ->label('VLAN')
                            ->required()
                            ->numeric()
                            ->placeholder('Enter VLAN number (1-4094)')
                            ->helperText('VLAN must be a number between 1 and 4094.'),

                        Forms\Components\TextInput::make('Controller')
                            ->label('Controller')
                            ->placeholder('Enter Controller (e.g., Cisco, Aruba)'),

                        Forms\Components\Select::make('Link')
                            ->label('Connection Type')
                            ->required()
                            ->options([
                                'FO-GSM' => 'FO-GSM',
                                'SINGLE-GSM' => 'SINGLE-GSM',
                                'DUAL-GSM' => 'DUAL-GSM',
                            ])
                            ->helperText('Select the primary connection type.'),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->description('Additional details and status information.')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('Customer')
                            ->label('Customer')
                            ->required()
                            ->options([
                                'ALFAMART' => 'ALFAMART',
                                'LAWSON' => 'LAWSON',
                            ])
                            ->searchable()
                            ->helperText('Select the customer associated with this site.'),

                        Forms\Components\Select::make('Status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'OPERATIONAL' => 'Operational',
                            ])
                            ->default('OPERATIONAL')
                            ->disabled()
                            ->helperText('Status is fixed to Operational for this table.'),

                        Forms\Components\Textarea::make('Keterangan')
                            ->label('Remarks')
                            ->placeholder('Enter additional notes or remarks about the site.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(TableRemote::query()->where('Status', 'OPERATIONAL'))
            ->description('Daftar semua Remote dengan status OPERATIONAL.')
            ->columns([
                TextColumn::make('Site_ID')
                    ->label('Site ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy Site ID')
                    ->weight(FontWeight::Bold)
                    // ->icon('heroicon-o-building-library')
                    ->toggleable(),
            
                TextColumn::make('Nama_Toko')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-shopping-bag')
                    ->toggleable(),
            
                TextColumn::make('DC')
                    ->label('Distribution Center')
                    ->badge()
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(),
            
                TextColumn::make('IP_Address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy IP Address or visit with port 8090')
                    ->icon('heroicon-o-globe-alt')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => "<a href='http://{$state}:8090' target='_blank'>{$state}</a>")
                    ->html()
                    ->toggleable(),
            
                TextColumn::make('Vlan')
                    ->label('VLAN')
                    ->badge()
                    ->icon('heroicon-o-squares-2x2')
                    ->color('warning')
                    ->alignCenter()
                    ->toggleable(),
            
                TextColumn::make('Controller')
                    ->label('Controller')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-cpu-chip')
                    ->toggleable(),
            
                TextColumn::make('Link')
                    ->label('Connection Type')
                    ->badge()
                    ->icon('heroicon-o-signal')
                    ->color(fn (string $state): string => match ($state) {
                        'FO-GSM' => 'success',
                        'SINGLE-GSM' => 'info',
                        'DUAL-GSM' => 'warning',
                        'FO' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
            
                TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->toggleable(),
            
                TextColumn::make('Online_Date')
                    ->label('Online Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('success')
                    ->toggleable(),
            
                TextColumn::make('Customer')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->toggleable(),
            
                TextColumn::make('Keterangan')
                    ->label('Remarks')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->Keterangan)
                    ->default('-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(),
            ])
            ->defaultSort('Site_ID', 'asc')
            ->filters([
    // Distribution Center Filter
    Tables\Filters\SelectFilter::make('DC')
        ->label('Distribution Center')
        ->options(function () {
            return TableRemote::distinct()
                ->pluck('DC', 'DC')
                ->filter() // Remove null values
                ->mapWithKeys(function ($dc) {
                    return [$dc => ucfirst(strtolower($dc))];
                })
                ->toArray();
        })
        ->multiple()
        ->searchable()
        ->placeholder('Select Distribution Center')
        ->indicateUsing(function (array $data): ?string {
            if (!empty($data['values'])) {
                return 'DC: ' . count($data['values']) . ' selected';
            }
            return null;
        }),

    // Customer Filter
    Tables\Filters\SelectFilter::make('Customer')
        ->label('Customer')
        ->options(function () {
            return TableRemote::distinct()
                ->pluck('Customer', 'Customer')
                ->filter() // Remove null values
                ->mapWithKeys(function ($customer) {
                    return [$customer => ucfirst(strtolower($customer))];
                })
                ->toArray();
        })
        ->multiple()
        ->searchable()
        ->placeholder('Select Customer')
        ->indicateUsing(function (array $data): ?string {
            if (!empty($data['values'])) {
                return 'Customer: ' . count($data['values']) . ' selected';
            }
            return null;
        }),

    // Controller Filter
    Tables\Filters\SelectFilter::make('Controller')
        ->label('Controller')
        ->options(function () {
            return TableRemote::distinct()
                ->pluck('Controller', 'Controller')
                ->filter() // Remove null values
                ->mapWithKeys(function ($controller) {
                    return [$controller => ucfirst(strtolower($controller))];
                })
                ->toArray();
        })
        ->multiple()
        ->searchable()
        ->placeholder('Select Controller')
        ->indicateUsing(function (array $data): ?string {
            if (!empty($data['values'])) {
                return 'Controller: ' . count($data['values']) . ' selected';
            }
            return null;
        }),


    // Connection Type Filter (Mapped to Category)
    Tables\Filters\SelectFilter::make('Link')
        ->label('Connection Type')
        ->options([
            'FO-GSM' => 'FO-GSM',
            'SINGLE-GSM' => 'Single-GSM',
            'DUAL-GSM' => 'Dual-GSM',
        ])
        ->multiple()
        ->placeholder('Select Connection Type')
        ->indicateUsing(function (array $data): ?string {
            if (!empty($data['values'])) {
                return 'Connection: ' . implode(', ', $data['values']);
            }
            return null;
        }),
])
->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
->filtersFormColumns(4)

->filtersTriggerAction(
    fn (Tables\Actions\Action $action) => $action
        ->button()
        ->label('Filters')
        ->icon('heroicon-o-funnel')
        ->color('primary')
)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->action(function () {
                        Log::info('TableRemoteResource: Starting export action with PhpSpreadsheet');

                        try {
                            $spreadsheet = new Spreadsheet();
                            $sheet = $spreadsheet->getActiveSheet();

                            // Set headers
                            $headers = [
                                'Site ID',
                                'Nama Toko',
                                'Distribution Center',
                                'IP Address',
                                'VLAN',
                                'Controller',
                                'Customer',
                                'Online Date',
                                'Connection Type',
                                'Status',
                                'Remarks',
                            ];
                            $sheet->fromArray($headers, null, 'A1');

                            // Set data
                            $data = TableRemote::all()->map(function ($row) {
                                return [
                                    $row->Site_ID ?? '-',
                                    strtoupper($row->Nama_Toko ?? '-'),
                                    $row->DC ?? '-',
                                    $row->IP_Address ?? '-',
                                    $row->Vlan ?? '-',
                                    $row->Controller ?? '-',
                                    $row->Customer ?? '-',
                                    $row->Online_Date ? \Carbon\Carbon::parse($row->Online_Date)->format('d/m/Y') : '-',
                                    match ($row->Link) {
                                        'FO-GSM' => '✅ FO-GSM',
                                        'SINGLE-GSM' => '🔵 SINGLE-GSM',
                                        'DUAL-GSM' => '🟡 DUAL-GSM',
                                        default => $row->Link ?? '-'
                                    },
                                    '✓ ' . ($row->Status ?? '-'),
                                    $row->Keterangan ?? '-',
                                ];
                            })->toArray();
                            $sheet->fromArray($data, null, 'A2');

                            // Apply styles
                            $highestRow = $sheet->getHighestRow();

                            // Header style
                            $sheet->getStyle('A1:K1')->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'color' => ['argb' => Color::COLOR_WHITE],
                                    'size' => 12,
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FF006400'], // Dark green
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                    'wrapText' => true,
                                ],
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color' => ['argb' => Color::COLOR_BLACK],
                                    ],
                                ],
                            ]);

                            // Data rows style
                            for ($row = 2; $row <= $highestRow; $row++) {
                                $fillColor = ($row % 2 === 0) ? 'FFDFECDB' : 'FFFFFFFF'; // Light green for even, white for odd
                                if (strpos($sheet->getCell("I{$row}")->getValue(), 'FO-GSM') !== false) {
                                    $fillColor = 'FFFFF5D7'; // Light yellow for FO-GSM
                                }
                                $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                                    'alignment' => [
                                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                                        'vertical' => Alignment::VERTICAL_CENTER,
                                        'wrapText' => true,
                                    ],
                                    'borders' => [
                                        'allBorders' => [
                                            'borderStyle' => Border::BORDER_THIN,
                                            'color' => ['argb' => Color::COLOR_BLACK],
                                        ],
                                    ],
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['argb' => $fillColor],
                                    ],
                                ]);
                            }

                            // Add chart data (count of remotes per DC)
                            $chartData = TableRemote::select('DC', DB::raw('COUNT(*) as count'))
                                ->groupBy('DC')
                                ->get()
                                ->map(function ($item) {
                                    return [$item->DC ?? 'Unknown', $item->count];
                                })
                                ->toArray();

                            Log::info('Chart Data', ['data' => $chartData]);

                            // Add chart data to sheet (start after data table)
                            $chartStartRow = $highestRow + 3;
                            $sheet->setCellValue('A' . $chartStartRow, 'Distribution Center');
                            $sheet->setCellValue('B' . $chartStartRow, 'Number of Remotes');
                            $sheet->fromArray($chartData, null, 'A' . ($chartStartRow + 1));

                            // Apply style to chart data
                            $chartEndRow = $chartStartRow + count($chartData);
                            $sheet->getStyle('A' . $chartStartRow . ':B' . $chartEndRow)->applyFromArray([
                                'font' => ['bold' => true],
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color' => ['argb' => Color::COLOR_BLACK],
                                    ],
                                ],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);

                            // Create chart
                            // Define the label for the series
                            $label = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Sheet1!$B$' . $chartStartRow, null, 1)]; // "Number of Remotes"

                            // Define the categories (X-axis labels)
                            $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Sheet1!$A$' . ($chartStartRow + 1) . ':$A$' . $chartEndRow, null, count($chartData))];

                            // Define the values (Y-axis data)
                            $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Sheet1!$B$' . ($chartStartRow + 1) . ':$B$' . $chartEndRow, null, count($chartData))];

                            // Create the data series
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
                            $title = new Title('Number of Remotes per Distribution Center');

                            $chart = new Chart(
                                'RemotePerDC',
                                $title,
                                $legend,
                                $plotArea
                            );

                            $chart->setTopLeftPosition('D' . ($chartStartRow + 2));
                            $chart->setBottomRightPosition('I' . ($chartStartRow + 15));

                            $sheet->addChart($chart);

                            // Auto-size columns
                            foreach (range('A', 'K') as $col) {
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }

                            // Save and download
                            $filePath = storage_path('app/public/TableRemote_Export_' . now()->format('Ymd_His') . '.xlsx');
                            $writer = new Xlsx($spreadsheet);
                            $writer->setIncludeCharts(true);
                            $writer->save($filePath);

                            Log::info('TableRemoteResource: Export completed', ['file' => $filePath]);

                            return response()->download($filePath, 'TableRemote_Export_' . now()->format('Ymd_His') . '.xlsx', [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])->deleteFileAfterSend(true);
                        } catch (\Exception $e) {
                            Log::error('TableRemoteResource: Export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            Notification::make()
                                ->title('Export Failed')
                                ->body('An error occurred while exporting: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ImportAction::make()
                    ->importer(TableRemoteImportImporter::class)
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
                            'Site_ID',
                            'Nama_Toko',
                            'DC',
                            'IP_Address',
                            'Vlan',
                            'Controller',
                            'Customer',
                            'Online_Date',
                            'Link',
                            'Status',
                            'Keterangan',
                        ];

                        $filePath = storage_path('app/public/TableRemote_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                        $writer = new Writer();
                        $writer->openToFile($filePath);

                        $sheet = $writer->getCurrentSheet();
                        $row = \OpenSpout\Common\Entity\Row::fromValues($headers);
                        $writer->addRow($row);

                        $sampleRow = [
                            'CD27',
                            'INDUSTRI CIKARANG 6',
                            'BEKASI',
                            '7.48.1.246',
                            '162',
                            'PRO-SDX-02',
                            'ALFAMART',
                            '2022-05-09',
                            'FO-GSM',
                            'OPERATIONAL',
                            'Sample remark',
                        ];
                        $row = \OpenSpout\Common\Entity\Row::fromValues($sampleRow);
                        $writer->addRow($row);

                        $writer->close();

                        return response()->download($filePath, 'TableRemote_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTableRemotes::route('/'),
            'create' => Pages\CreateTableRemote::route('/create'),
            'view' => Pages\ViewTableRemote::route('/{record}'),
            'edit' => Pages\EditTableRemote::route('/{record}/edit'),
        ];
    }
}
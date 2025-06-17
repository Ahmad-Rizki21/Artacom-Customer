<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;
use App\Models\AlfaLawson\TableRemote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tab;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ImportAction;
use App\Filament\Imports\TableRemoteImportImporter;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\TableRemoteExporter;

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
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs::make('Details')
                            ->schema([
                                Section::make('Site Information')
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

                                Section::make('Network Configuration')
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

                                Section::make('Additional Information')
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
                                                'BSI' => 'BSI',
                                            ])
                                            ->searchable()
                                            ->helperText('Select the customer associated with this site.'),

                                        Forms\Components\Select::make('Status')
                                            ->label('Status')
                                            ->required()
                                            ->options([
                                                'OPERATIONAL' => 'Operational',
                                                'DISMANTLED' => 'Dismantled',
                                            ])
                                            ->helperText('Select the status of the site.'),

                                        Forms\Components\Textarea::make('Keterangan')
                                            ->label('Remarks')
                                            ->placeholder('Enter additional notes or remarks about the site.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs::make('History')
                            ->schema([
                                Placeholder::make('HistoryList')
                                    ->content(function ($record) {
                                        if (!$record) {
                                            return view('filament.pages.remote-history', ['histories' => []]);
                                        }
                                        $record->load('histories');
                                        return view('filament.pages.remote-history', ['histories' => $record->histories ?? []]);
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
            ->query(TableRemote::query())
            ->description('Daftar semua Remote.')
            ->columns([
                TextColumn::make('Site_ID')
                    ->label('Site ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy Site ID')
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('Nama_Toko')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('DC')
                    ->label('Distribution Center')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('IP_Address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy IP Address or visit with port 8090')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => "<a href='http://{$state}:8090' target='_blank'>{$state}</a>")
                    ->html()
                    ->toggleable(),

                TextColumn::make('Vlan')
                    ->label('VLAN')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('Controller')
                    ->label('Controller')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('Link')
                    ->label('Connection Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FO-GSM' => 'success',
                        'SINGLE-GSM' => 'info',
                        'DUAL-GSM' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'OPERATIONAL',
                        'danger' => 'DISMANTLED',
                    ])
                    ->toggleable(),

                TextColumn::make('Online_Date')
                    ->label('Online Date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('Customer')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('Keterangan')
                    ->label('Remarks')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->Keterangan)
                    ->default('-')
                    ->toggleable(),
            ])
            ->defaultSort('Site_ID', 'asc')
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

                Tables\Filters\SelectFilter::make('Customer')
                    ->label('Customer')
                    ->options(function () {
                        return TableRemote::distinct()
                            ->pluck('Customer', 'Customer')
                            ->filter()
                            ->mapWithKeys(fn ($customer) => [$customer => ucfirst(strtolower($customer))])
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->placeholder('Select Customer'),

                Tables\Filters\SelectFilter::make('Controller')
                    ->label('Controller')
                    ->options(function () {
                        return TableRemote::distinct()
                            ->pluck('Controller', 'Controller')
                            ->filter()
                            ->mapWithKeys(fn ($controller) => [$controller => ucfirst(strtolower($controller))])
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->placeholder('Select Controller'),

                Tables\Filters\SelectFilter::make('Status')
                    ->label('Status')
                    ->options([
                        'OPERATIONAL' => 'Operational',
                        'DISMANTLED' => 'Dismantled',
                    ])
                    ->multiple()
                    ->placeholder('Select Status')
                    ->query(function ($query, array $data) {
                        $values = $data['values'] ?? [];
                        if (!empty($values)) {
                            $query->whereIn('Status', $values);
                        }
                    }),

                Tables\Filters\SelectFilter::make('Link')
                    ->label('Connection Type')
                    ->options([
                        'FO-GSM' => 'FO-GSM',
                        'SINGLE-GSM' => 'Single-GSM',
                        'DUAL-GSM' => 'Dual-GSM',
                    ])
                    ->multiple()
                    ->placeholder('Select Connection Type'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
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
                ExportAction::make()
                    ->exporter(TableRemoteExporter::class)
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->fileName(fn () => 'TableRemote_Export_' . now()->format('Ymd_His') . '.xlsx')
                    ->chunkSize(1000),
                ImportAction::make()
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
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->fromArray($headers, null, 'A1');

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
                        $sheet->fromArray([$sampleRow], null, 'A2');

                        // Apply basic styles
                        $sheet->getStyle('A1:K1')->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save($filePath);

                        return response()->download($filePath, 'TableRemote_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->paginated([10, 25, 50, 100]);
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
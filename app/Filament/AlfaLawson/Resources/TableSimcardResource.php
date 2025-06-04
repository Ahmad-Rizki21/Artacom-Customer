<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;
use App\Models\AlfaLawson\TableSimcard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Group;
// Impor yang benar untuk TableSimcardImportImporter
use App\Filament\Imports\TableSimcardImportImporter;
// Impor yang benar untuk XLSXWriter dan Row
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;

class TableSimcardResource extends Resource
{
    protected static ?string $model = TableSimcard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?string $navigationLabel = 'SIM Card';
    protected static ?string $label = 'SIM Card';
    protected static ?string $pluralLabel = 'SIM Cards';
    protected static ?string $recordTitleAttribute = 'Sim_Number';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $mainSchema = fn (string $operation) => [
            Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Details')
                        ->schema([
                            Section::make('Informasi SIM Card')
                                ->description('Masukkan detail SIM Card untuk pengelolaan jaringan.')
                                ->icon('heroicon-o-information-circle')
                                ->headerActions([
                                    Forms\Components\Actions\Action::make('clear')
                                        ->label('Reset Form')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('gray')
                                        ->action(fn (Forms\Components\Component $component) => $component->getContainer()->reset()),
                                ])
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('Sim_Number')
                                                ->label('Nomor SIM')
                                                ->required()
                                                ->maxLength(16)
                                                ->unique(TableSimcard::class, 'Sim_Number', ignoreRecord: true)
                                                ->placeholder('Contoh: 0812345678901234')
                                                ->helperText('Nomor SIM harus unik dan maksimal 16 karakter.')
                                                ->prefixIcon('heroicon-o-phone')
                                                ->autofocus()
                                                ->validationMessages([
                                                    'unique' => 'Nomor SIM sudah terdaftar.',
                                                    'max' => 'Nomor SIM tidak boleh lebih dari 16 karakter.',
                                                ])
                                                ->extraInputAttributes(['class' => 'border-2 border-indigo-200 focus:border-indigo-500 transition-colors'])
                                                ->columnSpan(1),
                                            Forms\Components\Select::make('Provider')
                                                ->label('Provider')
                                                ->options([
                                                    'Telkomsel' => 'Telkomsel',
                                                    'Indosat' => 'Indosat',
                                                    'XL' => 'XL',
                                                    'Axis' => 'Axis',
                                                    'Tri' => 'Tri',
                                                ])
                                                ->required()
                                                ->searchable()
                                                ->placeholder('Pilih provider')
                                                ->prefixIcon('heroicon-o-globe-alt')
                                                ->native(false)
                                                ->extraAttributes(['class' => 'bg-indigo-50'])
                                                ->columnSpan(1),
                                            Forms\Components\TextInput::make('Site_ID')
                                                ->label('Lokasi Toko')
                                                ->required()
                                                ->placeholder('Masukkan kode toko atau lokasi')
                                                ->prefixIcon('heroicon-m-building-storefront')
                                                ->helperText('Gunakan kode toko atau deskripsi lokasi kartu.')
                                                ->extraInputAttributes(['class' => 'border-2 border-indigo-200 focus:border-indigo-500 transition-colors'])
                                                ->columnSpan(2),
                                            Forms\Components\TextInput::make('SN_Card')
                                                ->label('Nomor Seri Kartu (SN Card)')
                                                ->maxLength(25)
                                                ->placeholder('Tidak ada SN')
                                                ->default('Tidak ada SN')
                                                ->helperText('Masukkan nomor seri kartu jika tersedia.')
                                                ->prefixIcon('heroicon-o-identification')
                                                ->extraInputAttributes(['class' => 'border-2 border-indigo-200 focus:border-indigo-500 transition-colors'])
                                                ->columnSpan(1),
                                            Forms\Components\Select::make('Status')
                                                ->label('Status')
                                                ->options([
                                                    'active' => 'Aktif',
                                                    'inactive' => 'Tidak Aktif',
                                                ])
                                                ->default('active')
                                                ->prefixIcon('heroicon-o-power')
                                                ->native(false)
                                                ->extraAttributes(['class' => 'bg-indigo-50'])
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2),
                                ]),
                            Section::make('Informasi Tambahan')
                                ->collapsible()
                                ->icon('heroicon-o-plus-circle')
                                ->schema([
                                    Forms\Components\Textarea::make('Informasi_Tambahan')
                                        ->label('Catatan')
                                        ->placeholder('Tambahkan catatan terkait SIM Card ini...')
                                        ->rows(4)
                                        ->helperText('Gunakan untuk mencatat informasi tambahan seperti tanggal aktivasi atau detail lainnya.')
                                        ->extraInputAttributes(['class' => 'border-2 border-indigo-200 focus:border-indigo-500 transition-colors']),
                                ]),
                        ]),
                    Tabs\Tab::make('History')
                        ->schema([
                            Forms\Components\Placeholder::make('HistoryList')
                                ->content(function ($record) {
                                    $record->load('histories');
                                    return view('filament.pages.simcard-history', ['histories' => $record->histories ?? []]);
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
            ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Daftar semua SIM Card yang terdaftar di sistem.')
            ->columns([
                TextColumn::make('Sim_Number')
                    ->label('Nomor SIM')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->tooltip(fn ($record) => "Nomor: {$record->Sim_Number}")
                    ->wrap()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->toggleable(),
                TextColumn::make('Provider')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->icon(fn (string $state): string => match ($state) {
                        'Telkomsel' => 'heroicon-o-signal',
                        'Indosat' => 'heroicon-o-signal',
                        'XL' => 'heroicon-o-signal',
                        'Axis' => 'heroicon-o-signal',
                        'Tri' => 'heroicon-o-signal',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Telkomsel' => 'danger',
                        'Indosat' => 'warning',
                        'XL' => 'info',
                        'Axis' => 'success',
                        'Tri' => 'primary',
                        default => 'gray',
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
                TextColumn::make('SN_Card')
                    ->label('SN Card')
                    ->searchable()
                    ->sortable()
                    ->default('Tidak ada SN')
                    ->icon('heroicon-o-identification')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->toggleable(),
                TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('Informasi_Tambahan')
                    ->label('Catatan')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->Informasi_Tambahan)
                    ->default('-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Status')
                    ->label('Filter Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('Provider')
                    ->label('Filter Provider')
                    ->options([
                        'Telkomsel' => 'Telkomsel',
                        'Indosat' => 'Indosat',
                        'XL' => 'XL',
                        'Axis' => 'Axis',
                        'Tri' => 'Tri',
                    ])
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(TableSimcardImportImporter::class)
                    ->label('Import SIM Cards')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info'),
                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->action(function () {
                        $headers = [
                            'Sim_Number',
                            'Provider',
                            'Site_ID',
                            'Informasi_Tambahan',
                            'SN_Card',
                            'Status',
                        ];
                        $filePath = storage_path('app/public/TableSimcard_Import_Template_' . now()->format('Ymd_His') . '.xlsx');
                        $writer = new XLSXWriter();
                        $writer->openToFile($filePath);
                        $sheet = $writer->getCurrentSheet();
                        $row = Row::fromValues($headers);
                        $writer->addRow($row);
                        $sampleRow = [
                            '6281181116494',
                            'Telkomsel',
                            'SITE001',
                            'Catatan tambahan',
                            '15000017328173',
                            'active',
                        ];
                        $row = Row::fromValues($sampleRow);
                        $writer->addRow($row);
                        $writer->close();
                        return response()->download($filePath, 'TableSimcard_Import_Template_' . now()->format('Ymd_His') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('Sim_Number', 'asc')
            ->striped()
            ->paginated([10, 25, 50])
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTableSimcards::route('/'),
            'create' => Pages\CreateTableSimcard::route('/create'),
            'edit' => Pages\EditTableSimcard::route('/{record}/edit'),
        ];
    }
}
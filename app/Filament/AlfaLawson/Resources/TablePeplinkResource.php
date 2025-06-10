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
use Illuminate\Database\Eloquent\Model;

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
        // Ambil daftar Site_ID dari TableRemote untuk dropdown
        $siteOptions = TableRemote::pluck('Site_ID', 'Site_ID')->toArray();

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
                                                        $component->state($record->SN); // Akan tampil dengan dash karena accessor
                                                    }
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    // Format SN dengan dash jika belum ada
                                                    if (strlen($state) === 12 && strpos($state, '-') === false) {
                                                        $state = substr($state, 0, 4) . '-' . substr($state, 4, 4) . '-' . substr($state, 8, 4);
                                                    }
                                                    $set('SN', strtoupper($state)); // Simpan dengan dash
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
                                                ->default(now()), // Default ke tanggal dan waktu saat ini (5 Juni 2025, 13:31 WIB)
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
                                            Forms\Components\Select::make('Site_ID')
                                                ->label('Site ID')
                                                ->options($siteOptions)
                                                ->required()
                                                ->prefixIcon('heroicon-m-building-storefront')
                                                ->helperText('Pilih lokasi toko terkait perangkat.')
                                                ->searchable()
                                                ->nullable() // Izinkan null jika tidak ada Site_ID valid
                                                ->afterStateHydrated(function ($component, $state, $record) {
                                                    if ($record && !$record->remote) {
                                                        $component->state(null); // Set null jika tidak ada relasi
                                                    }
                                                }),
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
                    ->tooltip(fn ($record) => $record->remote
                        ? "Toko: {$record->remote->Nama_Toko} " . ($record->remote->Lokasi_Tambahan ? "- {$record->remote->Lokasi_Tambahan}" : '')
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
                        'ARTACOM' => 'ARTACOM',
                        'JEDI' => 'JEDI',
                        'TRANSTEL' => 'TRANSTEL',
                        'ORIX' => 'ORIX',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Gunakan default delete action
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
                        // Logika download template tetap sama
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->action(function () {
                        // Logika export tetap sama
                    }),
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

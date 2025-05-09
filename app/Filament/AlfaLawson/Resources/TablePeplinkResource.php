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
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

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
        return $form
            ->schema([
                Section::make('Peplink Device Details')
                    ->description('Lengkapi data perangkat Peplink')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Card::make()
                            ->schema([
                                Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('SN')
                                        ->label('Serial Number')
                                        ->required()
                                        ->maxLength(24)
                                        ->placeholder('Masukkan serial number')
                                        ->unique(ignoreRecord: true)
                                        ->prefixIcon('heroicon-o-hashtag')
                                        ->helperText('Serial Number harus unik untuk setiap perangkat.'),

                                    Forms\Components\Select::make('Model')
                                        ->required()
                                        ->options([
                                            'Balance 20' => 'Balance 20',
                                            'Balance 30' => 'Balance 30',
                                            'Balance 50' => 'Balance 50',
                                            'Balance 210' => 'Balance 210',
                                            'MAX BR1' => 'MAX BR1',
                                            // Tambahkan model lain jika perlu
                                        ])
                                        ->searchable()
                                        ->prefixIcon('heroicon-o-cube')
                                        ->placeholder('Pilih tipe/model perangkat')
                                        ->helperText('Pilih model perangkat Peplink dari daftar.'),

                                    Forms\Components\Select::make('Kepemilikan')
                                        ->label('Ownership')
                                        ->required()
                                        ->options([
                                            'ALFA' => 'ALFA',
                                            'SEWA' => 'SEWA',
                                            'CUSTOMER' => 'CUSTOMER',
                                        ])
                                        ->prefixIcon('heroicon-o-user-group')
                                        ->placeholder('Pilih kepemilikan')
                                        ->helperText('Tentukan status kepemilikan perangkat.'),

                                    Forms\Components\DatePicker::make('tgl_beli')
                                        ->label('Purchase Date')
                                        ->required()
                                        ->maxDate(now())
                                        ->prefixIcon('heroicon-o-calendar')
                                        ->displayFormat('d M Y')
                                        ->helperText('Tanggal perangkat dibeli.'),

                                    Forms\Components\TextInput::make('garansi')
                                        ->label('Warranty Period')
                                        ->required()
                                        ->maxLength(16)
                                        ->placeholder('Contoh: 12 Bulan')
                                        ->prefixIcon('heroicon-o-shield-check')
                                        ->helperText('Masa garansi perangkat (misal: 12 Bulan).'),

                                        Forms\Components\Select::make('Site_ID')
                                        ->label('Lokasi Toko')
                                        ->relationship(
                                            'remote',
                                            'Site_ID',
                                            fn (Builder $query) => $query->orderBy('DC')
                                        )
                                        ->getOptionLabelFromRecordUsing(fn (TableRemote $record) =>
                                            "{$record->Nama_Toko} [{$record->Site_ID}] — {$record->DC}" .
                                            ($record->Kota ? " • {$record->Kota}" : "") .
                                            ($record->Alamat ? " • {$record->Alamat}" : "")
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->native(false)
                                        ->prefixIcon('heroicon-o-building-storefront')
                                        ->searchPrompt('Cari lokasi toko...')
                                        ->noSearchResultsMessage('Lokasi tidak ditemukan.')
                                        ->loadingMessage('Memuat lokasi...')
                                        ->helperText('Pilih lokasi toko untuk perangkat Peplink ini.')
                                        ->validationMessages([
                                            'required' => 'Lokasi toko wajib diisi.',
                                        ])
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('Status')
                                        ->label('Status')
                                        ->required()
                                        ->options([
                                            'Active' => 'Active',
                                            'Inactive' => 'Inactive',
                                            'Maintenance' => 'Maintenance',
                                            'Stored' => 'Stored',
                                        ])
                                        ->default('Active')
                                        ->prefixIcon('heroicon-o-light-bulb')
                                        ->helperText('Status penggunaan perangkat.'),
                                ]),
                            ]),
                    ]),
            ]);
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
                    default => 'secondary',
                })
                ->toggleable(),

            TextColumn::make('remote.Nama_Toko')
                ->label('Lokasi Toko')
                ->searchable()
                ->sortable()
                ->icon('heroicon-o-building-storefront')
                ->toggleable()
                ->toggledHiddenByDefault(),

            TextColumn::make('Site_ID')
                ->label('Site ID')
                ->searchable()
                ->sortable()
                ->icon('heroicon-o-identification')
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

            Tables\Columns\TextColumn::make('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Active' => 'success',
                    'Inactive' => 'danger',
                    'Maintenance' => 'warning',
                    'Stored' => 'secondary',
                    default => 'secondary',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'Active' => 'heroicon-o-check-circle',
                    'Inactive' => 'heroicon-o-x-circle',
                    'Maintenance' => 'heroicon-o-wrench-screwdriver',
                    'Stored' => 'heroicon-o-archive-box',
                    default => 'heroicon-o-question-mark-circle',
                })
                ->toggleable(),

            // Activity Log Column
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime('Y-m-d H:i:s')
                ->icon('heroicon-o-clock')
                ->sortable()
                ->toggleable()
                ->toggledHiddenByDefault()
                ->description(fn ($record) => 'by ' . ($record->created_by ?? 'System')),

            TextColumn::make('updated_at')
                ->label('Last Updated')
                ->dateTime('Y-m-d H:i:s')
                ->icon('heroicon-o-arrow-path')
                ->sortable()
                ->toggleable()
                ->toggledHiddenByDefault()
                ->description(fn ($record) => 'by ' . ($record->updated_by ?? 'System')),
        ])
        ->description('Daftar semua Perangkat Peplink yang terdaftar di sistem.')
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('Status')
                ->options([
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Maintenance' => 'Maintenance',
                    'Stored' => 'Stored',
                ])
                ->label('Filter Status'),

            SelectFilter::make('Kepemilikan')
                ->label('Filter Ownership')
                ->options([
                    'ALFA' => 'ALFA',
                    'SEWA' => 'SEWA',
                    'CUSTOMER' => 'CUSTOMER',
                ]),
        ])
        ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
            ])

        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ])
        ->emptyStateHeading('Belum ada data Peplink')
        ->emptyStateDescription('Klik tombol "Tambah" untuk menambahkan perangkat Peplink baru.');
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
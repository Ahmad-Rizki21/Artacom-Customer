<?php

namespace App\Filament\AlfaLawson\Resources;

use App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;
use App\Models\AlfaLawson\TableSimcard;
use App\Models\AlfaLawson\TableRemote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi SIM Card')
                            ->description('Masukkan detail SIM Card untuk lokasi tertentu.')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('Sim_Number')
                                    ->label('Nomor SIM')
                                    ->required()
                                    ->maxLength(16)
                                    ->unique(TableSimcard::class, 'Sim_Number', ignoreRecord: true)
                                    ->placeholder('Contoh: 0812345678901234')
                                    ->helperText('Nomor SIM harus unik dan maksimal 16 karakter.')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->reactive()
                                    ->autofocus()
                                    ->validationMessages([
                                        'unique' => 'Nomor SIM sudah terdaftar.',
                                        'max' => 'Nomor SIM tidak boleh lebih dari 16 karakter.',
                                    ]),

                                Forms\Components\Select::make('Provider')
                                    ->label('Provider')
                                    ->options([
                                        'Telkomsel' => 'Telkomsel',
                                        'Indosat' => 'Indosat',
                                        'XL' => 'XL',
                                        'Axis' => 'Axis',
                                        'Tri' => 'Tri',
                                    ])
                                    ->searchable()
                                    ->placeholder('Pilih provider')
                                    ->required()
                                    ->prefixIcon('heroicon-o-globe-alt')
                                    ->native(false),

                                Forms\Components\Select::make('Site_ID')
                                    ->label('Lokasi')
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
                                    ->prefixIcon('heroicon-m-building-storefront')
                                    ->columnSpanFull()
                                    ->helperText('Pilih lokasi yang terkait dengan SIM Card ini.'),

                                // Forms\Components\TextInput::make('SN_Card')
                                //     ->label('Nomor Seri Kartu (SN Card)')
                                //     ->maxLength(16)
                                //     ->placeholder('Contoh: SN1234567890')
                                //     ->helperText('Masukkan nomor seri kartu jika tersedia.')
                                //     ->prefixIcon('heroicon-o-identification'),
                                Forms\Components\TextInput::make('SN_Card')
                                ->label('Nomor Seri Kartu (SN Card)')
                                ->maxLength(25)
                                ->placeholder('Tidak ada SN') // Placeholder sebagai panduan
                                ->default('Tidak ada SN') // Default value jika tidak diisi
                                ->helperText('Masukkan nomor seri kartu jika tersedia.')
                                ->prefixIcon('heroicon-o-identification'),

                                Forms\Components\Select::make('Status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Aktif',
                                        'inactive' => 'Tidak Aktif',
                                    ])
                                    ->default('active')
                                    ->prefixIcon('heroicon-o-power')
                                    ->native(false),
                            ])
                            ->columns([
                                'sm' => 1,
                                'lg' => 2,
                            ]),
                    ])
                    ->extraAttributes(['class' => 'shadow-lg']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Daftar semua SIM Card yang terdaftar di sistem.')
            ->columns([
                Tables\Columns\TextColumn::make('Sim_Number')
                    ->label('Nomor SIM')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->tooltip(fn ($record) => "Nomor: {$record->Sim_Number}")
                    ->wrap()
                    ->extraAttributes(['class' => 'font-mono']),

                Tables\Columns\TextColumn::make('Provider')
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
                    }),

                Tables\Columns\TextColumn::make('remote.Nama_Toko')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => "[{$record->remote?->DC}] {$record->Site_ID} - {$record->remote?->Nama_Toko}")
                    ->wrap()
                    ->tooltip(fn ($record) => $record->remote ? "Toko: {$record->remote->Nama_Toko}" : 'Tidak ada lokasi')
                    ->icon('heroicon-m-building-storefront'),

                    Tables\Columns\TextColumn::make('SN_Card')
                    ->label('SN Card')
                    ->searchable()
                    ->sortable()
                    ->default('Tidak ada SN') // Default value jika kosong
                    ->icon('heroicon-o-identification')
                    ->extraAttributes(['class' => 'font-mono']),

                Tables\Columns\TextColumn::make('Status')
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
                    }),
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
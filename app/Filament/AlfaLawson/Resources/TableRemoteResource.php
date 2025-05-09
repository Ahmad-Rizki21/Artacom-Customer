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

                        Forms\Components\TextInput::make('Status')
                            ->label('Status')
                            ->required()
                            ->placeholder('Enter status code (e.g., Active)')
                            ->helperText('Use numeric values to represent the status.'),

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
            ->description('Daftar semua Remote yang terdaftar di sistem.')
            ->headerActions([
                Tables\Actions\Action::make('manageColumns')
                    ->label('Kelola Kolom')
                    ->icon('heroicon-o-bars-3')
                    ->color('gray')
                    ->modalHeading('Pilih Kolom yang Ditampilkan')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->form([
                        CheckboxList::make('visibleColumns')
                            ->label('Pilih Kolom')
                            ->options([
                                'Site_ID' => 'Site ID',
                                'Nama_Toko' => 'Nama Toko',
                                'DC' => 'Distribution Center',
                                'IP_Address' => 'IP Address',
                                'Vlan' => 'VLAN',
                                'Controller' => 'Controller',
                                'Link' => 'Connection Type',
                                'Status' => 'Status',
                                'Online_Date' => 'Online Date',
                                'Customer' => 'Customer',
                                'Keterangan' => 'Remarks',
                            ])
                            ->default(function () {
                                return array_keys([
                                    'Site_ID' => 'Site ID',
                                    'Nama_Toko' => 'Nama Toko',
                                    'DC' => 'Distribution Center',
                                    'IP_Address' => 'IP Address',
                                    'Vlan' => 'VLAN',
                                    'Controller' => 'Controller',
                                    'Link' => 'Connection Type',
                                    'Status' => 'Status',
                                    'Online_Date' => 'Online Date',
                                    'Customer' => 'Customer',
                                    'Keterangan' => 'Remarks',
                                ]);
                            })
                            ->columns(2)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        session()->put('visible_columns_remote', $data['visibleColumns']);
                        Notification::make()
                            ->title('Kolom diperbarui')
                            ->success()
                            ->send();
                    })
                    ->modalWidth('lg'),
            ])
            ->columns([
                TextColumn::make('Site_ID')
                    ->label('Site ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy Site ID')
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-o-building-library')
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
                    ->color(fn (string $state): string => match ($state) {
                        'BEKASI' => 'success',
                        'MARUNDA' => 'warning',
                        'SENTUL' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),
            
                TextColumn::make('IP_Address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy IP Address')
                    ->icon('heroicon-o-globe-alt')
                    ->color('primary')
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
                        default => 'gray',
                    })
                    ->toggleable(),
            
                TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'OPERATIONAL' => 'heroicon-o-check-circle',
                        'DOWN' => 'heroicon-o-x-circle',
                        'MAINTENANCE' => 'heroicon-o-wrench',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'OPERATIONAL' => 'success',
                        'DOWN' => 'danger',
                        'MAINTENANCE' => 'warning',
                        default => 'gray',
                    })
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
                Tables\Filters\SelectFilter::make('DC')
                    ->label('Distribution Center')
                    ->options([
                        'BEKASI' => 'Bekasi',
                        'MARUNDA' => 'Marunda',
                        'SENTUL' => 'Sentul',
                    ]),

                Tables\Filters\SelectFilter::make('Status')
                    ->label('Status')
                    ->options([
                        'OPERATIONAL' => 'Operational',
                        'DOWN' => 'Down',
                        'MAINTENANCE' => 'Maintenance',
                    ]),

                Tables\Filters\SelectFilter::make('Link')
                    ->label('Connection Type')
                    ->options([
                        'FO-GSM' => 'FO-GSM',
                        'SINGLE-GSM' => 'Single-GSM',
                        'DUAL-GSM' => 'Dual-GSM',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
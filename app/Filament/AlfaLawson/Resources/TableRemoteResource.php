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

class TableRemoteResource extends Resource
{
    protected static ?string $model = TableRemote::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
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
                                'Alfa' => 'Alfamart',
                                'Lawson' => 'Lawson',
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
            ->columns([
                Tables\Columns\TextColumn::make('Site_ID')
                    ->label('Site ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy Site ID')
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('Nama_Toko')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('DC')
                    ->label('Distribution Center')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        // 'DC1' => 'success',
                        // 'DC2' => 'warning',
                        // 'DC3' => 'info',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('IP_Address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy IP Address'),

                Tables\Columns\TextColumn::make('Vlan')
                    ->label('VLAN')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('Controller')
                    ->label('Controller')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('Link')
                    ->label('Connection Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FO' => 'success',
                        'GSM' => 'info',
                        'DUAL-GSM' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('Status')
                    ->label('Status')
                    ->badge(),
                    

                Tables\Columns\TextColumn::make('Online_Date')
                    ->label('Online Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('Site_ID', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('DC')
                    ->label('Distribution Center'),
                    // ->options([
                    //     'DC1' => 'Marunda',
                    //     'DC2' => 'Cikarang',
                    //     'DC3' => 'Sentul',
                    // ]),

                Tables\Filters\SelectFilter::make('Status')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('Link')
                    ->label('Connection Type')
                    ->options([
                        'FO' => 'Fiber Optic',
                        'GSM' => 'Sim Card',
                        'DUAL-GSM'=> 'Dual Sim Card',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make()
                //     ->label('Delete Remote')
                //     ->icon('heroicon-o-trash')
                //     ->color('danger')
                //     ->requiresConfirmation()
                //     ->action(function (Table $table, $record) {
                //         $record->delete();
                //         Notification::make()
                //             ->title('Remote Connection Deleted')
                //             ->success()
                //             ->send();
                //     }),
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
            'view' => Pages\ViewTableRemote::route('/{record}'),  // Tambahkan ini
            'edit' => Pages\EditTableRemote::route('/{record}/edit'),
        ];
    }
}
<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TableFo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Style;

class TableFoExporter extends Exporter
{
    protected static ?string $model = TableFo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('CID')
                ->label('Connection ID')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Provider')
                ->label('Provider Name')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Register_Name')
                ->label('Register Name')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Site_ID')
                ->label('Site ID')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.Nama_Toko')
                ->label('Store Name')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.DC')
                ->label('Distribution Center')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.IP_Address')
                ->label('IP Address')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.Vlan')
                ->label('VLAN')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.Link')
                ->label('Connection Type')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.Customer')
                ->label('Customer')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Status')
                ->label('Connection Status')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s') : '-'),

            ExportColumn::make('updated_at')
                ->label('Last Updated')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s') : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your fiber optic connections export has completed and ' . 
                number_format($export->successful_rows) . ' ' . 
                str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . 
                    str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    // Add styling for the Excel file
    public function getExcelStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setShouldWrapText(false);
    }
}
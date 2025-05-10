<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TableRemote;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Style;

class TableRemoteExporter extends Exporter
{
    protected static ?string $model = TableRemote::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('Site_ID')
                ->label('Site ID')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Nama_Toko')
                ->label('Nama Toko')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('DC')
                ->label('Distribution Center')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('IP_Address')
                ->label('IP Address')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Vlan')
                ->label('VLAN')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Controller')
                ->label('Controller')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Customer')
                ->label('Customer')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Online_Date')
                ->label('Online Date')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),
            ExportColumn::make('Link')
                ->label('Connection Type')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            ExportColumn::make('Keterangan')
                ->label('Remarks')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your table remote export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    // Optional: Add styling for the Excel file
    public function getExcelStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setShouldWrapText(false);
    }
}
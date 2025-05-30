<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TablePeplink;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Style;

class TablePeplinkExporter extends Exporter
{
    protected static ?string $model = TablePeplink::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('SN')
                ->label('Serial Number')
                ->formatStateUsing(function ($state) {
                    // Ensure the SN is formatted with dashes
                    if (strlen($state) === 12) {
                        return substr($state, 0, 4) . '-' . substr($state, 4, 4) . '-' . substr($state, 8, 4);
                    }
                    return $state ?? '-';
                }),

            ExportColumn::make('Model')
                ->label('Model')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Kepemilikan')
                ->label('Ownership')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('tgl_beli')
                ->label('Purchase Date')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),

            ExportColumn::make('garansi')
                ->label('Warranty')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Site_ID')
                ->label('Site ID')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('remote.Nama_Toko')
                ->label('Store Name')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state ?? '-'),

            ExportColumn::make('Deskripsi')
                ->label('Description')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your Peplink devices export has completed and ' . 
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
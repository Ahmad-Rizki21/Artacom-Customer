<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TableRemote;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Log;

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
                ->formatStateUsing(fn ($state) => strtoupper($state ?? '-')),

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
                ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y') : '-'),

            ExportColumn::make('Link')
                ->label('Connection Type')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'FO-GSM' => '✅ FO-GSM',
                    'SINGLE-GSM' => '🔵 SINGLE-GSM',
                    'DUAL-GSM' => '🟡 DUAL-GSM',
                    default => $state ?? '-'
                }),

            ExportColumn::make('Status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => '✓ ' . ($state ?? '-')),

            ExportColumn::make('Keterangan')
                ->label('Remarks')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your remote connections export has completed and ' . 
                number_format($export->successful_rows) . ' ' . 
                str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . 
                    str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getExcelStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(12)
            ->setShouldWrapText(true);
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('status')
                ->label('Filter by Status')
                ->options([
                    'OPERATIONAL' => 'Operational',
                    'DISMANTLED' => 'Dismantled',
                ])
                ->multiple()
                ->default(['OPERATIONAL', 'DISMANTLED'])
                ->helperText('Select statuses to include in the export.'),
        ];
    }

    public function export(): Writer
    {
        $writer = new Writer();
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
        $writer->openToFile($tempFile);

        // Define header style for notes and legend
        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setFontColor('000000') // Teks hitam
            ->setShouldWrapText(true)
            ->setCellAlignment(CellAlignment::LEFT);

        // Add notes and legend (using multiple rows with the same style)
        $writer->addRow(Row::fromValues(['Report Generated: ' . now()->format('d/m/Y H:i')], $headerStyle));
        $writer->addRow(Row::fromValues(['Note: This report contains remote connection details filtered by user selection.'], $headerStyle));
        $writer->addRow(Row::fromValues(['Legend:'], $headerStyle));
        $writer->addRow(Row::fromValues(['✅ FO-GSM = Fiber Optic with GSM (Primary Connection)'], $headerStyle));
        $writer->addRow(Row::fromValues(['🔵 SINGLE-GSM = Single GSM (Secondary/Backup Connection)'], $headerStyle));
        $writer->addRow(Row::fromValues(['🟡 DUAL-GSM = Dual GSM (Redundant Connection)'], $headerStyle));
        $writer->addRow(Row::fromValues(['✓ OPERATIONAL = Active Status'], $headerStyle));
        $writer->addRow(Row::fromValues(['✓ DISMANTLED = Decommissioned Status'], $headerStyle));

        // Add header
        $headerDataStyle = (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setFontColor('FFFFFF') // Teks putih
            ->setBackgroundColor('006400') // Latar belakang hijau tua
            ->setShouldWrapText(true)
            ->setCellAlignment(CellAlignment::CENTER);

        $headings = static::getColumns()->map(fn (ExportColumn $column) => $column->getLabel())->all();
        $writer->addRow(Row::fromValues($headings, $headerDataStyle));

        // Add data
        $dataStyle = (new Style())
            ->setFontSize(12)
            ->setShouldWrapText(false)
            ->setCellAlignment(CellAlignment::LEFT);

        $query = $this->getQuery();
        foreach ($query->cursor() as $record) {
            $rowData = static::getColumns()->map(
                fn (ExportColumn $column) => $column->getFormattedState($record)
            )->all();
            $writer->addRow(Row::fromValues($rowData, $dataStyle));
        }

        $writer->close();
        return $writer;
    }
}
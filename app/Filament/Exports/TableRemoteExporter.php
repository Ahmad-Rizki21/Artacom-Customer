<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TableRemote;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TableRemoteExporter extends Exporter
{
    protected static ?string $model = TableRemote::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('Site_ID')->label('Site ID'),
            ExportColumn::make('Nama_Toko')->label('Nama Toko'),
            ExportColumn::make('DC')->label('Distribution Center'),
            ExportColumn::make('IP_Address')->label('IP Address'),
            ExportColumn::make('Vlan')->label('VLAN'),
            ExportColumn::make('Controller')->label('Controller'),
            ExportColumn::make('Customer')->label('Customer'),
            ExportColumn::make('Online_Date')->label('Online Date')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : '-'),
            ExportColumn::make('Link')->label('Connection Type'),
            ExportColumn::make('Status')->label('Status'),
            ExportColumn::make('Keterangan')->label('Remarks'),
        ];
    }

    public static function export(Export $export): void
    {
        $query = static::getFilteredQuery($export);
        $data = $query->get();

        $export->file(
            Excel::raw(new TableRemoteExcelExport($data), \Maatwebsite\Excel\Excel::XLSX)
        );
    }

    protected static function getFilteredQuery(Export $export)
    {
        $query = TableRemote::query();

        $filters = $export->getFilters();

        if (isset($filters['Status']) && !empty($filters['Status']['value'])) {
            $query->whereIn('Status', (array)$filters['Status']['value']);
        }

        if (isset($filters['DC']) && !empty($filters['DC']['value'])) {
            $query->whereIn('DC', (array)$filters['DC']['value']);
        }

        if (isset($filters['Customer']) && !empty($filters['Customer']['value'])) {
            $query->whereIn('Customer', (array)$filters['Customer']['value']);
        }

        if (isset($filters['Link']) && !empty($filters['Link']['value'])) {
            $query->whereIn('Link', (array)$filters['Link']['value']);
        }

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = $export->getRecordsCount();
        return "{$count} records exported successfully!";
    }
}

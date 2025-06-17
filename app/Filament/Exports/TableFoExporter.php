<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\TableFo;
use App\Models\AlfaLawson\TableRemote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\ExportColumn;

class TableFoExporter implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = TableFo::query()
            ->with('remote')
            ->whereHas('remote', function (Builder $query) {
                $query->where('Link', 'FO-GSM');
            });

        // Terapkan filter yang diterima
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter => $value) {
                if (!empty($value)) {
                    switch ($filter) {
                        case 'DC':
                            $query->whereHas('remote', function (Builder $query) use ($value) {
                                $query->whereIn('DC', (array) $value);
                            });
                            break;
                        case 'remote.Customer':
                            $query->whereHas('remote', function (Builder $query) use ($value) {
                                $query->whereIn('Customer', (array) $value);
                            });
                            break;
                        case 'Status':
                            $query->whereIn('Status', (array) $value);
                            break;
                        case 'Provider':
                            $query->whereIn('Provider', (array) $value);
                            break;
                    }
                }
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Connection ID',
            'Provider',
            'Register Name',
            'Location',
            'Site ID',
            'Distribution Center',
            'Status',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->CID ?? '-',
            $row->Provider ?? '-',
            $row->Register_Name ?? '-',
            $row->remote->Nama_Toko ?? '-',
            $row->Site_ID ?? '-',
            $row->remote->DC ?? '-',
            $row->Status ?? '-',
            $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i:s') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    // Kosongkan untuk menghindari formulir opsi
    public static function getOptionsFormComponents(): array
    {
        return [];
    }

    // Placeholder untuk memenuhi ekspektasi Filament
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('CID')->label('Connection ID'),
            ExportColumn::make('Provider')->label('Provider'),
            ExportColumn::make('Register_Name')->label('Register Name'),
            ExportColumn::make('remote.Nama_Toko')->label('Location'),
            ExportColumn::make('Site_ID')->label('Site ID'),
            ExportColumn::make('remote.DC')->label('Distribution Center'),
            ExportColumn::make('Status')->label('Status'),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }
}
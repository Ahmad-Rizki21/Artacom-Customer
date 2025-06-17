<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\AlfaLawson\TableSimcard;
use Carbon\Carbon;

class TableSimcardExcelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Nomor SIM',
            'Provider',
            'Lokasi Toko',
            'Nomor Seri Kartu (SN Card)',
            'Status',
            'Catatan',
        ];
    }

    public function map($record): array
    {
        return [
            $record->Sim_Number ?? '-',
            $record->Provider ?? '-',
            $record->Site_ID ?? '-',
            $record->SN_Card ?? 'Tidak ada SN',
            $record->Status ?? '-',
            $record->Informasi_Tambahan ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3490DC'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'A:F' => [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Nomor SIM
            'B' => 20, // Provider
            'C' => 20, // Lokasi Toko
            'D' => 25, // Nomor Seri Kartu (SN Card)
            'E' => 15, // Status
            'F' => 40, // Catatan
        ];
    }
}

<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\AlfaLawson\Maipu;
use Carbon\Carbon;

class MaipuExcelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
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
            'Serial Number',
            'Model',
            'Ownership',
            'Purchase Date',
            'Warranty',
            'Site ID',
            'Status',
            'Description',
        ];
    }

    public function map($record): array
    {
        return [
            $record->SN ?? '-',
            $record->Model ?? '-',
            $record->Kepemilikan ?? '-',
            $record->tgl_beli ? Carbon::parse($record->tgl_beli)->format('d/m/Y') : '-',
            $record->garansi ?? '-',
            $record->Site_ID ?? '-',
            $record->Status ?? '-',
            $record->Deskripsi ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3490DC']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
            'A:H' => [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Serial Number
            'B' => 25, // Model
            'C' => 15, // Ownership
            'D' => 15, // Purchase Date
            'E' => 15, // Warranty
            'F' => 15, // Site ID
            'G' => 20, // Status
            'H' => 30, // Description
        ];
    }
}

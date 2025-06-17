<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\AlfaLawson\TableFo;

class TableFoExcelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
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

    public function map($record): array
    {
        return [
            $record->CID ?? '-',
            $record->Provider ?? '-',
            $record->Register_Name ?? '-',
            $record->remote->Nama_Toko ?? '-',
            $record->Site_ID ?? '-',
            $record->remote->DC ?? '-',
            $record->Status ?? '-',
            $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i:s') : '-',
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
            'A' => 15, // Connection ID
            'B' => 20, // Provider
            'C' => 25, // Register Name
            'D' => 30, // Location
            'E' => 15, // Site ID
            'F' => 20, // Distribution Center
            'G' => 15, // Status
            'H' => 20, // Created At
        ];
    }
}
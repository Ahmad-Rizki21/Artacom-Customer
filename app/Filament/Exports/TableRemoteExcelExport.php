<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\AlfaLawson\TableRemote;

class TableRemoteExcelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
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
            'Site ID',
            'Nama Toko',
            'Distribution Center', 
            'IP Address',
            'VLAN',
            'Controller',
            'Customer',
            'Online Date',
            'Connection Type',
            'Status',
            'Remarks'
        ];
    }

    public function map($record): array
    {
        return [
            $record->Site_ID,
            $record->Nama_Toko,
            $record->DC,
            $record->IP_Address,
            $record->Vlan,
            $record->Controller,
            $record->Customer,
            $record->Online_Date,
            $record->Link,
            $record->Status,
            $record->Keterangan
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
            'A:K' => [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Site ID
            'B' => 30, // Nama Toko
            'C' => 25, // DC
            'D' => 18, // IP Address
            'E' => 10, // VLAN
            'F' => 20, // Controller
            'G' => 15, // Customer
            'H' => 15, // Online Date
            'I' => 18, // Connection Type
            'J' => 12, // Status
            'K' => 40  // Remarks
        ];
    }
}

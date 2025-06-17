<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class TableRemoteExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'SITE ID',
            'NAMA TOKO',
            'DISTRIBUTION CENTER',
            'IP ADDRESS',
            'VLAN',
            'CONTROLLER',
            'CUSTOMER',
            'ONLINE DATE',
            'CONNECTION TYPE',
            'STATUS',
            'REMARKS'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header Style
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => Color::COLOR_WHITE],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2A629A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Data Style
        $sheet->getStyle('A2:K'.$sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Column Specific Alignment
        $sheet->getStyle('A2:A'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Wrap Text for Remarks
        $sheet->getStyle('K2:K'.$sheet->getHighestRow())->getAlignment()->setWrapText(true);
        
        // Alternate Row Color
        foreach(range(2, $sheet->getHighestRow()) as $row) {
            if($row % 2 == 0) {
                $sheet->getStyle('A'.$row.':K'.$row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F5F9FF');
            }
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // SITE ID
            'B' => 25,  // NAMA TOKO
            'C' => 22,  // DISTRIBUTION CENTER
            'D' => 15,  // IP ADDRESS
            'E' => 10,  // VLAN
            'F' => 18,  // CONTROLLER
            'G' => 15,  // CUSTOMER
            'H' => 15,  // ONLINE DATE
            'I' => 18,  // CONNECTION TYPE
            'J' => 12,  // STATUS
            'K' => 35   // REMARKS
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Auto Size Columns
                foreach (range('A', 'J') as $col) {
                    $event->sheet->getColumnDimension($col)
                        ->setAutoSize(true);
                }
                
                // Freeze Header Row
                $event->sheet->freezePane('A2');
                
                // Auto Filter
                $event->sheet->setAutoFilter('A1:K1');
                
                // Set Print Setup
                $event->sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1);
            },
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\AlfaLawson\TableRemote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TableRemoteExporter implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        return TableRemote::all()->map(function ($row) {
            return [
                'Site ID' => $row->Site_ID ?? '-',
                'Nama Toko' => strtoupper($row->Nama_Toko ?? '-'),
                'Distribution Center' => $row->DC ?? '-',
                'IP Address' => $row->IP_Address ?? '-',
                'VLAN' => $row->Vlan ?? '-',
                'Controller' => $row->Controller ?? '-',
                'Customer' => $row->Customer ?? '-',
                'Online Date' => $row->Online_Date ? \Carbon\Carbon::parse($row->Online_Date)->format('d/m/Y') : '-',
                'Connection Type' => match ($row->Link) {
                    'FO-GSM' => '✅ FO-GSM',
                    'SINGLE-GSM' => '🔵 SINGLE-GSM',
                    'DUAL-GSM' => '🟡 DUAL-GSM',
                    default => $row->Link ?? '-'
                },
                'Status' => '✓ ' . ($row->Status ?? '-'),
                'Remarks' => $row->Keterangan ?? '-',
            ];
        });
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
            'Remarks',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style for header
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006400']], // Dark green
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Style for all data rows
            '2:' . $sheet->getHighestRow() => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate(); // Get the PhpSpreadsheet Worksheet
                $highestRow = $sheet->getHighestRow();

                // Apply alternating row colors
                for ($row = 2; $row <= $highestRow; $row++) {
                    $fillColor = ($row % 2 === 0) ? 'DFECDB' : 'FFFFFF'; // Light green for even, white for odd
                    if (strpos($sheet->getCell("I{$row}")->getValue(), 'FO-GSM') !== false) {
                        $fillColor = 'FFF5D7'; // Light yellow for FO-GSM
                    }
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fillColor);
                }

                // Add chart data (count of remotes per DC)
                $chartData = TableRemote::select('DC', DB::raw('COUNT(*) as count'))
                    ->groupBy('DC')
                    ->get()
                    ->map(function ($item) {
                        return [$item->DC ?? 'Unknown', $item->count];
                    })
                    ->toArray();

                // Log the chart data for debugging
                Log::info('Chart Data', ['data' => $chartData]);

                // Add chart data to sheet (start after data table)
                $chartStartRow = $highestRow + 3;
                $sheet->setCellValue('A' . $chartStartRow, 'Distribution Center');
                $sheet->setCellValue('B' . $chartStartRow, 'Number of Remotes');

                // Write chart data to sheet for visual purposes
                $chartEndRow = $chartStartRow + count($chartData);
                foreach ($chartData as $index => $data) {
                    $row = $chartStartRow + 1 + $index;
                    $sheet->setCellValueExplicit('A' . $row, $data[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('B' . $row, $data[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                }

                // Apply style to chart data
                $sheet->getStyle('A' . $chartStartRow . ':B' . $chartEndRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Extract categories and values directly from chartData
                $categories = array_column($chartData, 0); // Extract Distribution Centers
                $values = array_column($chartData, 1); // Extract Number of Remotes

                // Create chart with static data
                $dataSeriesLabels = [
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Number of Remotes']),
                ];
                $xAxisTickValues = [
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, count($categories), $categories),
                ];
                $dataSeriesValues = [
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, null, null, count($values), $values),
                ];

                $series = new DataSeries(
                    DataSeries::TYPE_BARCHART,
                    DataSeries::GROUPING_STANDARD,
                    range(0, count($dataSeriesValues) - 1),
                    $dataSeriesLabels,
                    $xAxisTickValues,
                    $dataSeriesValues
                );

                $plotArea = new PlotArea(null, [$series]);
                $legend = new Legend(Legend::POSITION_RIGHT, null, false);
                $title = new Title('Number of Remotes per Distribution Center');

                $chart = new Chart(
                    'RemotePerDC',
                    $title,
                    $legend,
                    $plotArea
                );

                $chart->setTopLeftPosition('D' . ($chartStartRow + 2));
                $chart->setBottomRightPosition('J' . ($chartStartRow + 15));

                $sheet->addChart($chart);
            },
        ];
    }
}
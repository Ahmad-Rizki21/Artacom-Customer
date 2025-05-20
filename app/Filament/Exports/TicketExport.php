<?php

namespace App\Filament\Exports;

use App\Models\AlfaLawson\Ticket;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Facades\Log;

class TicketExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths, 
    WithEvents,
    WithCustomStartCell
{
    protected $tickets;

    public function __construct(?Collection $tickets = null)
    {
        $this->tickets = $tickets ?? Ticket::all();
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'No Ticket',
            'Customer',
            'Site ID',
            'Alamat',
            'IP Address',
            'Kategori Pelaporan',
            'Problem Summary',
            'Status Ticket',
            'Open Date',
            'Pending Date',
            'Closed Date',
            'Action Description',
            'Open Clock',
            'Total Pending',
            'Total Duration',
            'Downtime',
        ];
    }

    public function map($ticket): array
    {
        $remoteData = $ticket->remote ?? null;
        $siteId = $remoteData ? $remoteData->Site_ID : '-';
        $alamat = $remoteData ? $remoteData->Nama_Toko : '-';
        $ipAddress = $remoteData ? $remoteData->IP_Address : '-';

        $latestAction = TicketAction::where('No_Ticket', $ticket->No_Ticket)
            ->orderBy('Action_Time', 'desc')
            ->first();

        // Log the raw timestamps to debug
        Log::info("Ticket {$ticket->No_Ticket} Timestamps", [
            'Open_Time' => $ticket->Open_Time ? $ticket->Open_Time->toDateTimeString() : null,
            'Pending_Start' => $ticket->Pending_Start ? $ticket->Pending_Start->toDateTimeString() : null,
            'Pending_Stop' => $ticket->Pending_Stop ? $ticket->Pending_Stop->toDateTimeString() : null,
            'Closed_Time' => $ticket->Closed_Time ? $ticket->Closed_Time->toDateTimeString() : null,
        ]);

        // Log the calculated durations
        Log::info("Ticket {$ticket->No_Ticket} Durations", [
            'open_duration' => $ticket->open_duration,
            'pending_duration' => $ticket->pending_duration,
            'total_duration' => $ticket->total_duration,
        ]);

        $totalDurationSeconds = $this->parseDurationToSeconds($ticket->total_duration);
        $totalPendingSeconds = $this->parseDurationToSeconds($ticket->pending_duration);
        $downtimeSeconds = max(0, $totalDurationSeconds - $totalPendingSeconds);
        $downtime = $this->formatDuration($downtimeSeconds);

        Log::info("Ticket {$ticket->No_Ticket} Downtime Calculation", [
            'total_duration_seconds' => $totalDurationSeconds,
            'total_pending_seconds' => $totalPendingSeconds,
            'downtime_seconds' => $downtimeSeconds,
            'downtime' => $downtime,
        ]);

        return [
            $ticket->No_Ticket ?? '-',
            $ticket->Customer ?? '-',
            $siteId,
            $alamat,
            $ipAddress,
            $ticket->Catagory ?? '-',
            $ticket->Problem_Summary ?? '-',
            $ticket->Status ?? '-',
            optional($ticket->Open_Time)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Pending_Start)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Closed_Time)->format('d/m/Y H:i:s') ?? '-',
            $latestAction->Action_Description ?? '-',
            $ticket->open_duration ?? '00:00:00', // Open Clock
            $ticket->pending_duration ?? '00:00:00', // Total Pending
            $ticket->total_duration ?? '00:00:00', // Total Duration
            $downtime, // Downtime = Total Duration - Pending Duration
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // No Ticket
            'B' => 20, // Customer
            'C' => 15, // Site ID
            'D' => 30, // Alamat
            'E' => 15, // IP Address
            'F' => 20, // Kategori Pelaporan
            'G' => 30, // Problem Summary
            'H' => 15, // Status Ticket
            'I' => 20, // Open Date
            'J' => 20, // Pending Date
            'K' => 20, // Closed Date
            'L' => 30, // Action Description
            'M' => 15, // Open Clock
            'N' => 15, // Total Pending
            'O' => 15, // Total Duration
            'P' => 15, // Downtime
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'Laporan Ticket Problem PT. Artacomindo');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FF000000'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Periode: ' . now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FF000000'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);

        $sheet->getStyle('A4:P4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4A90E2'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(30);

        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A5:P{$highestRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        for ($row = 5; $row <= $highestRow; $row++) {
            if (($row % 2) === 0) {
                $sheet->getStyle("A{$row}:P{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F2F2'],
                    ],
                ]);
            }

            $status = $sheet->getCell("H{$row}")->getValue();
            $color = match ($status) {
                'CLOSED' => new Color('FF00FF00'),
                'OPEN' => new Color('FF0000FF'),
                'PENDING' => new Color('FFFFFF00'),
                default => new Color('FF000000'),
            };
            $sheet->getStyle("H{$row}")->getFont()->setColor($color);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->freezePane('A5');
            },
        ];
    }

    private function parseDurationToSeconds(string $duration): int
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $duration));
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    private function formatDuration(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }
}
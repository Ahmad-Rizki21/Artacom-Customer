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

        // Parse durations to seconds using the safe parsing function
        // Use null coalescing operator (??) to provide default value if property is null
        $openDurationSeconds = $this->parseDurationToSeconds($ticket->open_duration ?? '00:00:00');
        $totalPendingSeconds = $this->parseDurationToSeconds($ticket->pending_duration ?? '00:00:00');
        $totalDurationSeconds = $this->parseDurationToSeconds($ticket->total_duration ?? '00:00:00');

        // Calculate downtime in seconds, ensuring it's not negative
        $downtimeSeconds = max(0, $totalDurationSeconds - $totalPendingSeconds);

        // Format all durations back to HH:MM:SS
        $openDurationFormatted = $this->formatDuration($openDurationSeconds);
        $totalPendingFormatted = $this->formatDuration($totalPendingSeconds);
        $totalDurationFormatted = $this->formatDuration($totalDurationSeconds);
        $downtimeFormatted = $this->formatDuration($downtimeSeconds);

        // Log for debugging (optional but good practice)
        Log::info("Ticket {$ticket->No_Ticket} Export Durations", [
            'open_duration_raw' => $ticket->open_duration,
            'pending_duration_raw' => $ticket->pending_duration,
            'total_duration_raw' => $ticket->total_duration,
            'open_seconds' => $openDurationSeconds,
            'pending_seconds' => $totalPendingSeconds,
            'total_seconds' => $totalDurationSeconds,
            'downtime_seconds' => $downtimeSeconds,
            'open_formatted' => $openDurationFormatted,
            'pending_formatted' => $totalPendingFormatted,
            'total_formatted' => $totalDurationFormatted,
            'downtime_formatted' => $downtimeFormatted,
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
            optional($ticket->Pending_Start)->format('d/m/Y H:i:s') ?? '-', // Note: This is Pending Start Date, not duration
            optional($ticket->Closed_Time)->format('d/m/Y H:i:s') ?? '-',
            $latestAction->Action_Description ?? '-',
            $openDurationFormatted, // Open Clock (Formatted)
            $totalPendingFormatted, // Total Pending (Formatted)
            $totalDurationFormatted, // Total Duration (Formatted)
            $downtimeFormatted, // Downtime (Formatted)
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

    private function parseDurationToSeconds(string $duration = '00:00:00'): int
    {
        // Validasi format dan berikan nilai default jika tidak valid
        if (empty($duration) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
            // Coba parsing jika formatnya hanya detik (misal dari model)
            if (is_numeric($duration)) {
                return (int) $duration;
            }
            Log::warning("Invalid duration format encountered: {$duration}. Defaulting to 0 seconds.");
            return 0; // Return 0 seconds if format is invalid and not numeric
        }
        
        // Sekarang aman untuk melakukan explode karena format sudah dipastikan valid
        $parts = explode(':', $duration);
        
        // Pastikan array memiliki 3 elemen (jam, menit, detik)
        if (count($parts) !== 3) {
             Log::warning("Exploded duration does not have 3 parts: {$duration}. Defaulting to 0 seconds.");
            return 0; // Return 0 jika format tidak sesuai setelah explode
        }
        
        // Konversi ke integer dan hitung total detik
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];
        $seconds = (int) $parts[2];
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }


    private function formatDuration(int $seconds): string
    {
        if ($seconds < 0) {
             Log::warning("Negative duration encountered: {$seconds} seconds. Formatting as 00:00:00.");
             $seconds = 0;
        }
        return sprintf('%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }
}


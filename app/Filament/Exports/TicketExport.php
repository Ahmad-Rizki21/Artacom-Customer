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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
    protected $includeHeader = true;
    protected $filterStatus = null;
    protected $dateFrom = null;
    protected $dateTo = null;
    protected $title = 'Laporan Ticket Problem PT. Artacomindo';
    protected $subtitle = null;

    public function __construct(?Collection $tickets = null, $options = [])
    {
        $this->tickets = $tickets ?? Ticket::all();
        
        $this->includeHeader = $options['includeHeader'] ?? true;
        $this->filterStatus = $options['filterStatus'] ?? null;
        $this->dateFrom = $options['dateFrom'] ?? null;
        $this->dateTo = $options['dateTo'] ?? null;
        $this->title = $options['title'] ?? $this->title;
        $this->subtitle = $options['subtitle'] ?? null;
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
            'Open Level',
            'Escalation Level',
            'Open Date',
            'Pending Date',
            'Closed Date',
            'Pending Reason',
            'Action Description',
            'PIC',
            'Reported By',
            'Open Clock',
            'Total Pending',
            'Total Duration',
            'Downtime',
            'Classification Problem',
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

        $allPendingActions = TicketAction::where('No_Ticket', $ticket->No_Ticket)
            ->where('Action_Taken', 'Pending Clock')
            ->orderBy('Action_Time', 'desc')
            ->get();
        
        $latestPendingAction = $allPendingActions->first();
        $pendingReason = $latestPendingAction ? $latestPendingAction->Action_Description : $ticket->Pending_Reason ?? '-';

        if (strlen($pendingReason) > 150) {
            $pendingReason = substr($pendingReason, 0, 147) . '...';
        }

        $currentEscalation = $ticket->Current_Escalation_Level ?? '-';
        $escalationLevel = '-';
        $escalationTitle = '-';
        
        if ($currentEscalation === '-' || empty($currentEscalation)) {
            try {
                $escalationAction = TicketAction::where('No_Ticket', $ticket->No_Ticket)
                    ->where('Action_Taken', 'Escalation')
                    ->orderBy('Action_Time', 'desc')
                    ->first();
                
                if ($escalationAction) {
                    if (strpos($escalationAction->Action_Description, 'To: ') !== false) {
                        preg_match('/To: (.+)/', $escalationAction->Action_Description, $matches);
                        if (isset($matches[1])) {
                            $currentEscalation = $matches[1];
                        }
                    } else {
                        $currentEscalation = $escalationAction->Escalation_Target_Level ?? $currentEscalation;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error getting escalation info for ticket {$ticket->No_Ticket}: " . $e->getMessage());
            }
        }
        
        if ($currentEscalation !== '-' && !empty($currentEscalation)) {
            switch (strtolower(trim($currentEscalation))) {
                case 'management':
                    $escalationLevel = 'Level 6';
                    $escalationTitle = 'Management';
                    $currentEscalation = 'Level 6 - Management';
                    break;
                case 'high-admin':
                case 'high admin':
                    $escalationLevel = 'Level 2';
                    $escalationTitle = 'SPV NOC';
                    $currentEscalation = 'Level 2 - SPV NOC';
                    break;
                case 'noc':
                    $escalationLevel = 'Level 1';
                    $escalationTitle = 'NOC';
                    $currentEscalation = 'Level 1 - NOC';
                    break;
                case 'teknisi':
                    $escalationLevel = 'Level 3';
                    $escalationTitle = 'Teknisi';
                    $currentEscalation = 'Level 3 - Teknisi';
                    break;
                case 'spv teknisi':
                    $escalationLevel = 'Level 4';
                    $escalationTitle = 'SPV Teknisi';
                    $currentEscalation = 'Level 4 - SPV Teknisi';
                    break;
                case 'engineer':
                    $escalationLevel = 'Level 5';
                    $escalationTitle = 'Engineer';
                    $currentEscalation = 'Level 5 - Engineer';
                    break;
                default:
                    if (strpos($currentEscalation, ' - ') !== false) {
                        list($escalationLevel, $escalationTitle) = explode(' - ', $currentEscalation, 2);
                    } else if (preg_match('/level\s*(\d+)/i', $currentEscalation, $matches)) {
                        $escalationLevel = 'Level ' . $matches[1];
                        $escalationTitle = $this->getLevelTitle($matches[1]);
                        $currentEscalation = $escalationLevel . ' - ' . $escalationTitle;
                    } else {
                        $level = $this->getLevelFromRole($currentEscalation);
                        if ($level > 0) {
                            $escalationLevel = 'Level ' . $level;
                            $escalationTitle = $currentEscalation;
                            $currentEscalation = $escalationLevel . ' - ' . $escalationTitle;
                        } else {
                            $escalationLevel = 'Level';
                            $escalationTitle = $currentEscalation;
                            $currentEscalation = 'Level - ' . $currentEscalation;
                        }
                    }
                    break;
            }
        }

        $openDurationSeconds = $ticket->open_duration_seconds ?? 0;
        $totalPendingSeconds = $ticket->pending_duration_seconds ?? 0;
        $totalDurationSeconds = $ticket->total_duration_seconds ?? 0;
        
        if ($openDurationSeconds == 0 && $totalPendingSeconds == 0 && $totalDurationSeconds == 0) {
            $timer = $ticket->getCurrentTimer();
            $openDurationSeconds = $timer['open']['seconds'] ?? 0;
            $totalPendingSeconds = $timer['pending']['seconds'] ?? 0;
            $totalDurationSeconds = $timer['total']['seconds'] ?? 0;
            
            if ($openDurationSeconds == 0 && $ticket->open_duration) {
                $openDurationSeconds = $this->parseDurationToSeconds($ticket->open_duration);
            }
            
            if ($totalPendingSeconds == 0 && $ticket->pending_duration) {
                $totalPendingSeconds = $this->parseDurationToSeconds($ticket->pending_duration);
            }
            
            if ($totalDurationSeconds == 0 && $ticket->total_duration) {
                $totalDurationSeconds = $this->parseDurationToSeconds($ticket->total_duration);
            }
        }

        $downtimeSeconds = max(0, $totalDurationSeconds - $totalPendingSeconds);
        
        $openDurationFormatted = $this->formatDuration($openDurationSeconds);
        $totalPendingFormatted = $this->formatDuration($totalPendingSeconds);
        $totalDurationFormatted = $this->formatDuration($totalDurationSeconds);
        $downtimeFormatted = $this->formatDuration($downtimeSeconds);

        return [
            $ticket->No_Ticket ?? '-',
            $ticket->Customer ?? '-',
            $siteId,
            $alamat,
            $ipAddress,
            $ticket->Catagory ?? '-',
            $ticket->Problem_Summary ?? '-',
            $ticket->Status ?? '-',
            $ticket->Open_Level ?? '-',
            $currentEscalation,
            optional($ticket->Open_Time)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Pending_Start)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Closed_Time)->format('d/m/Y H:i:s') ?? '-',
            $pendingReason,
            $latestAction->Action_Description ?? '-',
            $ticket->Pic ?? '-',
            $ticket->Reported_By ?? '-',
            $openDurationFormatted,
            $totalPendingFormatted,
            $totalDurationFormatted,
            $downtimeFormatted,
            $ticket->Classification ?? '-',
        ];
    }

    public function columnWidths(): array
{
    return [
        'A' => 18, // No Ticket
        'B' => 22, // Customer
        'C' => 18, // Site ID
        'D' => 35, // Alamat
        'E' => 18, // IP Address
        'F' => 22, // Kategori Pelaporan
        'G' => 40, // Problem Summary (increased for more space)
        'H' => 18, // Status Ticket
        'I' => 18, // Open Level
        'J' => 25, // Escalation Level (increased for longer text)
        'K' => 22, // Open Date
        'L' => 22, // Pending Date
        'M' => 22, // Closed Date
        'N' => 40, // Pending Reason (increased for more space)
        'O' => 50, // Action Description (increased for detailed content)
        'P' => 18, // PIC
        'Q' => 20, // Classification
        'R' => 18, // Reported By
        'S' => 18, // Open Clock
        'T' => 18, // Total Pending
        'U' => 18, // Total Duration
        'V' => 18, // Downtime
    ];
}

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
{
    $lastColumn = 'V';
    $highestRow = $sheet->getHighestRow();

    // Title (Row 1)
    $sheet->mergeCells("A1:{$lastColumn}1");
    $sheet->setCellValue('A1', $this->title);
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['argb' => 'FFFFFFFF'], // White text
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF1E3A8A'], // Dark blue background
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FFFFFFFF'], // White border
            ],
        ],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(35);

    // Subtitle/Period (Row 2)
    $sheet->mergeCells("A2:{$lastColumn}2");
    $periodText = 'Periode: ' . Carbon::now()->format('d/m/Y H:i:s');
    if ($this->subtitle) {
        $periodText = $this->subtitle;
    } elseif ($this->dateFrom && $this->dateTo) {
        $periodText = 'Periode: ' . Carbon::parse($this->dateFrom)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->dateTo)->format('d/m/Y');
    }
    $sheet->setCellValue('A2', $periodText);
    $sheet->getStyle('A2')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['argb' => 'FF1E3A8A'], // Dark blue text
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFEFF6FF'], // Very light blue background
        ],
    ]);
    $sheet->getRowDimension(2)->setRowHeight(25);

    // Spacer Row (Row 3)
    $sheet->mergeCells("A3:{$lastColumn}3");
    $sheet->getRowDimension(3)->setRowHeight(10);

    // Header Row (Row 4)
    $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['argb' => 'FFFFFFFF'], // White text
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF3B82F6'], // Bright blue background
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFFFFFFF'], // White borders
            ],
        ],
    ]);
    $sheet->getRowDimension(4)->setRowHeight(40);

    // Data Rows (Row 5 onwards)
    $sheet->getStyle("A5:{$lastColumn}{$highestRow}")->applyFromArray([
        'font' => [
            'size' => 11,
            'color' => ['argb' => 'FF1F2937'], // Dark gray text
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFD1D5DB'], // Light gray borders
            ],
        ],
    ]);

    // Left-align specific columns for better readability
    $sheet->getStyle("G5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Problem Summary
    $sheet->getStyle("O5:O{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Action Description
    $sheet->getStyle("N5:N{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Pending Reason
    $sheet->getStyle("D5:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Alamat

    // Alternate row shading for better readability
    for ($row = 5; $row <= $highestRow; $row++) {
        $fillColor = ($row % 2 == 0) ? 'FFF9FAFB' : 'FFFFFFFF'; // Light gray for even rows, white for odd rows
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $fillColor],
            ],
        ]);

        // Status Column Styling (Column H)
        $status = $sheet->getCell("H{$row}")->getValue();
        $statusColor = match ($status) {
            'CLOSED' => 'FF16A34A', // Green
            'OPEN' => 'FF2563EB', // Blue
            'PENDING' => 'FFF59E0B', // Yellow
            default => 'FF6B7280', // Gray
        };
        $sheet->getStyle("H{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // White text
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $statusColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'], // Black border for emphasis
                ],
            ],
        ]);

        // Escalation Level Styling (Column J)
        $escalation = $sheet->getCell("J{$row}")->getValue();
        if ($escalation && $escalation !== '-') {
            $levelMatch = [];
            preg_match('/Level\s*(\d+)/i', $escalation, $levelMatch);
            $level = isset($levelMatch[1]) ? (int)$levelMatch[1] : 0;

            $escalationColor = match ($level) {
                1 => 'FF93C5FD', // Light Blue (NOC)
                2 => 'FF60A5FA', // Blue (SPV NOC)
                3 => 'FF86EFAC', // Light Green (Teknisi)
                4 => 'FF4ADE80', // Green (SPV Teknisi)
                5 => 'FFFED7AA', // Light Orange (Engineer)
                6 => 'FFFCA5A5', // Light Red (Management)
                default => 'FFE5E7EB', // Gray
            };

            if ($level === 0) {
                $escalationColor = match (true) {
                    str_contains(strtolower($escalation), 'noc') && !str_contains(strtolower($escalation), 'spv') => 'FF93C5FD',
                    str_contains(strtolower($escalation), 'spv noc') => 'FF60A5FA',
                    str_contains(strtolower($escalation), 'teknisi') && !str_contains(strtolower($escalation), 'spv') => 'FF86EFAC',
                    str_contains(strtolower($escalation), 'spv teknisi') => 'FF4ADE80',
                    str_contains(strtolower($escalation), 'engineer') => 'FFFED7AA',
                    str_contains(strtolower($escalation), 'management') => 'FFFCA5A5',
                    default => 'FFE5E7EB',
                };
            }

            $textColor = ($level >= 5 || str_contains(strtolower($escalation), 'engineer') || str_contains(strtolower($escalation), 'management')) ? 'FF000000' : 'FF000000'; // Black text for higher levels
            $sheet->getStyle("J{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => $textColor],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $escalationColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'], // Black border for emphasis
                    ],
                ],
            ]);
        }

        // Dynamically adjust row height for text-heavy columns
        foreach (['G', 'N', 'O'] as $col) { // Problem Summary, Pending Reason, Action Description
            $cellValue = $sheet->getCell("{$col}{$row}")->getValue();
            if ($cellValue && strlen($cellValue) > 0) {
                $lineCount = substr_count($cellValue, "\n") + 1;
                $charCount = strlen($cellValue);
                $rowHeight = max(20, min(100, $lineCount * 15 + ($charCount / 50)));
                $sheet->getRowDimension($row)->setRowHeight($rowHeight);
            }
        }
    }

    // Summary Row
    $summaryRow = $highestRow + 2;
    $sheet->mergeCells("A{$summaryRow}:G{$summaryRow}");
    $sheet->setCellValue("A{$summaryRow}", "Total Tickets: " . ($highestRow - 4));
    $sheet->getStyle("A{$summaryRow}:G{$summaryRow}")->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['argb' => 'FF1E3A8A'], // Dark blue text
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFEFF6FF'], // Very light blue background
        ],
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FF1E3A8A'], // Dark blue border
            ],
        ],
    ]);
    $sheet->getRowDimension($summaryRow)->setRowHeight(30);

    // Footer Row
    $footerRow = $summaryRow + 2;
    $sheet->mergeCells("A{$footerRow}:{$lastColumn}{$footerRow}");
    $sheet->setCellValue("A{$footerRow}", "Laporan dihasilkan pada: " . Carbon::now()->format('d/m/Y H:i:s') . " oleh PT. Artacomindo");
    $sheet->getStyle("A{$footerRow}:{$lastColumn}{$footerRow}")->applyFromArray([
        'font' => [
            'italic' => true,
            'size' => 10,
            'color' => ['argb' => 'FF6B7280'], // Gray text
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
    ]);
    $sheet->getRowDimension($footerRow)->setRowHeight(20);
}

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $event->sheet->freezePane('A5');

            foreach ($this->columnWidths() as $column => $minWidth) {
                $currentWidth = $event->sheet->getColumnDimension($column)->getWidth();
                $calculatedWidth = min(60, max($minWidth, $currentWidth));
                $event->sheet->getColumnDimension($column)->setWidth($calculatedWidth);
            }

            $event->sheet->getHeaderFooter()
                ->setOddHeader('&L&B' . $this->title . '&R&D')
                ->setOddFooter('&L&B' . $this->title . '&RPage &P of &N');

            $event->sheet->getPageSetup()
                ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                ->setFitToPage(true)
                ->setFitToWidth(1)
                ->setFitToHeight(0)
                ->setPrintArea("A1:{$event->sheet->getHighestColumn()}{$event->sheet->getHighestRow()}")
                ->setHorizontalCentered(true);

            $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);

            $event->sheet->getPageMargins()
                ->setTop(0.75)
                ->setBottom(0.75)
                ->setLeft(0.5)
                ->setRight(0.5);
        },
    ];
}

    private function getLevelTitle($level)
    {
        $level = (int)$level;
        return match ($level) {
            1 => 'NOC',
            2 => 'SPV NOC',
            3 => 'Teknisi',
            4 => 'SPV Teknisi',
            5 => 'Engineer',
            6 => 'Management',
            default => 'Staff',
        };
    }
    
    private function getLevelFromRole($role)
    {
        return match (strtolower(trim($role))) {
            'noc' => 1,
            'spv noc' => 2,
            'teknisi' => 3,
            'spv teknisi' => 4,
            'engineer' => 5,
            'management' => 6,
            default => 0,
        };
    }
    
    private function parseDurationToSeconds(string $duration = '00:00:00'): int
    {
        if (empty($duration) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
            if (is_numeric($duration)) {
                return (int) $duration;
            }
            Log::warning("Invalid duration format encountered: {$duration}. Defaulting to 0 seconds.");
            return 0;
        }
        
        $parts = explode(':', $duration);
        
        if (count($parts) !== 3) {
            Log::warning("Exploded duration does not have 3 parts: {$duration}. Defaulting to 0 seconds.");
            return 0;
        }
        
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
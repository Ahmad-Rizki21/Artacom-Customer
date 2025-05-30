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
            'A' => 15, // No Ticket
            'B' => 20, // Customer
            'C' => 15, // Site ID
            'D' => 30, // Alamat
            'E' => 15, // IP Address
            'F' => 20, // Kategori Pelaporan
            'G' => 30, // Problem Summary
            'H' => 15, // Status Ticket
            'I' => 15, // Open Level
            'J' => 20, // Escalation Level
            'K' => 20, // Open Date
            'L' => 20, // Pending Date
            'M' => 20, // Closed Date
            'N' => 30, // Pending Reason
            'O' => 40, // Action Description (increased for detailed content)
            'P' => 15, // PIC
            'Q' => 15, // Reported By
            'R' => 15, // Open Clock
            'S' => 15, // Total Pending
            'T' => 15, // Total Duration
            'U' => 15, // Downtime
            'V' => 15, // Classification
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'V';
        
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $this->title);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FF000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEEF2FF'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF4A90E2'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

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
                'color' => ['argb' => 'FF4A4A4A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF8F9FA'],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);
        
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->getRowDimension(3)->setRowHeight(10);

        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4A90E2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(30);

        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A5:{$lastColumn}{$highestRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle("G5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("O5:O{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("N5:N{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        for ($row = 5; $row <= $highestRow; $row++) {
            if (($row % 2) === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F2F2'],
                    ],
                ]);
            } else {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFFFFF'],
                    ],
                ]);
            }

            $status = $sheet->getCell("H{$row}")->getValue();
            $statusColor = match ($status) {
                'CLOSED' => 'FF00B050',
                'OPEN' => 'FF0070C0',
                'PENDING' => 'FFFFC000',
                default => 'FF000000',
            };
            
            $sheet->getStyle("H{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $statusColor],
                ],
            ]);
            
            $escalation = $sheet->getCell("J{$row}")->getValue();
            if ($escalation && $escalation !== '-') {
                $levelMatch = [];
                preg_match('/Level\s*(\d+)/i', $escalation, $levelMatch);
                $level = isset($levelMatch[1]) ? (int)$levelMatch[1] : 0;
                
                $escalationColor = match ($level) {
                    1 => 'FF00B0F0',
                    2 => 'FF0070C0',
                    3 => 'FF92D050',
                    4 => 'FF00B050',
                    5 => 'FFFFC000',
                    6 => 'FFFF0000',
                    default => 'FFC0C0C0',
                };
                
                if ($level === 0) {
                    $escalationColor = match (true) {
                        str_contains(strtolower($escalation), 'noc') && !str_contains(strtolower($escalation), 'spv') => 'FF00B0F0',
                        str_contains(strtolower($escalation), 'spv noc') => 'FF0070C0',
                        str_contains(strtolower($escalation), 'teknisi') && !str_contains(strtolower($escalation), 'spv') => 'FF92D050',
                        str_contains(strtolower($escalation), 'spv teknisi') => 'FF00B050',
                        str_contains(strtolower($escalation), 'engineer') => 'FFFFC000',
                        str_contains(strtolower($escalation), 'management') => 'FFFF0000',
                        default => 'FFC0C0C0',
                    };
                }
                
                $sheet->getStyle("J{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $escalationColor],
                    ],
                ]);
            }

            // Dynamically adjust row height for Action Description column
            $actionDescription = $sheet->getCell("O{$row}")->getValue();
            if ($actionDescription && strlen($actionDescription) > 0) {
                $lineCount = substr_count($actionDescription, "\n") + 1;
                $rowHeight = max(15, $lineCount * 15);
                $sheet->getRowDimension($row)->setRowHeight($rowHeight);
            }
        }
        
        $summaryRow = $highestRow + 2;
        $sheet->mergeCells("A{$summaryRow}:G{$summaryRow}");
        $sheet->setCellValue("A{$summaryRow}", "Total Tickets: " . ($highestRow - 4));
        $sheet->getStyle("A{$summaryRow}:G{$summaryRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2F2F2'],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        
        $footerRow = $summaryRow + 2;
        $sheet->mergeCells("A{$footerRow}:{$lastColumn}{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", "Laporan dihasilkan pada: " . Carbon::now()->format('d/m/Y H:i:s') . " oleh PT. Artacomindo");
        $sheet->getStyle("A{$footerRow}:{$lastColumn}{$footerRow}")->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 10,
                'color' => ['argb' => 'FF808080'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->freezePane('A5');
                
                foreach ($this->columnWidths() as $column => $minWidth) {
                    $currentWidth = $event->sheet->getColumnDimension($column)->getWidth();
                    $calculatedWidth = min(50, max($minWidth, $currentWidth));
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
                    ->setFitToHeight(0);
                
                $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);
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
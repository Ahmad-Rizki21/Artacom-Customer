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
        
        // Opsi tambahan
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
            'Open Level',          // Tambahkan kolom untuk Open Level
            'Escalation Level',    // Tambahkan kolom untuk Level Eskalasi
            'Open Date',
            'Pending Date',
            'Closed Date',
            'Pending Reason',
            'Action Description',
            'PIC',                 // Tambahkan kolom untuk PIC
            'Reported By',         // Tambahkan kolom untuk Reported By
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

        // Ambil action terakhir
        $latestAction = TicketAction::where('No_Ticket', $ticket->No_Ticket)
            ->orderBy('Action_Time', 'desc')
            ->first();

        // Ambil semua action untuk tiket ini untuk memperoleh riwayat pending yang lengkap
        $allPendingActions = TicketAction::where('No_Ticket', $ticket->No_Ticket)
            ->where('Action_Taken', 'Pending Clock')
            ->orderBy('Action_Time', 'desc')
            ->get();
        
        // Ambil pending reason dari action terakhir dengan Pending Clock
        $latestPendingAction = $allPendingActions->first();
        $pendingReason = $latestPendingAction ? $latestPendingAction->Action_Description : $ticket->Pending_Reason ?? '-';

        // Jika pending reason terlalu panjang, potong dan tambahkan elipsis
        if (strlen($pendingReason) > 150) {
            $pendingReason = substr($pendingReason, 0, 147) . '...';
        }

        // Ambil informasi eskalasi dari model Ticket
        $currentEscalation = $ticket->Current_Escalation_Level ?? '-';
        $escalationLevel = '-';
        $escalationTitle = '-';
        
        // Jika tidak ada nilai eskalasi di model, coba ambil dari aksi-aksi terkait
        if ($currentEscalation === '-' || empty($currentEscalation)) {
            try {
                // Cari aksi eskalasi terbaru
                $escalationAction = TicketAction::where('No_Ticket', $ticket->No_Ticket)
                    ->where('Action_Taken', 'Escalation')
                    ->orderBy('Action_Time', 'desc')
                    ->first();
                
                if ($escalationAction) {
                    // Cek jika ada "To: " dalam deskripsi action
                    if (strpos($escalationAction->Action_Description, 'To: ') !== false) {
                        preg_match('/To: (.+)/', $escalationAction->Action_Description, $matches);
                        if (isset($matches[1])) {
                            $currentEscalation = $matches[1];
                        }
                    } else {
                        // Coba ambil dari kolom Escalation_Target_Level jika ada
                        $currentEscalation = $escalationAction->Escalation_Target_Level ?? $currentEscalation;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error getting escalation info for ticket {$ticket->No_Ticket}: " . $e->getMessage());
            }
        }
        
        // Format tampilan eskalasi dengan format "Level - Jabatan"
        if ($currentEscalation !== '-' && !empty($currentEscalation)) {
            // Petakan nilai eskalasi ke format yang diinginkan
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
                    // Jika formatnya sudah "Level X - Jabatan", pertahankan
                    if (strpos($currentEscalation, ' - ') !== false) {
                        list($escalationLevel, $escalationTitle) = explode(' - ', $currentEscalation, 2);
                    } 
                    // Jika hanya angka level, tambahkan format
                    else if (preg_match('/level\s*(\d+)/i', $currentEscalation, $matches)) {
                        $escalationLevel = 'Level ' . $matches[1];
                        $escalationTitle = $this->getLevelTitle($matches[1]);
                        $currentEscalation = $escalationLevel . ' - ' . $escalationTitle;
                    }
                    // Jika hanya jabatan, cari level yang sesuai
                    else {
                        $level = $this->getLevelFromRole($currentEscalation);
                        if ($level > 0) {
                            $escalationLevel = 'Level ' . $level;
                            $escalationTitle = $currentEscalation;
                            $currentEscalation = $escalationLevel . ' - ' . $escalationTitle;
                        } else {
                            // Jika bukan format yang dikenal, gunakan apa adanya
                            $escalationLevel = 'Level';
                            $escalationTitle = $currentEscalation;
                            $currentEscalation = 'Level - ' . $currentEscalation;
                        }
                    }
                    break;
            }
        }

        // Gunakan nilai tersimpan dalam database jika tersedia
        $openDurationSeconds = $ticket->open_duration_seconds ?? 0;
        $totalPendingSeconds = $ticket->pending_duration_seconds ?? 0;
        $totalDurationSeconds = $ticket->total_duration_seconds ?? 0;
        
        // Jika tidak ada nilai tersimpan, hitung ulang
        if ($openDurationSeconds == 0 && $totalPendingSeconds == 0 && $totalDurationSeconds == 0) {
            // Perhitungan menggunakan timer dari model
            $timer = $ticket->getCurrentTimer();
            $openDurationSeconds = $timer['open']['seconds'] ?? 0;
            $totalPendingSeconds = $timer['pending']['seconds'] ?? 0;
            $totalDurationSeconds = $timer['total']['seconds'] ?? 0;
            
            // Jika masih 0, coba parsing dari atribut lama (jika ada)
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

        // Calculate downtime in seconds (Total Duration - Total Pending)
        $downtimeSeconds = max(0, $totalDurationSeconds - $totalPendingSeconds);
        
        // Format semua durasi ke format HH:MM:SS
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
            $ticket->Open_Level ?? '-',           // Tambahkan Open Level
            $currentEscalation,                   // Tambahkan Escalation Level
            optional($ticket->Open_Time)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Pending_Start)->format('d/m/Y H:i:s') ?? '-',
            optional($ticket->Closed_Time)->format('d/m/Y H:i:s') ?? '-',
            $pendingReason,
            $latestAction->Action_Description ?? '-',
            $ticket->Pic ?? '-',                 // Tambahkan PIC
            $ticket->Reported_By ?? '-',         // Tambahkan Reported By
            $openDurationFormatted,
            $totalPendingFormatted,
            $totalDurationFormatted,
            $downtimeFormatted,
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
            'O' => 30, // Action Description
            'P' => 15, // PIC
            'Q' => 15, // Reported By
            'R' => 15, // Open Clock
            'S' => 15, // Total Pending
            'T' => 15, // Total Duration
            'U' => 15, // Downtime
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        // Hitung jumlah kolom (A sampai U = 21 kolom)
        $lastColumn = 'U';
        
        // Header judul laporan
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
                'startColor' => ['argb' => 'FFEEF2FF'], // Warna latar belakang soft blue
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF4A90E2'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Sub-header (periode atau informasi tambahan)
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
                'color' => ['argb' => 'FF4A4A4A'], // Dark gray
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF8F9FA'], // Light gray
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);
        
        // Baris kosong untuk jarak
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->getRowDimension(3)->setRowHeight(10);

        // Header tabel
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFFFF'], // White
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4A90E2'], // Blue
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

        // Membuat baris-baris data menjadi bergaris dan selang-seling warna
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

        // Khusus kolom Problem Summary dan Action Description, rata kiri
        $sheet->getStyle("G5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("O5:O{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("N5:N{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Styling setiap baris dengan warna selang-seling dan status warna
        for ($row = 5; $row <= $highestRow; $row++) {
            // Zebra striping (baris selang-seling)
            if (($row % 2) === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F2F2'], // Light gray
                    ],
                ]);
            } else {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFFFFF'], // White
                    ],
                ]);
            }

            // Warna status ticket
            $status = $sheet->getCell("H{$row}")->getValue();
            $statusColor = match ($status) {
                'CLOSED' => 'FF00B050', // Green
                'OPEN' => 'FF0070C0',   // Blue
                'PENDING' => 'FFFFC000', // Yellow/Orange
                default => 'FF000000',   // Black
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
            ]);
            
            // Warna eskalasi jika ada
            $escalation = $sheet->getCell("J{$row}")->getValue();
            if ($escalation && $escalation !== '-') {
                // Ekstrak level dari format "Level X - Jabatan"
                $levelMatch = [];
                preg_match('/Level\s*(\d+)/i', $escalation, $levelMatch);
                $level = isset($levelMatch[1]) ? (int)$levelMatch[1] : 0;
                
                // Warna berdasarkan level
                $escalationColor = match ($level) {
                    1 => 'FF00B0F0',    // Light Blue for Level 1 (NOC)
                    2 => 'FF0070C0',    // Dark Blue for Level 2 (SPV NOC)
                    3 => 'FF92D050',    // Light Green for Level 3 (Teknisi)
                    4 => 'FF00B050',    // Dark Green for Level 4 (SPV Teknisi)
                    5 => 'FFFFC000',    // Yellow/Gold for Level 5 (Engineer)
                    6 => 'FFFF0000',    // Red for Level 6 (Management)
                    default => 'FFC0C0C0',  // Grey for others
                };
                
                // Warna alternatif berdasarkan jabatan jika level tidak terdeteksi
                if ($level === 0) {
                    $escalationColor = match (true) {
                        str_contains(strtolower($escalation), 'noc') && !str_contains(strtolower($escalation), 'spv') => 'FF00B0F0',      // Light Blue
                        str_contains(strtolower($escalation), 'spv noc') => 'FF0070C0',   // Dark Blue
                        str_contains(strtolower($escalation), 'teknisi') && !str_contains(strtolower($escalation), 'spv') => 'FF92D050',  // Light Green
                        str_contains(strtolower($escalation), 'spv teknisi') => 'FF00B050', // Dark Green
                        str_contains(strtolower($escalation), 'engineer') => 'FFFFC000',   // Yellow
                        str_contains(strtolower($escalation), 'management') => 'FFFF0000', // Red
                        default => 'FFC0C0C0',   // Grey
                    };
                }
                
                $sheet->getStyle("J{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FF000000'], // Black text
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $escalationColor],
                    ],
                ]);
            }
        }
        
        // Tambahkan baris jumlah/ringkasan di bagian bawah
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
        
        // Tampilkan footer atau info tambahan
        $footerRow = $summaryRow + 2;
        $sheet->mergeCells("A{$footerRow}:{$lastColumn}{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", "Laporan dihasilkan pada: " . Carbon::now()->format('d/m/Y H:i:s') . " oleh PT. Artacomindo");
        $sheet->getStyle("A{$footerRow}:{$lastColumn}{$footerRow}")->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 10,
                'color' => ['argb' => 'FF808080'], // Gray
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
                
                // Otomatis menyesuaikan lebar kolom berdasarkan konten
                // Tapi tetap harus mempertimbangkan batas minimum dan maksimum
                foreach ($this->columnWidths() as $column => $minWidth) {
                    $currentWidth = $event->sheet->getColumnDimension($column)->getWidth();
                    $calculatedWidth = min(50, max($minWidth, $currentWidth)); // Min 15, max 50
                    $event->sheet->getColumnDimension($column)->setWidth($calculatedWidth);
                }
                
                // Tambahkan header dan footer khusus
                $event->sheet->getHeaderFooter()
                    ->setOddHeader('&L&B' . $this->title . '&R&D')
                    ->setOddFooter('&L&B' . $this->title . '&RPage &P of &N');
                
                // Atur pengaturan cetak
                $event->sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                
                // Set judul cetak
                $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);
            },
        ];
    }

    /**
     * Mendapatkan judul jabatan berdasarkan level
     * 
     * @param int|string $level
     * @return string
     */
    private function getLevelTitle($level)
    {
        // Mapping level ke jabatan sesuai struktur organisasi
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
    
    /**
     * Mendapatkan informasi level dari jabatan
     * 
     * @param string $role
     * @return int
     */
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
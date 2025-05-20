<?php

namespace App\Filament\AlfaLawson\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\AlfaLawson\Ticket;
use Carbon\Carbon;

class MonthlyTicketChart extends ApexChartWidget
{
    protected static ?string $chartId = 'ticketChart';
    protected static ?string $heading = 'Laporan Ticket Bulanan';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = null; // Nonaktifkan polling
    protected static bool $deferLoading = true;

    // Membuat chart full width
    protected int | string | array $columnSpan = 'full';

    protected function getOptions(): array
    {
        // Ambil data ticket bulanan untuk 12 bulan terakhir
        $monthlyData = $this->getMonthlyTicketData();

        // Tentukan nilai maksimum untuk y-axis secara dinamis
        $maxValue = max(
            max($monthlyData['total']),
            max($monthlyData['open_alfa']),
            max($monthlyData['open_lawson']),
            max($monthlyData['pending']),
            max($monthlyData['closed'])
        );
        // Tambahkan buffer 10% ke nilai maksimum agar grafik tidak terlalu rapat
        $yAxisMax = ceil($maxValue * 1.1);

        return [
            'chart' => [
                'type' => 'line', // Tipe utama line, kombinasikan dengan bar
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
                'zoom' => [
                    'enabled' => false,
                ],
                'fontFamily' => 'inherit',
                'width' => '100%',
            ],
            'series' => [
                [
                    'name' => 'Total Tickets',
                    'data' => $monthlyData['total'],
                    'type' => 'bar',
                    'color' => '#1E90FF', // Biru untuk bar
                ],
                [
                    'name' => 'Open (Alfa)',
                    'data' => $monthlyData['open_alfa'],
                    'type' => 'line',
                    'color' => '#FF4560', // Merah untuk Open Alfa
                ],
                [
                    'name' => 'Open (Lawson)',
                    'data' => $monthlyData['open_lawson'],
                    'type' => 'line',
                    'color' => '#FF69B4', // Pink untuk Open Lawson
                ],
                [
                    'name' => 'Pending',
                    'data' => $monthlyData['pending'],
                    'type' => 'line',
                    'color' => '#f59e0b', // Kuning
                ],
                [
                    'name' => 'Closed',
                    'data' => $monthlyData['closed'],
                    'type' => 'line',
                    'color' => '#10b981', // Hijau
                ],
            ],
            'xaxis' => [
                'categories' => $monthlyData['months'],
                'labels' => [
                    'style' => [
                        'colors' => '#d1d5db', // Abu-abu terang, kontras di mode gelap
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#d1d5db', // Abu-abu terang, kontras di mode gelap
                        'fontWeight' => 600,
                    ],
                ],
                'max' => $yAxisMax, // Nilai maksimum dinamis
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [0, 2, 2, 2, 2], // Bar tanpa stroke, line dengan stroke
            ],
            'grid' => [
                'borderColor' => '#4b5563', // Abu-abu gelap untuk grid, kontras di mode gelap
                'row' => [
                    'colors' => ['transparent', 'transparent'],
                    'opacity' => 0.1,
                ],
                'padding' => [
                    'left' => 0,
                    'right' => 0,
                ],
            ],
            'markers' => [
                'size' => 5,
                'hover' => [
                    'size' => 7,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
                'theme' => 'dark',
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'center',
                'labels' => [
                    'colors' => '#d1d5db', // Abu-abu terang, kontras di mode gelap
                ],
                'itemMargin' => [
                    'horizontal' => 10,
                ],
            ],
            'responsive' => [
                [
                    'breakpoint' => 1000,
                    'options' => [
                        'chart' => [
                            'width' => '100%',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Ambil data ticket bulanan untuk 12 bulan terakhir dari database
     *
     * @return array
     */
    protected function getMonthlyTicketData(): array
    {
        $months = collect();
        $totalTickets = collect();
        $openAlfaTickets = collect();
        $openLawsonTickets = collect();
        $pendingTickets = collect();
        $closedTickets = collect();

        // Tentukan rentang waktu: 12 bulan terakhir (Juni 2024 - Mei 2025)
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Ambil data tiket yang dibuat dalam rentang waktu tersebut
        $tickets = Ticket::selectRaw('YEAR(Open_Time) as year, MONTH(Open_Time) as month, Customer, Status, COUNT(*) as count')
            ->whereBetween('Open_Time', [$startDate, $endDate])
            ->groupBy('year', 'month', 'Customer', 'Status')
            ->get();

        // Inisialisasi data untuk setiap bulan
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M Y');
            $months->push($monthName);

            $year = $month->year;
            $monthNum = $month->month;

            // Filter data untuk bulan ini
            $monthData = $tickets->where('year', $year)->where('month', $monthNum);

            // Hitung total tiket
            $total = $monthData->sum('count');
            $totalTickets->push($total);

            // Hitung tiket OPEN untuk ALFAMART
            $openAlfa = $monthData->where('Customer', 'ALFAMART')->where('Status', Ticket::STATUS_OPEN)->sum('count');
            $openAlfaTickets->push($openAlfa);

            // Hitung tiket OPEN untuk LAWSON
            $openLawson = $monthData->where('Customer', 'LAWSON')->where('Status', Ticket::STATUS_OPEN)->sum('count');
            $openLawsonTickets->push($openLawson);

            // Hitung tiket PENDING (semua customer)
            $pending = $monthData->where('Status', Ticket::STATUS_PENDING)->sum('count');
            $pendingTickets->push($pending);

            // Hitung tiket CLOSED (semua customer)
            $closed = $monthData->where('Status', Ticket::STATUS_CLOSED)->sum('count');
            $closedTickets->push($closed);
        }

        return [
            'months' => $months->toArray(),
            'total' => $totalTickets->toArray(),
            'open_alfa' => $openAlfaTickets->toArray(),
            'open_lawson' => $openLawsonTickets->toArray(),
            'pending' => $pendingTickets->toArray(),
            'closed' => $closedTickets->toArray(),
        ];
    }
}
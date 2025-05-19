<?php

namespace App\Filament\AlfaLawson\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

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
                    'color' => '#FF69B4', // Pink untuk Open Bakti
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
                'max' => 50,
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
     * Ambil data ticket bulanan untuk 12 bulan terakhir menggunakan data dummy
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

        // Data untuk 12 bulan terakhir (Juni 2024 - Mei 2025)
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M Y');
            $months->push($monthName);

            // Data dummy sesuai dengan chart
            if ($monthName === 'May 2025') {
                $openAlfa = 2;
                $openLawson = 1;
                $pending = 5;
                $closed = 45;
                $total = 53; // 2 + 1 + 5 + 45
            } elseif ($monthName === 'Apr 2025') {
                $openAlfa = 1;
                $openLawson = 1;
                $pending = 4;
                $closed = 40;
                $total = 46;
            } elseif ($monthName === 'Mar 2025') {
                $openAlfa = 1;
                $openBakti = 1;
                $pending = 3;
                $closed = 35;
                $total = 40;
            } elseif ($monthName === 'Feb 2025') {
                $openAlfa = 1;
                $openLawson = 1;
                $pending = 2;
                $closed = 30;
                $total = 34;
            } else {
                $openAlfa = rand(0, 2);
                $openLawson = rand(0, 2);
                $pending = rand(0, 5);
                $closed = rand(10, 30);
                $total = $openAlfa + $openLawson + $pending + $closed;
            }
            $openAlfaTickets->push($openAlfa);
            $openLawsonTickets->push($openLawson);
            $pendingTickets->push($pending);
            $closedTickets->push($closed);
            $totalTickets->push($total);
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
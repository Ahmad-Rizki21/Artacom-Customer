<?php

namespace App\Filament\AlfaLawson\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsAlfaLawsonRemoteOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected function getStats(): array
    {
        // Simulasi data (ganti dengan query ke database sesuai kebutuhan)
        $totalRemote = 5; // Ganti dengan query seperti: Remote::count();
        $totalTicketAlfa = 10; // Ganti dengan query seperti: Ticket::where('type', 'alfa')->count();
        $totalTicketLawson = 15; // Ganti dengan query seperti: Ticket::where('type', 'lawson')->count();

        return [
            Stat::make('Total Remote Alfamart Lawson', $totalRemote)
                ->description('Remote connections')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('danger'),
            Stat::make('Total Ticket Alfa', $totalTicketAlfa)
                ->description('Alfa tickets')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Total Ticket Lawson', $totalTicketLawson)
                ->description('Lawson tickets')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
    protected function getView(): string
    {
        return 'filament.widgets.stats-overview';
    }
}

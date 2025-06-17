<?php

namespace App\Filament\AlfaLawson\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AlfaLawson\TableRemote;
use App\Models\AlfaLawson\Ticket;

class StatsAlfaLawsonRemoteOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Query untuk menghitung total remote dengan status OPERATIONAL
        $totalRemote = TableRemote::where('Status', 'OPERATIONAL')->count();

        // Query untuk menghitung total tiket untuk customer ALFAMART
        $totalTicketAlfa = Ticket::where('Customer', 'ALFAMART')->count();

        // Query untuk menghitung total tiket untuk customer LAWSON
        $totalTicketLawson = Ticket::where('Customer', 'LAWSON')->count();

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
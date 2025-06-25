<?php

namespace App\Filament\AlfaLawson\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AlfaLawson\TableRemote;
use App\Models\AlfaLawson\RemoteAtmbsi;
use App\Models\AlfaLawson\Ticket;
use Carbon\Carbon;

class StatsAlfaLawsonRemoteOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Total remote Alfamart Lawson OPERATIONAL
        $totalRemote = TableRemote::where('Status', 'OPERATIONAL')->count();

        // Total remote ATM BSI OPERATIONAL
        $totalRemoteBsi = RemoteAtmbsi::where('Status', 'OPERATIONAL')->count();

        // Total remote Alfamart Lawson DISMANTLED
        $totalRemoteDismantled = TableRemote::where('Status', 'DISMANTLED')->count();

        // Total remote ATM BSI DISMANTLED
        $totalRemoteBsiDismantled = RemoteAtmbsi::where('Status', 'DISMANTLED')->count();

        // Total dismantled bulan ini Alfamart Lawson
        $currentMonth = Carbon::now()->format('Y-m');
        $totalDismantledThisMonth = TableRemote::where('Status', 'DISMANTLED')
            ->whereRaw('DATE_FORMAT(updated_at, "%Y-%m") = ?', [$currentMonth])
            ->count();

        // Total tiket customer ALFAMART
        $totalTicketAlfa = Ticket::where('Customer', 'ALFAMART')->count();

        // Total tiket customer LAWSON
        $totalTicketLawson = Ticket::where('Customer', 'LAWSON')->count();

        return [
            Stat::make('Total Remote Alfamart Lawson', $totalRemote)
                ->description('Remote connections')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('danger'),

            Stat::make('Total Remote Atm BSI', $totalRemoteBsi)
                ->description('Remote connections')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('info'),

            Stat::make('Dismantled This Month Atm Bsi', $totalRemoteBsiDismantled)
                ->description('Dismantled connections')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('gray'),

            Stat::make('Dismantled This Month Alfa Lawson', $totalDismantledThisMonth)
                ->description('Dismantled in ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('gray'),

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

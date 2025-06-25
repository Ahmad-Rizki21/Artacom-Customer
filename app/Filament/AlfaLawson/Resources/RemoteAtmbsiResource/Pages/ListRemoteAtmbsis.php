<?php

namespace App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource\Pages;

use App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Models\AlfaLawson\RemoteAtmbsi;
use Illuminate\Contracts\View\View;

class ListRemoteAtmbsis extends ListRecords
{
    protected static string $resource = RemoteAtmbsiResource::class;

    public function getFooter(): ?View
    {
        $table = $this->getTable();
        $records = $table->getRecords();
        $firstItem = $records->firstItem() ?? 0;
        $lastItem = $records->lastItem() ?? 0;

        $totalRecords = RemoteAtmbsi::count();
        $dismantledCount = RemoteAtmbsi::where('Status', 'DISMANTLED')->count();
        $operationalCount = $totalRecords - $dismantledCount;

        return view('filament.tables.custom-footer', [
            'firstItem' => $firstItem,
            'lastItem' => $lastItem,
            'operationalCount' => $operationalCount,
            'dismantledCount' => $dismantledCount,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Remote ATM BSI')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->tooltip('Create a new Remote ATM BSI connection')
                ->modalWidth('lg')
                ->modalHeading('Create New Remote ATM BSI Connection')
                ->modalDescription('Add a new Remote ATM BSI connection to the system with its configuration.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Remote ATM BSI Connection Created')
                        ->body('The Remote ATM BSI connection has been created successfully.')
                        ->duration(5000)
                ),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Manage and monitor all Remote ATM BSI connections and their configurations.';
    }

    protected function getDefaultTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-server';
    }

    protected function getDefaultTableEmptyStateHeading(): ?string
    {
        return 'No Remote ATM BSI Connections Found';
    }

    protected function getDefaultTableEmptyStateDescription(): ?string
    {
        return 'Start by creating your first Remote ATM BSI connection. Click the button below to begin.';
    }

    protected function getDefaultTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Remote ATM BSI Connection')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }

    protected function getMetadata(): array
    {
        return [
            'description' => 'Remote ATM BSI Connections Management System',
            'author' => 'Ahmad-Rizki21',
            'lastModified' => '2025-06-25 16:39:00', // Diperbarui sesuai tanggal sekarang
        ];
    }

    protected function configureTableView(): void
    {
        $this->table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s')
            ->defaultSort('Online_Date', 'desc'); // Sesuaikan dengan kolom yang relevan
    }
}
<?php

namespace App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;

use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTableRemotes extends ListRecords
{
    protected static string $resource = TableRemoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Remote')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->tooltip('Create a new remote connection')
                ->modalWidth('lg')
                ->modalHeading('Create New Remote Connection')
                ->modalDescription('Add a new remote connection to the system with its configuration.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Remote Connection Created')
                        ->body('The remote connection has been created successfully.')
                        ->duration(5000)
                ),
        ];
    }


    protected function getHeaderWidgets(): array
    {
        return [
            // Jika Anda memiliki widget, tambahkan di sini
        ];
    }


    public function getSubheading(): ?string
    {
        return 'Manage and monitor all remote connections and their configurations.';
    }

    // Customizing empty state
    protected function getDefaultTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-computer-desktop';
    }

    protected function getDefaultTableEmptyStateHeading(): ?string
    {
        return 'No Remote Connections Found';
    }

    protected function getDefaultTableEmptyStateDescription(): ?string
    {
        return 'Start by creating your first remote connection. Click the button below to begin.';
    }

    protected function getDefaultTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Remote Connection')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }

    // Optional: Add a footer message
    protected function getFooterWidgets(): array
    {
        return [
            // Jika Anda memiliki widget footer, tambahkan di sini
        ];
    }

    // Optional: Add page metadata
    protected function getMetadata(): array
    {
        return [
            'description' => 'Remote Connections Management System',
            'author' => 'Ahmad-Rizki21',
            'lastModified' => '2025-05-08 15:06:49',
        ];
    }

    // Optional: Customize the table's view
    protected function configureTableView(): void
    {
        $this->table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s') // Auto-refresh every 60 seconds
            ->defaultSort('created_at', 'desc');
    }
}
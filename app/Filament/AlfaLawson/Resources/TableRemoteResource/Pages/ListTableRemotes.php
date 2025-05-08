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
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Remote created')
                        ->body('New remote connection has been created successfully.')
                )
        ];
    }

    // protected function getTitle(): string 
    // {
    //     return 'Remote Connections';
    // }

    // protected function getSubheading(): string
    // {
    //     return 'Manage all remote connections and their configurations.';
    // }
}
<?php

namespace App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTablePeplink extends CreateRecord
{
    protected static string $resource = TablePeplinkResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Peplink device registered')
            ->body('New device has been successfully added to the system.');
    }
}
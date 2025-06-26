<?php

namespace App\Filament\AlfaLawson\Resources\MaipuResource\Pages;

use App\Filament\AlfaLawson\Resources\MaipuResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMaipu extends CreateRecord
{
    protected static string $resource = MaipuResource::class;
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

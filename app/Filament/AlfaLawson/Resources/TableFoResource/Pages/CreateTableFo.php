<?php

namespace App\Filament\AlfaLawson\Resources\TableFoResource\Pages;

use App\Filament\AlfaLawson\Resources\TableFoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTableFo extends CreateRecord
{
    protected static string $resource = TableFoResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('FO Connection Created')
            ->body('New fiber optic connection has been created successfully.')
            ->duration(5000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
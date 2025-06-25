<?php

namespace App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource\Pages;

use App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateRemoteAtmbsi extends CreateRecord
{
    protected static string $resource = RemoteAtmbsiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(route('filament.alfa-lawson.resources.remote-atmbsis.index'))
                ->icon('heroicon-o-x-circle'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Remote ATM BSI Connection Created')
            ->body('The Remote ATM BSI connection has been created successfully.')
            ->duration(5000);
    }
}
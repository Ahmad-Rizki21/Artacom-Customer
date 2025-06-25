<?php

namespace App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource\Pages;

use App\Filament\AlfaLawson\Resources\RemoteAtmbsiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRemoteAtmbsi extends EditRecord
{
    protected static string $resource = RemoteAtmbsiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('cancel')
            //     ->label('Cancel')
            //     ->color('gray')
            //     ->url(route('filament.alfa-lawson.resources.remote-atmbsis.index'))
            //     ->icon('heroicon-o-x-circle'),
            Actions\DeleteAction::make()
                ->label('Delete')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Remote ATM BSI Connection Deleted')
                        ->body('The Remote ATM BSI connection has been deleted successfully.')
                        ->duration(5000)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Remote ATM BSI Connection Updated')
            ->body('The Remote ATM BSI connection has been updated successfully.')
            ->duration(5000);
    }
}
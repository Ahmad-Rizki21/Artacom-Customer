<?php

namespace App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;

use App\Filament\AlfaLawson\Resources\TableSimcardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTableSimcard extends CreateRecord
{
    protected static string $resource = TableSimcardResource::class;

    /**
     * Customize the notification after a record is created.
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('SIM Card Berhasil Dibuat')
            ->body('Data SIM Card dengan nomor ' . $this->record->Sim_Number . ' telah berhasil disimpan.') // Perbaikan 'sim_number' menjadi 'Sim_Number'
            ->success()
            ->seconds(5);
    }

    /**
     * Redirect to the index page after successful creation.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
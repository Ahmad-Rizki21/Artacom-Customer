<?php

namespace App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;

use App\Filament\AlfaLawson\Resources\TableSimcardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTableSimcard extends EditRecord
{
    protected static string $resource = TableSimcardResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('SIM Card Berhasil Diperbarui')
            ->body('Data SIM Card dengan nomor ' . $this->record->Sim_Number . ' telah berhasil diperbarui.')
            ->success()
            ->seconds(5);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
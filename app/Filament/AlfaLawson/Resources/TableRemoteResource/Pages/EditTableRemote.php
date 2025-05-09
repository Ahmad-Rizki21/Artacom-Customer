<?php

namespace App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;

use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTableRemote extends EditRecord
{
    protected static string $resource = TableRemoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Remote Berhasil Diperbarui')
            ->body('Data untuk Site ID ' . $this->record->Site_ID . ' telah berhasil diperbarui.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->color('success')
            ->duration(5000) // Notifikasi akan ditampilkan selama 5 detik
            ->success();
    }
}
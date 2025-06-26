<?php

namespace App\Filament\AlfaLawson\Resources\MaipuResource\Pages;

use App\Filament\AlfaLawson\Resources\MaipuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditMaipu extends EditRecord
{
    protected static string $resource = MaipuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Berhasil Diperbarui')
            ->body('Perubahan pada data Table Maipu telah berhasil disimpan.')
            ->actions([
                Action::make('view')
                    ->label('Kembali ke Daftar')
                    ->url(fn () => MaipuResource::getUrl('index'))
                    ->button()
                    ->color('primary'),
                Action::make('undo')
                    ->label('Batalkan')
                    ->url('#') // Ganti dengan URL atau logika untuk undo jika ada
                    ->color('danger'),
            ])
            ->persistent();
    }
}

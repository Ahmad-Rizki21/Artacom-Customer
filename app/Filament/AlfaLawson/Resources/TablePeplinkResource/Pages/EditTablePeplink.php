<?php

namespace App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditTablePeplink extends EditRecord
{
    protected static string $resource = TablePeplinkResource::class;

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
            ->body('Perubahan pada data Table Peplink telah berhasil disimpan.')
            ->actions([
                Action::make('view')
                    ->label('Kembali ke Daftar')
                    ->url(fn () => TablePeplinkResource::getUrl('index'))
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
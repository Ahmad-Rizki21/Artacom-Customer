<?php

namespace App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;

use App\Filament\AlfaLawson\Resources\TableSimcardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditTableSimcard extends EditRecord
{
    protected static string $resource = TableSimcardResource::class;

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
            ->title('SIM Card Berhasil Diperbarui')
            ->body('Data SIM Card dengan nomor ' . $this->record->Sim_Number . ' telah berhasil diperbarui.')
            ->actions([
                Action::make('view')
                    ->label('Kembali ke Daftar')
                    ->url(fn () => TableSimcardResource::getUrl('index'))
                    ->button()
                    ->color('primary'),
                Action::make('undo')
                    ->label('Batalkan')
                    ->url('#') // Ganti dengan URL atau logika untuk undo jika ada
                    ->color('danger'),
            ])
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
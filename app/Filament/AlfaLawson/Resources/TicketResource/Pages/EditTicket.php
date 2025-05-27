<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Carbon\Carbon;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record])),
            Actions\DeleteAction::make()
                ->requiresConfirmation() // Add confirmation prompt
                ->modalHeading('Delete Ticket')
                ->modalDescription('Apakah anda yakin ingin menghapus tiket ini?')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successNotification(
                    Notification::make()
                        ->title('Ticket Deleted')
                        ->body('The ticket has been successfully deleted.')
                        ->success()
                ),
        ];
    }

    protected function afterSave(): void
    {
        // Get the current time in WIB (UTC+7)
        $currentTime = Carbon::now('Asia/Jakarta')->format('H:i A, d F Y');
        $ticketNumber = $this->record->No_Ticket;

        // Send notification
        Notification::make()
            ->title('Ticket Updated')
            ->body("Ticket dengan Nomor {$ticketNumber} Berhasil ter Update. ({$currentTime})")
            ->success()
            ->send();

        // Redirect to the list page of the ticket resource after saving
        $this->redirect($this->getResource()::getUrl('index'));
    }
}
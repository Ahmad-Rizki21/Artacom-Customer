<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

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
}
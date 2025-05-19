<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['Status'] = 'OPEN';
        $data['Open_By'] = Auth::id();
        $data['Open_Time'] = now();
        $data['Open_Level'] = 'Level 1'; // Default level
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman view setelah create
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Ticket Created Successfully')
            ->icon('heroicon-o-credit-card')
            ->body('A new ticket has been created with ID: ' . $this->record->No_Ticket)
            ->success()
            ->send();
    }
}
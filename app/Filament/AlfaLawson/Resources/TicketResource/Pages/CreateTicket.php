<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\AlfaLawson\TicketAction;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['Status'] = 'OPEN';
        $data['Open_By'] = Auth::id();
        $data['Open_Time'] = now();
        $data['Open_Level'] = Auth::user()->Level ?? 'Level 1'; // Ambil dari level user
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman view setelah create
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        try {
            // Buat entri awal di TicketAction berdasarkan Problem
            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => 'OPEN', // Status awal sebagai 'Note'
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1',
                'Action_Description' => $this->record->Problem ?? 'No problem description provided.', // Ambil dari kolom Problem
            ]);

            // Notifikasi sukses
            Notification::make()
                ->title('Ticket Created Successfully')
                ->icon('heroicon-o-credit-card')
                ->body('A new ticket has been created with ID: ' . $this->record->No_Ticket)
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Notifikasi jika ada error
            Notification::make()
                ->danger()
                ->title('Error Creating Initial Progress')
                ->body($e->getMessage())
                ->send();
        }
    }
}
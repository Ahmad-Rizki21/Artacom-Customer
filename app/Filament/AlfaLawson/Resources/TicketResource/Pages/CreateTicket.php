<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Facades\Log;

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
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        try {
            // Buat entri awal di TicketAction berdasarkan Problem
            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => 'OPEN',
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1',
                'Action_Description' => $this->record->Problem ?? 'No problem description provided.',
            ]);

            // Save evidence if uploaded using EvidenceService
            if (isset($this->data['evidences']) && !empty($this->data['evidences'])) {
                $evidenceService = app(\App\Services\EvidenceService::class);
                $result = $evidenceService->uploadFiles($this->record->No_Ticket, $this->data['evidences'], [
                    'upload_stage' => \App\Models\AlfaLawson\TicketEvidence::STAGE_INITIAL,
                    'description' => null,
                ]);

                if ($result['success_count'] > 0) {
                    Log::info('Evidence files uploaded via CreateTicket', [
                        'ticket' => $this->record->No_Ticket,
                        'uploaded_count' => $result['success_count'],
                    ]);
                }

                if ($result['error_count'] > 0) {
                    Log::error('Failed to upload some evidence files', [
                        'ticket' => $this->record->No_Ticket,
                        'errors' => $result['errors'],
                    ]);
                    Notification::make()
                        ->warning()
                        ->title('Upload Issues')
                        ->body('Some files failed to upload: ' . implode(', ', array_column($result['errors'], 'error')))
                        ->send();
                }

                // Refresh the record to load the new evidence
                $this->record->refresh();
            }

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
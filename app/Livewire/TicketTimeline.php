<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class TicketTimeline extends Component
{
    public $ticket;
    public $isPending = false;
    public $user; // Tambahkan properti $user

    // Define level mapping
    protected $levelMapping = [
        'Level 1' => 'NOC',
        'Level 2' => 'SPV NOC',
        'Level 3' => 'Teknisi',
        'Level 4' => 'SPV Teknisi',
        'Level 5' => 'Engineer',
        'Level 6' => 'Management',
    ];

    public function mount($ticket = null)
    {
        if (!$ticket && request()->has('record')) {
            $ticket = request()->get('record');
        }
        $this->ticket = $ticket;

        // Inisialisasi $user dengan pengguna yang terautentikasi
        $this->user = Auth::user();

        // Inisialisasi status toggle berdasarkan status ticket
        $this->isPending = $this->ticket->Status === 'PENDING';
    }

    public function togglePendingStatus()
    {
        try {
            $newStatus = $this->isPending ? 'PENDING' : 'OPEN';
            $actionTaken = $this->isPending ? 'Pending Clock' : 'Start Clock';
            $description = $this->isPending ? 'Ticket set to Pending via toggle' : 'Ticket set to Open via toggle';

            // Update ticket status
            $updateData = [
                'Status' => $newStatus,
            ];

            if ($newStatus === 'PENDING') {
                $updateData['Pending_Start'] = now();
                $updateData['Pending_Reason'] = $description;
                $updateData['Pending_Stop'] = null;
            } else {
                $updateData['Pending_Stop'] = now();
            }

            $this->ticket->update($updateData);

           TicketAction::create([
            'No_Ticket' => $this->ticket->No_Ticket,
            'Action_Taken' => $actionTaken,
            'Action_Time' => now(),
            'Action_By' => Auth::user()->name,
            'Action_Level' => Auth::user()->Level ?? 'Level 1', // Gunakan level user
            'Action_Description' => $description,
        ]);

            // Emit event untuk refresh UI dan timer
            $this->dispatch('statusUpdated', $this->ticket->Status);
            $this->dispatch('refresh');

            // Kirim notifikasi sukses
            Notification::make()
                ->success()
                ->title('Status Updated')
                ->body('The ticket status has been updated successfully.')
                ->send();
        } catch (\Exception $e) {
            Log::error('Toggle Status Error: ' . $e->getMessage());
            Notification::make()
                ->danger()
                ->title('Error Updating Status')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getLevelDisplayName($level)
    {
        return $this->levelMapping[$level] ?? $level;
    }

    public function render()
    {
        try {
            if (!$this->ticket) {
                throw new \Exception('Ticket data tidak ditemukan');
            }

            $actions = $this->ticket->actions()
                ->orderBy('Action_Time', 'desc')
                ->get();

            return view('livewire.ticket-timeline', [
                'actions' => $actions,
                'levelMapping' => $this->levelMapping,
                'user' => $this->user, // Pastikan $user dilewatkan ke view
            ]);
        } catch (\Exception $e) {
            Log::error('Timeline Error: ' . $e->getMessage());
            return view('livewire.ticket-timeline', [
                'actions' => collect([]),
                'levelMapping' => $this->levelMapping,
                'error' => 'Terjadi kesalahan saat memuat timeline',
            ]);
        }
    }
}
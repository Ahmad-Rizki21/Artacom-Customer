<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Facades\Log;

class TicketTimeline extends Component
{
    public $ticket;
    
    public function mount($ticket = null)
    {
        if (!$ticket && request()->has('record')) {
            $ticket = request()->get('record');
        }
        $this->ticket = $ticket;
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
            'actions' => $actions
        ]);
    } catch (\Exception $e) {
        // Log error
        Log::error('Timeline Error: ' . $e->getMessage());
        
        // Return view dengan pesan error
        return view('livewire.ticket-timeline', [
            'actions' => collect([]),
            'error' => 'Terjadi kesalahan saat memuat timeline'
        ]);
    }
}

    

}
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\Ticket;
use Illuminate\Support\Facades\Log;

class TicketTimer extends Component
{
    public $ticket;
    public bool $isRunning = true;
    public int $openTimeSeconds = 0;
    public int $pendingTimeSeconds = 0;
    public int $totalTimeSeconds = 0;
    public $slaPercentage = 0;
    private $lastUpdated = 0;

    protected $listeners = ['ticketStatusUpdated' => 'refreshTicket'];

    public function getListeners()
    {
        return [
            'echo:tickets.' . ($this->ticket->No_Ticket ?? 'unknown') . ',TicketStatusUpdated' => 'refreshTicket',
        ];
    }

    public function mount(Ticket $ticket = null)
    {
        if (!$ticket || !$ticket->exists) {
            Log::error('Ticket not provided or does not exist in TicketTimer component', [
                'ticket_id' => $ticket->No_Ticket ?? 'null',
                'exists' => $ticket->exists ?? false,
            ]);
            $ticketId = request()->route('ticket') ?? 'TI-0000002';
            $this->ticket = Ticket::where('No_Ticket', $ticketId)->firstOrFail();
        } else {
            $this->ticket = $ticket;
        }

        Log::debug('TicketTimer mounted', [
            'ticket_id' => $this->ticket->No_Ticket ?? 'null',
            'ticket_status' => $this->ticket->Status ?? 'null',
            'open_time' => $this->ticket->Open_Time ? $this->ticket->Open_Time->toDateTimeString() : 'null',
        ]);

        $this->slaPercentage = 0;
        $this->initializeTimer();
    }

    public function initializeTimer()
{
    if (!$this->ticket || !$this->ticket->Open_Time) {
        Log::error('Ticket or Open_Time is null in initializeTimer', [
            'ticket_id' => $this->ticket->No_Ticket ?? 'null',
            'open_time' => $this->ticket->Open_Time ?? 'null',
        ]);
        $this->openTimeSeconds = 0;
        $this->pendingTimeSeconds = 0;
    } else {
        $timer = $this->ticket->getCurrentTimer();
        $this->openTimeSeconds = $timer['open']['seconds'] ?? 0;
        $this->pendingTimeSeconds = $timer['pending']['seconds'] ?? 0;
    }

    $this->totalTimeSeconds = $this->openTimeSeconds + $this->pendingTimeSeconds;

    Log::debug('Timer initialized', [
        'ticket_id' => $this->ticket->No_Ticket ?? 'null',
        'status' => $this->ticket->Status ?? 'null',
        'open_seconds' => $this->openTimeSeconds,
        'pending_seconds' => $this->pendingTimeSeconds,
        'total_seconds' => $this->totalTimeSeconds,
    ]);
}

    public function refreshTicket()
    {
        $now = now()->timestamp;
        if ($now - $this->lastUpdated < 500) {
            return;
        }

        if (!$this->ticket) {
            Log::error('Ticket is null in refreshTicket');
            return;
        }

        $this->ticket->refresh();
        $this->initializeTimer();

        $this->dispatch('timerStateUpdated', [
            'status' => $this->ticket->Status ?? 'OPEN',
            'openSeconds' => $this->openTimeSeconds,
            'pendingSeconds' => $this->pendingTimeSeconds,
            'totalSeconds' => $this->totalTimeSeconds,
            'startTime' => $this->ticket->Open_Time?->timestamp ?? null,
            'pendingStart' => $this->ticket->Pending_Start?->timestamp ?? null, // Tambahkan ini
            'pendingStop' => $this->ticket->Pending_Stop?->timestamp ?? null,   // Tambahkan ini
            'closedTime' => $this->ticket->Closed_Time?->timestamp ?? null,     // Tambahkan ini
            'timestamp' => $now
        ]);

        $this->lastUpdated = $now;
    }

    public function render()
    {
        if (!$this->ticket) {
            Log::error('Ticket is null in render method');
            $this->ticket = Ticket::where('No_Ticket', 'TI-0000002')->firstOrFail();
        }

        return view('livewire.ticket-timer', [
            'record' => $this->ticket, // Gunakan 'record' agar sesuai dengan Blade
            'openTimeSeconds' => $this->openTimeSeconds,
            'pendingTimeSeconds' => $this->pendingTimeSeconds,
            'totalTimeSeconds' => $this->totalTimeSeconds,
            'slaPercentage' => $this->slaPercentage ?? 0,
        ]);
    }
}
<?php
// File: app/Livewire/TicketTimer.php

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

    // Listener tetap sama
    protected $listeners = ['ticketStatusUpdated' => 'updateTimer'];

    public function getListeners()
    {
        return [
            'echo:tickets.' . ($this->ticket->No_Ticket ?? 'unknown') . ',TicketStatusUpdated' => 'updateTimer',
        ];
    }

    // Mount tetap sama
    public function mount(Ticket $ticket = null)
    {
        if (!$ticket || !$ticket->exists) {
            Log::error('Ticket not provided or does not exist in TicketTimer component', [
                'ticket_id' => $ticket->No_Ticket ?? 'null',
                'exists' => $ticket->exists ?? false,
            ]);
            $ticketId = request()->route('ticket') ?? 'TI-0000001'; // Default for safety
            $this->ticket = Ticket::where('No_Ticket', $ticketId)->firstOrFail();
        } else {
            $this->ticket = $ticket;
        }

        $this->slaPercentage = 0; // Initialize or calculate SLA if needed
        $this->initializeTimer();
    }

    // InitializeTimer tetap sama
    public function initializeTimer()
    {
        if (!$this->ticket || !$this->ticket->Open_Time) {
            Log::error('Ticket or Open_Time is null in initializeTimer', [
                'ticket_id' => $this->ticket->No_Ticket ?? 'null',
                // ... other log details
            ]);
            $this->openTimeSeconds = 0;
            $this->pendingTimeSeconds = 0;
            $this->totalTimeSeconds = 0;
        } else {
            $timer = $this->ticket->getCurrentTimer();
            $this->openTimeSeconds = $timer['open']['seconds'] ?? 0;
            $this->pendingTimeSeconds = $timer['pending']['seconds'] ?? 0;
            $this->totalTimeSeconds = $timer['total']['seconds'] ?? 0;
            Log::debug('Initialized timer values', [
                'ticket_id' => $this->ticket->No_Ticket,
                'openSeconds' => $this->openTimeSeconds,
                'pendingSeconds' => $this->pendingTimeSeconds,
                'totalSeconds' => $this->totalTimeSeconds,
                'db_pending_duration_seconds' => $this->ticket->pending_duration_seconds,
            ]);
        }
    }

    // UpdateTimer disederhanakan
    public function updateTimer()
    {
        $now = now()->timestamp;
        // Throttle updates if needed (optional, 500ms example)
        // if ($now - $this->lastUpdated < 500) {
        //     return;
        // }

        $this->ticket->refresh(); // Get latest data from DB
        $timer = $this->ticket->getCurrentTimer(); // Calculate current timer values based on DB state

        $this->openTimeSeconds = $timer['open']['seconds'];
        $this->pendingTimeSeconds = $timer['pending']['seconds'];
        $this->totalTimeSeconds = $timer['total']['seconds'];

        // HAPUS: Logika akumulasi pending_duration_seconds di sini dihapus.
        // Perhitungan ini sekarang ditangani oleh model Ticket.

        Log::debug('Updated timer values from backend', [
            'ticket_id' => $this->ticket->No_Ticket,
            'status' => $this->ticket->Status,
            'openSeconds' => $this->openTimeSeconds,
            'pendingSeconds' => $this->pendingTimeSeconds,
            'totalSeconds' => $this->totalTimeSeconds,
            'db_pending_duration_seconds' => $this->ticket->pending_duration_seconds,
            'Pending_Start' => $this->ticket->Pending_Start?->timestamp,
            'Pending_Stop' => $this->ticket->Pending_Stop?->timestamp,
            'now' => $now,
        ]);

        // Dispatch event dengan data timer yang sudah dihitung
        $this->dispatch('timerStateUpdated', [
            'status' => $this->ticket->Status,
            'openSeconds' => $this->openTimeSeconds, // Kirim nilai yang sudah dihitung
            'pendingSeconds' => $this->pendingTimeSeconds, // Kirim nilai yang sudah dihitung
            'totalSeconds' => $this->totalTimeSeconds, // Kirim nilai yang sudah dihitung
            'startTime' => $this->ticket->Open_Time?->timestamp,
            'pendingStart' => $this->ticket->Pending_Start?->timestamp,
            'pendingStop' => $this->ticket->Pending_Stop?->timestamp,
            'closedTime' => $this->ticket->Closed_Time?->timestamp,
            'pendingDurationSeconds' => $this->ticket->pending_duration_seconds ?? 0, // Kirim akumulasi dari DB
            'timestamp' => $now
        ]);

        $this->lastUpdated = $now;
    }

    // Render tetap sama
    public function render()
    {
        if (!$this->ticket) {
            Log::error('Ticket is null in render method');
            // Handle error appropriately, maybe redirect or show error message
            // For now, try fetching a default ticket again
            $this->ticket = Ticket::where('No_Ticket', 'TI-0000001')->firstOrFail();
            $this->initializeTimer(); // Re-initialize timer if ticket was null
        }

        return view('livewire.ticket-timer', [
            'record' => $this->ticket,
            'openTimeSeconds' => $this->openTimeSeconds,
            'pendingTimeSeconds' => $this->pendingTimeSeconds,
            'totalTimeSeconds' => $this->totalTimeSeconds,
            'slaPercentage' => $this->slaPercentage ?? 0,
        ]);
    }
}
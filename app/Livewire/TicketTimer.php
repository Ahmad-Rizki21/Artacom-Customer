<?php
// File: app/Livewire/TicketTimer.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\Ticket;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketTimer extends Component
{
    public $ticket;
    public bool $isRunning = true;
    public int $openTimeSeconds = 0;
    public int $pendingTimeSeconds = 0;
    public int $totalTimeSeconds = 0;
    
    public $timerDisplay = [
        'open' => '00:00:00',
        'pending' => '00:00:00',
        'total' => '00:00:00'
    ];
    public $statusInfo = [];
    private $lastUpdated = 0;

    public function mount(Ticket $ticket = null)
    {
        if (!$ticket || !$ticket->exists) {
            Log::error('Ticket not provided or does not exist in TicketTimer component', [
                'ticket_id' => $ticket->No_Ticket ?? 'null',
                'exists' => $ticket->exists ?? false,
            ]);
            $ticketId = request()->route('ticket') ?? 'TI-0000001';
            $this->ticket = Ticket::where('No_Ticket', $ticketId)->firstOrFail();
        } else {
            $this->ticket = $ticket;
        }

        $this->initializeTimer();
        $this->updateStatusInfo();
    }

    public function refreshTimer()
    {
        $this->ticket->refresh();
        $this->initializeTimer();
        $this->updateStatusInfo();
    }

    public function initializeTimer()
    {
        if (!$this->ticket || !$this->ticket->Open_Time) {
            Log::error('Ticket or Open_Time is null in initializeTimer', [
                'ticket_id' => $this->ticket->No_Ticket ?? 'null',
            ]);
            $this->openTimeSeconds = 0;
            $this->pendingTimeSeconds = 0;
            $this->totalTimeSeconds = 0;
        } else {
            $timer = $this->ticket->getCurrentTimer();
            $this->openTimeSeconds = $timer['open']['seconds'] ?? 0;
            $this->pendingTimeSeconds = $timer['pending']['seconds'] ?? 0;
            $this->totalTimeSeconds = $timer['total']['seconds'] ?? 0;
            
            $this->updateTimerDisplay();
            
            Log::debug('Initialized timer values', [
                'ticket_id' => $this->ticket->No_Ticket,
                'status' => $this->ticket->Status,
                'openSeconds' => $this->openTimeSeconds,
                'pendingSeconds' => $this->pendingTimeSeconds,
                'totalSeconds' => $this->totalTimeSeconds,
            ]);
        }
    }

    private function updateTimerDisplay()
    {
        $this->timerDisplay = [
            'open' => $this->formatTime($this->openTimeSeconds),
            'pending' => $this->formatTime($this->pendingTimeSeconds),
            'total' => $this->formatTime($this->totalTimeSeconds)
        ];
    }

    private function updateStatusInfo()
    {
        $this->statusInfo = [
            'opened_at' => $this->ticket->Open_Time ? $this->ticket->Open_Time->format('M j, Y g:i A') : 'Unknown',
            'opened_by' => $this->ticket->Open_By ?? 'System',
            'current_status' => $this->ticket->Status,
            'pending_since' => $this->ticket->Pending_Start ? $this->ticket->Pending_Start->format('M j, Y g:i A') : null,
            'closed_at' => $this->ticket->Closed_Time ? $this->ticket->Closed_Time->format('M j, Y g:i A') : null,
        ];
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds < 0) {
            return '00:00:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function render()
    {
        if (!$this->ticket) {
            Log::error('Ticket is null in render method');
            $this->ticket = Ticket::where('No_Ticket', 'TI-0000001')->firstOrFail();
            $this->initializeTimer();
        }

        return view('livewire.ticket-timer', [
            'record' => $this->ticket,
            'openTimeSeconds' => $this->openTimeSeconds,
            'pendingTimeSeconds' => $this->pendingTimeSeconds,
            'totalTimeSeconds' => $this->totalTimeSeconds,
            'timerDisplay' => $this->timerDisplay,
            'statusInfo' => $this->statusInfo,
        ]);
    }
}
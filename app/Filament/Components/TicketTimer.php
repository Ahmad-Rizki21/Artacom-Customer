<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Component;
use Livewire\Component as LivewireComponent;
use Carbon\Carbon;

class TicketTimer extends LivewireComponent
{
    public $ticket;
    public $openDuration = '00:00:00';
    public $totalDuration = '00:00:00';

    public function mount($ticket)
    {
        $this->ticket = $ticket;
        $this->calculateDurations();
    }

    public function calculateDurations()
    {
        if (!$this->ticket || !$this->ticket->Open_Time) {
            return;
        }

        try {
            $openTime = Carbon::parse($this->ticket->Open_Time);
            $now = now();
            
            // Calculate Open Duration
            $openSeconds = $now->diffInSeconds($openTime);
            $this->openDuration = $this->formatDuration($openSeconds);

            // Calculate Total Duration
            $totalSeconds = $openSeconds;
            if ($this->ticket->Pending_Start && $this->ticket->Pending_Stop) {
                $pendingStart = Carbon::parse($this->ticket->Pending_Start);
                $pendingStop = Carbon::parse($this->ticket->Pending_Stop);
                $pendingSeconds = $pendingStop->diffInSeconds($pendingStart);
                $totalSeconds -= $pendingSeconds;
            }
            $this->totalDuration = $this->formatDuration($totalSeconds);
        } catch (\Exception $e) {
            $this->openDuration = '00:00:00';
            $this->totalDuration = '00:00:00';
        }
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function render()
    {
        $this->calculateDurations();
        
        return view('filament.components.ticket-timer', [
            'ticket' => $this->ticket,
            'openDuration' => $this->openDuration,
            'totalDuration' => $this->totalDuration
        ]);
    }
}
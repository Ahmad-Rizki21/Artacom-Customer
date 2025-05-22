<?php

namespace App\Filament\Components;

use Livewire\Component;
use Carbon\Carbon;

class TicketTimer extends Component
{
    public $record;
    public $openDuration;
    public $pendingDuration;
    public $totalDuration;
    public $lastUpdate;

    protected $listeners = [
        'refresh' => 'refreshTimer',
        'statusUpdated' => 'updateStatus'
    ];

    public function mount()
    {
        $this->refreshTimer();
    }

    public function updateStatus($newStatus)
    {
        $this->record->Status = $newStatus;
        $this->record->refresh(); // Refresh the record to get updated timestamps
        $this->refreshTimer();
    }

    public function refreshTimer()
    {
        $this->lastUpdate = now()->timestamp;
        $this->openDuration = $this->record->getOpenDurationAttribute();
        $this->pendingDuration = $this->record->getPendingDurationAttribute();
        $this->totalDuration = $this->record->getTotalDurationAttribute();
    }

    public function getPollingInterval()
    {
        return 1000; // Update every second
    }

    public function dehydrate()
    {
        $this->dispatch('poll-tick');
    }

    public function render()
{
    return view('livewire.ticket-timer', [
        'formattedOpenTime' => $this->formatTime($this->openTimeSeconds),
        'formattedPendingTime' => $this->formatTime($this->pendingTimeSeconds),
        'formattedTotalTime' => $this->formatTime($this->totalTimeSeconds),
        'slaPercentage' => $this->slaPercentage ?? 0,
        'slaColor' => $this->getSlaColor(),
    ]);
}

}
<?php

namespace App\Filament\Components;

use Livewire\Component as LivewireComponent;
use Carbon\Carbon;
use App\Models\AlfaLawson\Ticket;

class TicketTimer extends LivewireComponent
{
    public $record;
    public $openDuration = '00:00:00';
    public $totalDuration = '00:00:00';
    public $pendingDuration = '00:00:00';

    public function mount($record = null)
    {
        $this->record = $record ?? $this->getRecordFromContext();
        \Illuminate\Support\Facades\Log::info('TicketTimer mount called', [
            'record' => $this->record ? $this->record->toArray() : null,
        ]);
        $this->calculateDurations();
    }

    protected function getRecordFromContext()
    {
        try {
            return $this->getParentComponent()->getRecord();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to get record from context', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function calculateDurations()
    {
        if (!$this->record instanceof Ticket || !$this->record->Open_Time) {
            \Illuminate\Support\Facades\Log::warning('Invalid record or Open_Time is null in TicketTimer', [
                'record' => $this->record ? $this->record->toArray() : null,
            ]);
            $this->openDuration = '00:00:00';
            $this->totalDuration = '00:00:00';
            $this->pendingDuration = '00:00:00';
            return;
        }

        try {
            $openTime = Carbon::parse($this->record->Open_Time, 'Asia/Jakarta');
            $now = Carbon::now('Asia/Jakarta');

            // Calculate Open Duration (stops at Pending_Start or Closed_Time)
            $openEndTime = $now;
            if ($this->record->Pending_Start) {
                $openEndTime = Carbon::parse($this->record->Pending_Start, 'Asia/Jakarta');
            } elseif ($this->record->Status === Ticket::STATUS_CLOSED && $this->record->Closed_Time) {
                $openEndTime = Carbon::parse($this->record->Closed_Time, 'Asia/Jakarta');
            }
            $openSeconds = max(0, $openEndTime->diffInSeconds($openTime));
            $this->openDuration = $this->formatDuration($openSeconds);

            // Calculate Pending Duration (show even if CLOSED, if Pending_Stop exists)
            $this->pendingDuration = '00:00:00';
            if ($this->record->Pending_Start) {
                $pendingEndTime = null;
                if ($this->record->Pending_Stop) {
                    $pendingEndTime = Carbon::parse($this->record->Pending_Stop, 'Asia/Jakarta');
                } elseif ($this->record->Status === Ticket::STATUS_PENDING) {
                    $pendingEndTime = $now;
                }
                if ($pendingEndTime) {
                    $pendingSeconds = max(0, $pendingEndTime->diffInSeconds(Carbon::parse($this->record->Pending_Start, 'Asia/Jakarta')));
                    $this->pendingDuration = $this->formatDuration($pendingSeconds);
                }
            }

            // Calculate Total Duration
            $totalEndTime = $this->record->Status === Ticket::STATUS_CLOSED && $this->record->Closed_Time 
                ? Carbon::parse($this->record->Closed_Time, 'Asia/Jakarta') 
                : $now;
            $totalSeconds = max(0, $totalEndTime->diffInSeconds($openTime));
            if ($this->record->Pending_Start && $this->record->Pending_Stop) {
                $pendingSeconds = Carbon::parse($this->record->Pending_Stop, 'Asia/Jakarta')->diffInSeconds(Carbon::parse($this->record->Pending_Start, 'Asia/Jakarta'));
                $totalSeconds -= $pendingSeconds;
            } elseif ($this->record->Status === Ticket::STATUS_PENDING && $this->record->Pending_Start) {
                $pendingSeconds = $now->diffInSeconds(Carbon::parse($this->record->Pending_Start, 'Asia/Jakarta'));
                $totalSeconds -= $pendingSeconds;
            }
            $this->totalDuration = $this->formatDuration(max(0, $totalSeconds));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error calculating durations in TicketTimer', [
                'error' => $e->getMessage(),
                'record' => $this->record ? $this->record->toArray() : null,
            ]);
            $this->openDuration = '00:00:00';
            $this->totalDuration = '00:00:00';
            $this->pendingDuration = '00:00:00';
        }
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function updateTimers()
    {
        $this->calculateDurations();
    }

    public function render()
    {
        $this->calculateDurations();
        
        return view('filament.components.ticket-timer', [
            'record' => $this->record,
            'openDuration' => $this->openDuration,
            'totalDuration' => $this->totalDuration,
            'pendingDuration' => $this->pendingDuration,
            'openTime' => $this->record && $this->record->Open_Time ? $this->record->Open_Time : null,
            'pendingStart' => $this->record && $this->record->Pending_Start ? $this->record->Pending_Start : null,
            'pendingStop' => $this->record && $this->record->Pending_Stop ? $this->record->Pending_Stop : null,
            'closedTime' => $this->record && $this->record->Closed_Time ? $this->record->Closed_Time : null,
            'status' => $this->record ? $this->record->Status : null,
        ]);
    }

    protected function getParentComponent()
    {
        return $this->getParent();
    }
}
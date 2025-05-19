<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\Ticket;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Facades\Log;

class EditActionModal extends Component
{
    public $isOpen = false;
    public $actionId = null;
    public $actionTaken = '';
    public $actionDescription = '';
    public $ticket;

    protected $listeners = [
        'openEditModal' => 'openModal',
    ];

    protected $rules = [
        'actionTaken' => 'required|in:Start Clock,Pending Clock,Closed,Note',
        'actionDescription' => 'required|string|min:3',
    ];

    public function openModal($actionId, $ticketId)
    {
        Log::info('Opening Livewire modal for action ID: ' . $actionId);
        $this->actionId = $actionId;
        $this->ticket = Ticket::findOrFail($ticketId);
        $action = TicketAction::findOrFail($actionId);

        $this->actionTaken = $action->Action_Taken;
        $this->actionDescription = $action->Action_Description;
        $this->isOpen = true;

        Log::info('Modal opened with data:', [
            'action_taken' => $this->actionTaken,
            'action_description' => $this->actionDescription,
        ]);
    }

    public function save()
    {
        $this->validate();

        try {
            Log::info('Saving action ID: ' . $this->actionId, [
                'action_taken' => $this->actionTaken,
                'action_description' => $this->actionDescription,
            ]);

            $this->ticket->updateAction($this->actionId, [
                'action_taken' => $this->actionTaken,
                'action_description' => $this->actionDescription,
            ]);

            $this->isOpen = false;
            $this->resetForm();
            session()->flash('message', 'Action updated successfully.');
            $this->dispatch('actionUpdated');
        } catch (\Exception $e) {
            Log::error('Error saving action: ' . $e->getMessage());
            session()->flash('error', 'Error updating action: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    public function log($message)
    {
        Log::info($message);
    }

    private function resetForm()
    {
        $this->actionId = null;
        $this->actionTaken = '';
        $this->actionDescription = '';
    }

    public function render()
    {
        return view('livewire.edit-action-modal');
    }
}
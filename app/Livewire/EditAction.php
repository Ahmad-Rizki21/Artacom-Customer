<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AlfaLawson\TicketAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;

class EditAction extends Component
{
    public $actionId;
    public $ticket;
    public $actionData;

    public function mount($actionId, $ticket = null)
    {
        Log::info("EditAction component mounted with actionId: {$actionId}");
        $this->actionId = $actionId;
        $this->ticket = $ticket;
        $this->actionData = TicketAction::findOrFail($actionId)->toArray();
    }

    protected function getActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Edit Action')
                ->modalSubmitActionLabel('Save Changes')
                ->modalWidth('lg')
                ->form([
                    Select::make('action_taken')
                        ->label('Action Status')
                        ->options([
                            'Start Clock' => 'Start Clock',
                            'Pending Clock' => 'Pending Clock',
                            'Closed' => 'Closed',
                            'Note' => 'Note',
                        ])
                        ->default($this->actionData['Action_Taken'])
                        ->required(),
                    Textarea::make('action_description')
                        ->label('Description')
                        ->default($this->actionData['Action_Description'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $action = TicketAction::findOrFail($this->actionId);

                    try {
                        Log::info("Updating action with data: " . json_encode($data));

                        $action->update([
                            'Action_Taken' => $data['action_taken'],
                            'Action_Description' => $data['action_description'],
                        ]);

                        if ($this->ticket && $data['action_taken'] !== 'Note') {
                            if ($data['action_taken'] === 'Pending Clock') {
                                $this->ticket->update([
                                    'Status' => 'PENDING',
                                    'Pending_Start' => now(),
                                    'Pending_Reason' => $data['action_description']
                                ]);
                            } elseif ($data['action_taken'] === 'Start Clock') {
                                $this->ticket->update([
                                    'Status' => 'OPEN',
                                    'Pending_Stop' => now()
                                ]);
                            } elseif ($data['action_taken'] === 'Closed') {
                                $this->ticket->update([
                                    'Status' => 'CLOSED',
                                    'Closed_Time' => now(),
                                    'Action_Summry' => $data['action_description']
                                ]);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Action updated successfully')
                            ->send();

                        $this->dispatch('refresh-ticket');

                    } catch (\Exception $e) {
                        Log::error("Error updating action: " . $e->getMessage());

                        Notification::make()
                            ->danger()
                            ->title('Error updating action')
                            ->body($e->getMessage())
                            ->send();

                        throw new Halt();
                    }
                })
                ->modalSubmitAction(false) // Disable default submit to handle it manually
                ->mountUsing(function (Action $action, array $arguments) {
                    Log::info("Mounting action with arguments: " . json_encode($arguments));
                    $action->fillForm([
                        'action_taken' => $this->actionData['Action_Taken'],
                        'action_description' => $this->actionData['Action_Description'],
                    ]);
                }),
        ];
    }

    public function render()
    {
        return view('livewire.edit-action');
    }
}
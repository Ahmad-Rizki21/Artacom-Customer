<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use App\Models\AlfaLawson\TicketAction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Filament\Components\TicketTimer;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function addAction(array $data): void
    {
        try {
            if (empty($data['new_action_status']) || empty($data['new_action_description'])) {
                throw new \Exception('Action status and description are required.');
            }

            $updateData = [];
            if ($data['new_action_status'] === 'Pending Clock') {
                $updateData = [
                    'Status' => 'PENDING',
                    'Pending_Start' => now(),
                    'Pending_Reason' => $data['new_action_description'],
                ];
            } elseif ($data['new_action_status'] === 'Start Clock') {
                $updateData = [
                    'Status' => 'OPEN',
                    'Pending_Stop' => now(),
                ];
            } elseif ($data['new_action_status'] === 'Closed') {
                $updateData = [
                    'Status' => 'CLOSED',
                    'Closed_Time' => now(),
                    'Action_Summry' => $data['new_action_description'],
                ];
            }

            if (!empty($updateData)) {
                $this->record->update($updateData);
                $this->dispatch('statusUpdated', $this->record->Status);
                $this->dispatch('refresh');
            }

            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => $data['new_action_status'],
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1',
                'Action_Description' => $data['new_action_description'],
            ]);

            Notification::make()
                ->success()
                ->title('Action Added')
                ->body('The action was added successfully.')
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->No_Ticket]), navigate: false);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Adding Action')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function renderTicketTimer()
    {
        return TicketTimer::make()
            ->ticket($this->record)
            ->render();
    }

    protected function getHeaderActions(): array
    {
        // Define the escalation levels and their order
        $levelOrder = [
            'Level 1' => ['order' => 1, 'role' => 'NOC'],
            'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
            'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
            'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
            'Level 5' => ['order' => 5, 'role' => 'Engineer'],
            'Level 6' => ['order' => 6, 'role' => 'Management'],
        ];

        // Get the current user and ticket levels
        $currentUserLevel = Auth::user()->Level ?? 'Level 1';
        $currentTicketLevel = $this->record->Open_Level ?? 'Level 1';

        // Get the latest escalation level from TicketAction history
        $latestEscalation = $this->record->actions()
            ->where('Action_Taken', 'Escalation')
            ->orderBy('Action_Time', 'desc')
            ->first();

        $currentEscalationLevel = $latestEscalation ? $latestEscalation->Action_Level : $currentTicketLevel;

        // Filter escalation options to only show levels higher than the current user and escalation levels
        $escalationOptions = [];
        foreach ($levelOrder as $level => $info) {
            if ($info['order'] > max($levelOrder[$currentUserLevel]['order'] ?? 1, $levelOrder[$currentEscalationLevel]['order'] ?? 1)) {
                $escalationOptions[$level] = $info['role'];
            }
        }

        return [
            Actions\EditAction::make()
                ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record])),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Ticket')
                ->modalDescription('Apakah anda yakin ingin menghapus tiket ini?')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successNotification(
                    Notification::make()
                        ->title('Ticket Deleted')
                        ->body('The ticket has been successfully deleted.')
                        ->success()
                ),
            Actions\Action::make('escalate')
                ->label('Eskalasi')
                ->icon('heroicon-o-arrow-up')
                ->color('warning')
                ->visible(fn () => !empty($escalationOptions)) // Hide button if no escalation options are available
                ->form([
                    Select::make('escalation_level')
                        ->label('Escalation Level')
                        ->options($escalationOptions)
                        ->required()
                        ->prefixIcon('heroicon-m-exclamation-triangle')
                        ->native(false)
                        ->extraAttributes([
                            'class' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-lg',
                        ]),
                    Textarea::make('escalation_description')
                        ->label('Description')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->escalateTicket($data))
                ->modalSubmitActionLabel('Submit Escalation'),
            Actions\Action::make('addAction')
                ->label('Add Action')
                ->icon('heroicon-o-plus-circle')
                ->form([
                    Select::make('new_action_status')
                        ->label('Action Status')
                        ->options([
                            'Start Clock' => 'Start Clock',
                            'Pending Clock' => 'Pending Clock',
                            'Closed' => 'Closed',
                            'Note' => 'Note',
                        ])
                        ->required(),
                    Textarea::make('new_action_description')
                        ->label('Description')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->addAction($data))
                ->modalSubmitActionLabel('Submit')
                ->extraAttributes(['wire:submit.prevent' => 'addAction']),
        ];
    }

    public function escalateTicket(array $data): void
    {
        try {
            // Validate data
            if (empty($data['escalation_level']) || empty($data['escalation_description'])) {
                throw new \Exception('Escalation level and description are required.');
            }

            // Define the escalation levels and their order
            $levelOrder = [
                'Level 1' => ['order' => 1, 'role' => 'NOC'],
                'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
                'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
                'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
                'Level 5' => ['order' => 5, 'role' => 'Engineer'],
                'Level 6' => ['order' => 6, 'role' => 'Management'],
            ];

            // Get the current user and ticket levels
            $currentUserLevel = Auth::user()->Level ?? 'Level 1';
            $currentTicketLevel = $this->record->Open_Level ?? 'Level 1';

            // Get the latest escalation level from TicketAction history
            $latestEscalation = $this->record->actions()
                ->where('Action_Taken', 'Escalation')
                ->orderBy('Action_Time', 'desc')
                ->first();

            $currentEscalationLevel = $latestEscalation ? $latestEscalation->Action_Level : $currentTicketLevel;

            // Validate that the escalation level is higher than the current user and escalation levels
            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentUserLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to your current level (' . $levelOrder[$currentUserLevel]['role'] . ').');
            }

            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentEscalationLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to the current escalation level (' . $levelOrder[$currentEscalationLevel]['role'] . ').');
            }

            // Update ticket status and Action_Summry (do not modify Open_Level as it represents the original level)
            $this->record->update([
                'Status' => 'OPEN',
                'Action_Summry' => $data['escalation_description'],
            ]);

            // Add escalation action to history
            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => 'Escalation',
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => $data['escalation_level'],
                'Action_Description' => $data['escalation_description'],
            ]);

            // Emit event to update UI
            $this->dispatch('statusUpdated', $this->record->Status);
            $this->dispatch('refresh');

            // Send success notification
            Notification::make()
                ->success()
                ->title('Ticket Escalated')
                ->body('The ticket has been successfully escalated to ' . $levelOrder[$data['escalation_level']]['role'] . '.')
                ->send();

            // Redirect to refresh the page
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->No_Ticket]), navigate: false);
        } catch (\Exception $e) {
            // Send error notification
            Notification::make()
                ->danger()
                ->title('Error Escalating Ticket')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Grid::make(3)
                    ->schema([
                        \Filament\Infolists\Components\Grid::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Ticket Information')
                                    ->schema([
                                        TextEntry::make('No_Ticket')
                                            ->label('No Ticket'),
                                        TextEntry::make('Customer'),
                                        TextEntry::make('Site_ID')
                                            ->label('Site ID')
                                            ->default('-')
                                            ->getStateUsing(fn ($record) => $record->remote?->Site_ID ?? $record->Site_ID ?? '-'),
                                        TextEntry::make('Nama_Toko')
                                            ->label('Alamat')
                                            ->default('-')
                                            ->getStateUsing(fn ($record) => $record->remote?->Nama_Toko ?? '-'),
                                        TextEntry::make('DC')
                                            ->label('DC')
                                            ->default('-')
                                            ->getStateUsing(fn ($record) => $record->remote?->DC ?? '-'),
                                        TextEntry::make('IP_Address')
                                            ->label('IP Address')
                                            ->default('-')
                                            ->getStateUsing(function ($record) {
                                                $ip = $record->remote?->IP_Address ?? '-';
                                                return $ip !== '-' ? $ip : $ip;
                                            })
                                            ->url(function ($record) {
                                                $ip = $record->remote?->IP_Address ?? '';
                                                return $ip ? "http://{$ip}:8090" : null;
                                            })
                                            ->openUrlInNewTab(),
                                        TextEntry::make('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'OPEN' => 'warning',
                                                'PENDING' => 'info',
                                                'CLOSED' => 'success',
                                                default => 'secondary',
                                            }),
                                        TextEntry::make('Open_Level')
                                            ->label('Open Level')
                                            ->getStateUsing(function ($record) {
                                                $levelOrder = [
                                                    'Level 1' => 'NOC',
                                                    'Level 2' => 'SPV NOC',
                                                    'Level 3' => 'Teknisi',
                                                    'Level 4' => 'SPV Teknisi',
                                                    'Level 5' => 'Engineer',
                                                    'Level 6' => 'Management',
                                                ];
                                                return $levelOrder[$record->Open_Level] ?? $record->Open_Level;
                                            }),
                                        TextEntry::make('current_escalation_level')
                                            ->label('Escalated to')
                                            ->getStateUsing(function ($record) {
                                                $levelOrder = [
                                                    'Level 1' => 'NOC',
                                                    'Level 2' => 'SPV NOC',
                                                    'Level 3' => 'Teknisi',
                                                    'Level 4' => 'SPV Teknisi',
                                                    'Level 5' => 'Engineer',
                                                    'Level 6' => 'Management',
                                                ];
                                                $latestEscalation = $record->actions()
                                                    ->where('Action_Taken', 'Escalation')
                                                    ->orderBy('Action_Time', 'desc')
                                                    ->first();
                                                return $latestEscalation ? $levelOrder[$latestEscalation->Action_Level] ?? $latestEscalation->Action_Level : '-';
                                            }),
                                        TextEntry::make('Catagory')
                                            ->label('Category'),
                                    ])
                                    ->columns(3),

                                Section::make('Problem Details')
                                    ->schema([
                                        TextEntry::make('Problem')
                                            ->columnSpanFull(),
                                        TextEntry::make('Reported_By')
                                            ->label('Reported By')
                                            ->default('-'),
                                        TextEntry::make('Pic')
                                            ->label('PIC')
                                            ->getStateUsing(fn ($record) => $record->Pic ?? '-'),
                                        TextEntry::make('Tlp_Pic')
                                            ->label('PIC Phone')
                                            ->getStateUsing(fn ($record) => $record->Tlp_Pic ?? '-'),
                                    ])
                                    ->columns(2),

                                Section::make('Progress History')
                                    ->schema([
                                        ViewEntry::make('progress_timeline')
                                            ->view('filament.resources.ticket-progress-timeline')
                                            ->viewData([
                                                'record' => $this->record,
                                                'actions' => $this->record->actions()->orderBy('Action_Time', 'desc')->get(),
                                            ]),
                                    ]),
                            ]),

                        Section::make('Timer Information')
                            ->columnSpan(1)
                            ->schema([
                                ViewEntry::make('timer')
                                    ->view('livewire.ticket-timer')
                                    ->viewData(['record' => $this->record]),
                            ]),
                    ]),
            ]);
    }
}
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
            // Validate required fields
            if (empty($data['new_action_status']) || empty($data['new_action_description'])) {
                throw new \Exception('Action status and description are required.');
            }

            // Create a new ticket action
            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => $data['new_action_status'],
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1',
                'Action_Description' => $data['new_action_description'],
            ]);

            // Update ticket status based on action
            if ($data['new_action_status'] !== 'Note') {
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
                    // Emit events to update the UI and timer
                    $this->dispatch('statusUpdated', $this->record->Status);
                    $this->dispatch('refresh');
                }
            }

            // Send success notification
            Notification::make()
                ->success()
                ->title('Action Added')
                ->body('The action was added successfully.')
                ->send();

            // Redirect ke halaman yang sama untuk refresh browser
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->No_Ticket]), navigate: false);
        } catch (\Exception $e) {
            // Send error notification
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
                                            ->label('Open Level'),
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
                                            ->viewData(['record' => $this->record, 'livewire' => $this]),
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
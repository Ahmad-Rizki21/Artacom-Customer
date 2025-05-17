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
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
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
                ])
                ->action(function (array $data) {
                    $ticket = $this->record;

                    try {
                        // Create the ticket action
                        TicketAction::create([
                            'No_Ticket' => $ticket->No_Ticket,
                            'Action_Taken' => $data['new_action_status'],
                            'Action_Time' => now(),
                            'Action_By' => Auth::user()->name,
                            'Action_Level' => null, // Leave as NULL, derive from User model if needed
                            'Action_Description' => $data['new_action_description'], // Store description here
                        ]);

                        // Only update ticket status if it's not a note
                        if ($data['new_action_status'] !== 'Note') {
                            if ($data['new_action_status'] === 'Pending Clock') {
                                $ticket->update([
                                    'Status' => 'PENDING',
                                    'Pending_Start' => now(),
                                    'Pending_Reason' => $data['new_action_description']
                                ]);
                            } elseif ($data['new_action_status'] === 'Start Clock') {
                                $ticket->update([
                                    'Status' => 'OPEN',
                                    'Pending_Stop' => now()
                                ]);
                            } elseif ($data['new_action_status'] === 'Closed') {
                                $ticket->update([
                                    'Status' => 'CLOSED',
                                    'Closed_Time' => now(),
                                    'Action_Summry' => $data['new_action_description']
                                ]);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title($data['new_action_status'] === 'Note' ? 'Note added successfully' : 'Action added successfully')
                            ->send();

                        return redirect()->to($this->getResource()::getUrl('view', ['record' => $ticket]));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error adding action')
                            ->body($e->getMessage())
                            ->send();

                        throw new Halt();
                    }
                })
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $ticket = $this->record;

        // Set initial status to "Open Clock" and create initial action if not exists
        if ($ticket->actions()->where('Action_Taken', 'Start Clock')->doesntExist() && $ticket->Status === null) {
            try {
                $ticket->update([
                    'Status' => 'OPEN',
                    'Open_Time' => now(),
                ]);
                TicketAction::create([
                    'No_Ticket' => $ticket->No_Ticket,
                    'Action_Taken' => 'Start Clock',
                    'Action_Level' => null, // Leave as NULL
                    'Action_Description' => $ticket->Problem, // Move problem to description
                    'Action_By' => Auth::user()->name ?? 'system',
                    'Action_Time' => $ticket->Open_Time,
                ]);

                Log::info("Initial TicketAction created for ticket {$ticket->No_Ticket} on view: Action_Description = {$ticket->Problem}");
            } catch (\Exception $e) {
                Log::error("Failed to create initial TicketAction for ticket {$ticket->No_Ticket} on view: " . $e->getMessage());
            }
        }

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
                                            ->label('Remote'),
                                        TextEntry::make('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'OPEN' => 'warning',
                                                'PENDING' => 'info',
                                                'CLOSED' => 'success',
                                                default => 'secondary',
                                            }),
                                        TextEntry::make('Open_Level'),
                                        TextEntry::make('Catagory'),
                                    ])
                                    ->columns(3),

                                Section::make('Problem Details')
                                    ->schema([
                                        TextEntry::make('Problem')
                                            ->columnSpanFull(),
                                        TextEntry::make('Reported_By'),
                                        TextEntry::make('pic'),
                                        TextEntry::make('tlp_pic'),
                                    ])
                                    ->columns(2),

                                Section::make('Progress History')
                                ->schema([
                                    ViewEntry::make('progress_timeline')
                                        ->view('filament.resources.ticket-progress-timeline')
                                        ->viewData(['record' => $this->record])
                                ])
                            ]),

                        Section::make('Timer Information')
                            ->columnSpan(1)
                            ->schema([
                                ViewEntry::make('timer')
                                    ->view('components.ticket-timer')
                                    ->viewData([
                                        'record' => $this->record
                                    ])
                            ]),
                    ]),
            ]);
    }
}
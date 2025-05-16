<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\AlfaLawson\TicketAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\ViewEntry;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;
    
    // Tambahkan property untuk polling
    protected $listeners = ['refresh-timer' => '$refresh'];

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
                            'Completed' => 'Completed',
                        ])
                        ->required(),
                    Textarea::make('new_action_description')
                        ->label('Description')
                        ->required()
                ])
                ->action(function (array $data) {
                    $ticket = $this->record;
                    
                    TicketAction::create([
                        'No_Ticket' => $ticket->No_Ticket,
                        'Action_Taken' => $data['new_action_status'],
                        'Action_Time' => now(),
                        'Action_By' => Auth::user()->name,
                        'Action_Level' => $data['new_action_description'],
                    ]);

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
                    } elseif ($data['new_action_status'] === 'Completed') {
                        $ticket->update([
                            'Status' => 'CLOSED',
                            'Closed_Time' => now(),
                            'Action_Summry' => $data['new_action_description']
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title('Action added successfully')
                        ->send();

                    $this->refresh();
                })
        ];
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
                                        TextEntry::make('actions')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $actions = $record->actions()
                                                    ->orderByDesc('Action_Time')
                                                    ->get();

                                                if ($actions->isEmpty()) {
                                                    return '<div class="text-gray-500">No actions recorded yet.</div>';
                                                }

                                                $html = '<div class="space-y-4">';
                                                foreach ($actions as $action) {
                                                    $dateTime = Carbon::parse($action->Action_Time)->format('d M Y H:i');
                                                    $html .= "
                                                        <div class='p-4 bg-gray-50 rounded-lg'>
                                                            <div class='flex justify-between'>
                                                                <span class='font-medium'>{$action->Action_Taken}</span>
                                                                <span class='text-sm text-gray-600'>{$dateTime}</span>
                                                            </div>
                                                            <div class='mt-2'>{$action->Action_Level}</div>
                                                            <div class='mt-1 text-sm text-gray-600'>By: {$action->Action_By}</div>
                                                        </div>
                                                    ";
                                                }
                                                $html .= '</div>';
                                                return $html;
                                            }),
                                    ]),
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
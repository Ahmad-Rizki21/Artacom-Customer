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
use App\Models\AlfaLawson\TicketEvidence;
use Filament\Forms\Components\FileUpload;


use Barryvdh\DomPDF\Facade\Pdf;

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
                    // TAMBAHAN: Mengisi Problem_Summary saat tiket ditutup
                    'Problem_Summary' => $data['new_action_description'],
                ];
                
                // Pastikan durasi timer tersimpan saat ditutup
                $currentTimer = $this->record->getCurrentTimer(true);
                $updateData['open_duration_seconds'] = $currentTimer['open']['seconds'];
                $updateData['pending_duration_seconds'] = $currentTimer['pending']['seconds'];
                $updateData['total_duration_seconds'] = $currentTimer['total']['seconds'];
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
    ->visible(function () {
        $levelOrder = [
            'Level 1' => ['order' => 1, 'role' => 'NOC'],
            'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
            'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
            'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
            'Level 5' => ['order' => 5, 'role' => 'Engineer'],
            'Level 6' => ['order' => 6, 'role' => 'Management'],
        ];
        
        $currentUserLevel = Auth::user()->Level ?? 'Level 1';
        $currentEscalationLevel = $this->record->Current_Escalation_Level ?? $this->record->Open_Level ?? 'Level 1';
        
        foreach ($levelOrder as $level => $info) {
            if ($info['order'] > max(
                $levelOrder[$currentUserLevel]['order'] ?? 1,
                $levelOrder[$currentEscalationLevel]['order'] ?? 1
            )) {
                return true;
            }
        }
        return false;
    })
    ->form([
        Select::make('escalation_level')
            ->label('Escalation Level')
            ->options(function () {
                $levelOrder = [
                    'Level 1' => ['order' => 1, 'role' => 'NOC'],
                    'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
                    'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
                    'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
                    'Level 5' => ['order' => 5, 'role' => 'Engineer'],
                    'Level 6' => ['order' => 6, 'role' => 'Management'],
                ];
                
                $currentUserLevel = Auth::user()->Level ?? 'Level 1';
                $currentEscalationLevel = $this->record->Current_Escalation_Level ?? $this->record->Open_Level ?? 'Level 1';
                
                $options = [];
                foreach ($levelOrder as $level => $info) {
                    if ($info['order'] > max(
                        $levelOrder[$currentUserLevel]['order'] ?? 1,
                        $levelOrder[$currentEscalationLevel]['order'] ?? 1
                    )) {
                        $options[$level] = $info['role'];
                    }
                }
                return $options;
            })
            ->required()
            ->native(false),
        Textarea::make('escalation_description')
            ->label('Escalation Description')
            ->required()
            ->rows(3),
    ])
    ->action(function (array $data) {
        $this->escalateTicket($data);
    })
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
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            if ($state === 'Closed') {
                                $set('show_problem_summary', true);
                            } else {
                                $set('show_problem_summary', false);
                            }
                        }),
                    Textarea::make('new_action_description')
                        ->label('Description')
                        ->required()
                        ->rows(3),
                    Textarea::make('problem_summary')
                        ->label('Problem Summary')
                        ->helperText('Ringkasan teknis masalah (untuk penggunaan internal)')
                        ->rows(3)
                        ->hidden(fn (\Filament\Forms\Get $get) => $get('new_action_status') !== 'Closed')
                        ->afterStateHydrated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            // Copy dari action description jika kosong
                            if (empty($get('problem_summary')) && !empty($get('new_action_description'))) {
                                $set('problem_summary', $get('new_action_description'));
                            }
                        }),
                ])
                ->action(function (array $data) {
                    // Jika ada problem_summary yang diisi, gunakan itu
                    // Jika tidak, gunakan action_description
                    if ($data['new_action_status'] === 'Closed') {
                        if (!empty($data['problem_summary'])) {
                            // Update variabel data dengan problem_summary yang akan dikirim ke addAction
                            $data['problem_summary_value'] = $data['problem_summary'];
                        } else {
                            $data['problem_summary_value'] = $data['new_action_description'];
                        }
                    }
                    $this->addAction($data);
                })
                ->modalSubmitActionLabel('Submit')
                ->extraAttributes(['wire:submit.prevent' => 'addAction']),
            Actions\Action::make('downloadPdf')
            ->label('Download PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function () {
                $actions = $this->record->actions()->orderBy('Action_Time', 'asc')->get();
                $html = view('pdf.ticket-html', ['ticket' => $this->record, 'actions' => $actions])->render();
                $pdf = Pdf::loadHTML($html);
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, 'ticket_' . $this->record->No_Ticket . '.pdf');
            }),


            Actions\Action::make('uploadEvidence')
    ->label('Upload Evidence')
    ->icon('heroicon-o-paper-clip')
    ->color('info')
    ->form([
       FileUpload::make('evidence_files')
    ->label('Evidence Files')
    ->multiple()
    ->disk('public')
    ->directory('ticket-evidences')
    ->acceptedFileTypes([
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Videos
        'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv'
    ])
    ->maxSize(50 * 1024) // 50MB max
    ->maxFiles(10)
    ->required()
    ->helperText('Upload images, videos, or documents. Max 50MB per file, 10 files maximum.')
    ->columnSpanFull(),
            
        Select::make('upload_stage')
            ->label('Upload Stage')
            ->options([
                TicketEvidence::STAGE_INITIAL => 'Initial Report',
                TicketEvidence::STAGE_INVESTIGATION => 'Investigation',
                TicketEvidence::STAGE_RESOLUTION => 'Resolution',
                TicketEvidence::STAGE_CLOSED => 'Closed',
            ])
            ->default(function () {
                return match ($this->record->Status) {
                    'OPEN' => TicketEvidence::STAGE_INVESTIGATION,
                    'PENDING' => TicketEvidence::STAGE_INVESTIGATION,
                    'CLOSED' => TicketEvidence::STAGE_CLOSED,
                    default => TicketEvidence::STAGE_INITIAL,
                };
            })
            ->required(),
            
        Textarea::make('evidence_description')
            ->label('Evidence Description')
            ->placeholder('Describe the evidence being uploaded...')
            ->rows(3)
            ->columnSpanFull(),
    ])
    ->action(function (array $data) {
        $this->uploadEvidence($data);
    })
    ->modalSubmitActionLabel('Upload Evidence')
    ->modalWidth('lg'),

// Actions\Action::make('manageEvidence')
//     ->label('Manage Evidence')
//     ->icon('heroicon-o-folder-open')
//     ->color('warning')
//     ->action(function () {
//         $this->redirect(route('componenets.ticket.evidences', ['record' => $this->record->No_Ticket]));
//     })
//     ->visible(fn () => $this->record->evidences()->count() > 0),

        
        ];
    }

    public function uploadEvidence(array $data): void
{
    try {
        if (!isset($data['evidence_files']) || empty($data['evidence_files'])) {
            throw new \Exception('Please select at least one file to upload.');
        }

        $uploadedCount = 0;
        $files = $data['evidence_files']; // This should be an array of temporary file paths or IDs

        foreach ($files as $file) {
            if (!$file) continue;

            // Since Filament provides temporary file paths, we need to get the original file
            $temporaryFilePath = $file; // This is the temporary path provided by Filament
            $originalFile = new \Illuminate\Http\UploadedFile(
                storage_path('app/public/' . $temporaryFilePath),
                basename($temporaryFilePath),
                mime_content_type(storage_path('app/public/' . $temporaryFilePath)),
                null,
                true // Mark as test to avoid validation issues
            );

            $originalName = $originalFile->getClientOriginalName();
            $mimeType = $originalFile->getMimeType();
            $fileSize = $originalFile->getSize();
            $fileType = TicketEvidence::getFileTypeFromMime($mimeType);

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $originalFile->getClientOriginalExtension();
            $filePath = 'ticket-evidences/' . $filename;

            // Move the temporary file to the public disk
            $originalFile->storeAs('ticket-evidences', $filename, 'public');

            // Save to database
            TicketEvidence::create([
                'No_Ticket' => $this->record->No_Ticket,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'description' => $data['evidence_description'] ?? null,
                'uploaded_by' => Auth::id(),
                'upload_stage' => $data['upload_stage'],
            ]);

            $uploadedCount++;
        }

        // Create action history
        TicketAction::create([
            'No_Ticket' => $this->record->No_Ticket,
            'Action_Taken' => 'Evidence Upload',
            'Action_Time' => now(),
            'Action_By' => Auth::user()->name,
            'Action_Level' => Auth::user()->Level ?? 'Level 1',
            'Action_Description' => "Uploaded {$uploadedCount} evidence file(s) for stage: " . $data['upload_stage'],
        ]);

        Notification::make()
            ->success()
            ->title('Evidence Uploaded')
            ->body("{$uploadedCount} evidence file(s) have been uploaded successfully.")
            ->send();

        // Refresh the page to show new evidence
        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->No_Ticket]), navigate: false);
    } catch (\Exception $e) {
        Notification::make()
            ->danger()
            ->title('Upload Failed')
            ->body($e->getMessage())
            ->send();
    }
}

    public function escalateTicket(array $data): void
    {
        try {
            // Validate data is present (though Filament form should handle this)
            if (empty($data['escalation_level']) || empty($data['escalation_description'])) {
                throw new \Exception('Escalation level and description are required.');
            }

            $levelOrder = [
                'Level 1' => ['order' => 1, 'role' => 'NOC'],
                'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
                'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
                'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
                'Level 5' => ['order' => 5, 'role' => 'Engineer'],
                'Level 6' => ['order' => 6, 'role' => 'Management'],
            ];

            $currentUserLevel = Auth::user()->Level ?? 'Level 1';
            $currentEscalationLevel = $this->record->Current_Escalation_Level ?? $this->record->Open_Level ?? 'Level 1';

            // Validate the selected escalation level
            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentUserLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to your current level.');
            }

            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentEscalationLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to the current escalation level.');
            }

            // Update ticket
            $this->record->update([
                'Current_Escalation_Level' => $data['escalation_level'],
            ]);

            // Create action history with the user's level, not the escalation level
            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => 'Escalation',
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1', // Gunakan level user yang melakukan aksi
                'Action_Description' => $data['escalation_description'] . "\nEscalated to: " . ($levelOrder[$data['escalation_level']]['role'] ?? $data['escalation_level']),
            ]);

            Notification::make()
                ->success()
                ->title('Ticket Escalated')
                ->body('Ticket has been escalated to ' . ($levelOrder[$data['escalation_level']]['role'] ?? $data['escalation_level']))
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->No_Ticket]), navigate: false);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Escalation Failed')
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
                    // Main content (Ticket Information, Problem Details, Progress History) spanning 2 columns
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
                                    TextEntry::make('Current_Escalation_Level')
                                        ->label('Current Escalation')
                                        ->getStateUsing(function ($record) {
                                            $levelOrder = [
                                                'Level 1' => 'NOC',
                                                'Level 2' => 'SPV NOC',
                                                'Level 3' => 'Teknisi',
                                                'Level 4' => 'SPV Teknisi',
                                                'Level 5' => 'Engineer',
                                                'Level 6' => 'Management',
                                            ];
                                            return $record->Current_Escalation_Level 
                                                ? ($levelOrder[$record->Current_Escalation_Level] ?? $record->Current_Escalation_Level)
                                                : '-';
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
                                    TextEntry::make('Problem_Summary')
                                        ->label('Problem Summary')
                                        ->default('-')
                                        ->columnSpanFull(),
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

                    // Sidebar content (Timer Information and Evidence Statistics) in the third column
                    Section::make('Sidebar')
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Timer Information')
                                ->schema([
                                    ViewEntry::make('timer')
                                        ->view('livewire.ticket-timer')
                                        ->viewData(['record' => $this->record]),
                                ])
                                ->collapsible()
                                ->collapsed(false),

                            Section::make('Evidence Statistics')
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(4)
                                        ->schema([
                                            TextEntry::make('evidence_stats.total_count')
                                                ->label('Total Files')
                                                ->getStateUsing(fn () => $this->record->evidences()->count())
                                                ->badge()
                                                ->color('primary'),
                                            TextEntry::make('evidence_stats.images')
                                                ->label('Images')
                                                ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'image')->count())
                                                ->badge()
                                                ->color('info'),
                                            TextEntry::make('evidence_stats.videos')
                                                ->label('Videos')
                                                ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'video')->count())
                                                ->badge()
                                                ->color('warning'),
                                            TextEntry::make('evidence_stats.documents')
                                                ->label('Documents')
                                                ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'document')->count())
                                                ->badge()
                                                ->color('success'),
                                        ]),
                                    TextEntry::make('evidence_stats.total_size')
                                        ->label('Total Size')
                                        ->getStateUsing(function () {
                                            $totalSize = $this->record->evidences()->sum('file_size');
                                            $units = ['B', 'KB', 'MB', 'GB'];
                                            for ($i = 0; $totalSize > 1024 && $i < count($units) - 1; $i++) {
                                                $totalSize /= 1024;
                                            }
                                            return round($totalSize, 2) . ' ' . $units[$i];
                                        })
                                        ->badge()
                                        ->color('gray'),
                                ])
                                ->visible(fn () => $this->record->evidences()->count() > 0)
                                ->collapsible()
                                ->collapsed(false),
                        ]),

                    // Evidence Files section below the main content, spanning full width
                    Section::make('Evidence Files')
                    ->schema([
                        ViewEntry::make('evidence_management')
                            ->view('filament.components.ticket-evidences')
                            ->viewData([
                                'record' => $this->record,
                                'evidences' => $this->record->evidences()->latest()->get() ?? collect(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn () => $this->record->evidences()->count() === 0)
                    ->visible(fn () => $this->record->evidences()->count() > 0)
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('uploadMore')
                            ->label('Upload More')
                            ->icon('heroicon-m-plus')
                            ->color('primary')
                            ->action(function () {
                                $this->mountAction('uploadEvidence');
                            }),
                    ])
                    ->extraAttributes(['class' => 'mt-6']),
                ]),
        ]);
}
}
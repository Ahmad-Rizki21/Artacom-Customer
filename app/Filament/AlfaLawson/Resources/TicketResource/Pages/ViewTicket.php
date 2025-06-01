<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
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
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Colors\Color;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function addAction(array $data): void
    {
        try {
            if (empty($data['new_action_status']) || empty($data['new_action_description'])) {
                throw new \Exception('Action status and description are required.');
            }

            // Jika status tiket sudah CLOSED, batasi hanya untuk "Note"
            if ($this->record->Status === 'CLOSED' && $data['new_action_status'] !== 'Note') {
                throw new \Exception('Tiket yang sudah ditutup hanya dapat ditambahkan catatan (Note).');
            }

            $updateData = [];
            if ($data['new_action_status'] === 'Pending Clock' && $this->record->Status !== 'CLOSED') {
                $updateData = [
                    'Status' => 'PENDING',
                    'Pending_Start' => now(),
                    'Pending_Reason' => $data['new_action_description'],
                ];
            } elseif ($data['new_action_status'] === 'Start Clock' && $this->record->Status !== 'CLOSED') {
                $updateData = [
                    'Status' => 'OPEN',
                    'Pending_Stop' => now(),
                ];
            } elseif ($data['new_action_status'] === 'Closed' && $this->record->Status !== 'CLOSED') {
                // Validasi bahwa problem_summary dan classification ada jika statusnya Closed
                if (!isset($data['problem_summary']) || empty(trim($data['problem_summary']))) {
                    throw new \Exception('Problem Summary harus diisi sebelum menutup tiket.');
                }
                if (!isset($data['classification']) || empty(trim($data['classification']))) {
                    throw new \Exception('Classification harus diisi sebelum menutup tiket.');
                }

                $updateData = [
                    'Status' => 'CLOSED',
                    'Closed_Time' => now(),
                    'Action_Summry' => $data['new_action_description'],
                    'Problem_Summary' => $data['problem_summary'],
                    'Classification' => $data['classification'],
                ];

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
            'Level 1' => ['order' => 1, 'role' => 'NOC', 'icon' => 'heroicon-o-user'],
            'Level 2' => ['order' => 2, 'role' => 'SPV NOC', 'icon' => 'heroicon-o-user-group'],
            'Level 3' => ['order' => 3, 'role' => 'Teknisi', 'icon' => 'heroicon-o-wrench-screwdriver'],
            'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi', 'icon' => 'heroicon-o-cog-6-tooth'],
            'Level 5' => ['order' => 5, 'role' => 'Engineer', 'icon' => 'heroicon-o-computer-desktop'],
            'Level 6' => ['order' => 6, 'role' => 'Management', 'icon' => 'heroicon-o-building-office'],
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
                $escalationOptions[$level] = $info['role'] . ' (' . $level . ')';
            }
        }

        return [
            Actions\EditAction::make()
                ->label('Edit Ticket')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->button()
                ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record])),

            Actions\Action::make('escalate')
                ->label('Escalate Ticket')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('danger')
                ->button()
                ->visible(function () use ($levelOrder) {
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
                ->requiresConfirmation()
                ->modalHeading('🚨 Escalate Ticket')
                ->modalDescription('Are you sure you want to escalate this ticket to a higher level?')
                ->modalSubmitActionLabel('Yes, Escalate')
                ->modalCancelActionLabel('Cancel')
                ->form([
                    Select::make('escalation_level')
                        ->label('Escalation Level')
                        ->options($escalationOptions)
                        ->required()
                        ->native(false)
                        ->placeholder('Select escalation level')
                        ->helperText('Choose the appropriate level based on the complexity of the issue'),
                    Textarea::make('escalation_description')
                        ->label('Escalation Reason')
                        ->required()
                        ->rows(4)
                        ->placeholder('Explain why this ticket needs to be escalated...')
                        ->helperText('Provide detailed information about why escalation is necessary'),
                ])
                ->action(function (array $data) {
                    $this->escalateTicket($data);
                }),

            Actions\Action::make('addAction')
                ->label('Add Action')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->button()
                ->modalHeading('➕ Add New Action')
                ->modalDescription('Record a new action taken on this ticket')
                ->modalWidth('2xl')
                ->form([
                    Select::make('new_action_status')
                        ->label('Action Type')
                        ->options(function () {
                            $options = [
                                'Start Clock' => '▶️ Start Clock',
                                'Pending Clock' => '⏸️ Pending Clock',
                                'Closed' => '✅ Close Ticket',
                                'Note' => '📝 Add Note',
                            ];
                            if ($this->record->Status === 'CLOSED') {
                                return ['Note' => '📝 Add Note'];
                            }
                            return $options;
                        })
                        ->required()
                        ->native(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            if ($state === 'Closed') {
                                $set('show_problem_summary', true);
                                $set('show_classification', true);
                            } else {
                                $set('show_problem_summary', false);
                                $set('show_classification', false);
                            }
                        }),
                    Textarea::make('new_action_description')
                        ->label('Action Description')
                        ->required()
                        ->rows(4)
                        ->placeholder('Describe the action taken or issue observed...')
                        ->helperText('Provide detailed information about the action'),
                    Textarea::make('problem_summary')
                        ->label('Problem Summary')
                        ->helperText('Technical summary of the issue (for internal use)')
                        ->rows(3)
                        ->required(fn (\Filament\Forms\Get $get) => $get('new_action_status') === 'Closed')
                        ->hidden(fn (\Filament\Forms\Get $get) => $get('new_action_status') !== 'Closed')
                        ->placeholder('Summarize the technical details of the problem...')
                        ->afterStateHydrated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            if (empty($get('problem_summary')) && !empty($get('new_action_description'))) {
                                $set('problem_summary', $get('new_action_description'));
                            }
                        }),
                    Select::make('classification')
                        ->label('Problem Classification')
                        ->options([
                            'Hardware' => '🖥️ Hardware',
                            'Fo / Fiber Optic' => '📡 Fiber Optic',
                            'GSM / Sim Card' => '📱 GSM / SIM Card',
                            'Listrik' => '⚡ Power/Electrical',
                            'Customer' => '👤 Customer Issue',
                        ])
                        ->helperText('Classify the type of problem (required for ticket closure)')
                        ->required(fn (\Filament\Forms\Get $get) => $get('new_action_status') === 'Closed')
                        ->hidden(fn (\Filament\Forms\Get $get) => $get('new_action_status') !== 'Closed')
                        ->native(false)
                        ->placeholder('Select problem category'),
                ])
                ->action(function (array $data) {
                    $this->addAction($data);
                })
                ->modalSubmitActionLabel('Submit Action'),

            Actions\Action::make('uploadEvidence')
                ->label('Upload Evidence')
                ->icon('heroicon-o-camera')
                ->color('info')
                ->button()
                ->modalHeading('📎 Upload Evidence Files')
                ->modalDescription('Upload supporting files, images, or documents for this ticket')
                ->modalWidth('2xl')
                ->form([
                    FileUpload::make('evidence_files')
                        ->label('Evidence Files')
                        ->multiple()
                        ->disk('public')
                        ->directory('ticket-evidences')
                        ->acceptedFileTypes([
                            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                            'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm',
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'text/plain', 'text/csv'
                        ])
                        ->maxSize(50 * 1024)
                        ->maxFiles(10)
                        ->required()
                        ->helperText('📄 Supported: Images, Videos, Documents | Max: 50MB per file, 10 files total')
                        ->columnSpanFull()
                        ->imagePreviewHeight('150')
                        ->panelLayout('grid'),
                    Select::make('upload_stage')
                        ->label('Upload Stage')
                        ->options([
                            TicketEvidence::STAGE_INITIAL => '🆕 Initial Report',
                            TicketEvidence::STAGE_INVESTIGATION => '🔍 Investigation',
                            TicketEvidence::STAGE_RESOLUTION => '🔧 Resolution',
                            TicketEvidence::STAGE_CLOSED => '✅ Closed',
                        ])
                        ->default(function () {
                            return match ($this->record->Status) {
                                'OPEN' => TicketEvidence::STAGE_INVESTIGATION,
                                'PENDING' => TicketEvidence::STAGE_INVESTIGATION,
                                'CLOSED' => TicketEvidence::STAGE_CLOSED,
                                default => TicketEvidence::STAGE_INITIAL,
                            };
                        })
                        ->required()
                        ->native(false),
                    Textarea::make('evidence_description')
                        ->label('Evidence Description')
                        ->placeholder('Describe what these files show or demonstrate...')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Provide context for the uploaded evidence'),
                ])
                ->action(function (array $data) {
                    $this->uploadEvidence($data);
                })
                ->modalSubmitActionLabel('Upload Evidence'),

            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->button()
                ->action(function () {
                    $actions = $this->record->actions()->orderBy('Action_Time', 'asc')->get();
                    $html = view('pdf.ticket-html', ['ticket' => $this->record, 'actions' => $actions])->render();
                    $pdf = Pdf::loadHTML($html);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'ticket_' . $this->record->No_Ticket . '.pdf');
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('🗑️ Delete Ticket')
                ->modalDescription('Are you sure you want to permanently delete this ticket? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete Permanently')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->successNotification(
                    Notification::make()
                        ->title('Ticket Deleted')
                        ->body('The ticket has been successfully deleted.')
                        ->success()
                ),
        ];
    }

    // [Other methods remain the same - uploadEvidence, escalateTicket, etc.]
    public function uploadEvidence(array $data): void
    {
        try {
            if (!isset($data['evidence_files']) || empty($data['evidence_files'])) {
                throw new \Exception('Please select at least one file to upload.');
            }

            $uploadedCount = 0;
            $files = $data['evidence_files'];

            foreach ($files as $file) {
                if (!$file) continue;

                $temporaryFilePath = $file;
                $originalFile = new \Illuminate\Http\UploadedFile(
                    storage_path('app/public/' . $temporaryFilePath),
                    basename($temporaryFilePath),
                    mime_content_type(storage_path('app/public/' . $temporaryFilePath)),
                    null,
                    true
                );

                $originalName = $originalFile->getClientOriginalName();
                $mimeType = $originalFile->getMimeType();
                $fileSize = $originalFile->getSize();
                $fileType = TicketEvidence::getFileTypeFromMime($mimeType);

                $filename = time() . '_' . uniqid() . '.' . $originalFile->getClientOriginalExtension();
                $filePath = 'ticket-evidences/' . $filename;

                $originalFile->storeAs('ticket-evidences', $filename, 'public');

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

            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentUserLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to your current level.');
            }

            if (($levelOrder[$data['escalation_level']]['order'] ?? 0) <= ($levelOrder[$currentEscalationLevel]['order'] ?? 1)) {
                throw new \Exception('Cannot escalate to a level lower than or equal to the current escalation level.');
            }

            $this->record->update([
                'Current_Escalation_Level' => $data['escalation_level'],
            ]);

            TicketAction::create([
                'No_Ticket' => $this->record->No_Ticket,
                'Action_Taken' => 'Escalation',
                'Action_Time' => now(),
                'Action_By' => Auth::user()->name,
                'Action_Level' => Auth::user()->Level ?? 'Level 1',
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
                Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // Header Status Card
                        Section::make()
                            ->schema([
                                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                                    ->schema([
                                        TextEntry::make('No_Ticket')
                                            ->label('Ticket Number')
                                            ->badge()
                                            ->color('primary')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold)
                                            ->copyable()
                                            ->copyMessage('Ticket number copied!')
                                            ->icon('heroicon-m-ticket'),
                                        
                                        TextEntry::make('Status')
                                            ->label('Current Status')
                                            ->badge()
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold)
                                            ->color(fn (string $state): string => match ($state) {
                                                'OPEN' => 'warning',
                                                'PENDING' => 'info',
                                                'CLOSED' => 'success',
                                                default => 'gray',
                                            })
                                            ->icon(fn (string $state): string => match ($state) {
                                                'OPEN' => 'heroicon-m-play-circle',
                                                'PENDING' => 'heroicon-m-pause-circle',
                                                'CLOSED' => 'heroicon-m-check-circle',
                                                default => 'heroicon-m-question-mark-circle',
                                            }),
                                        
                                        TextEntry::make('Catagory')
                                            ->label('Category')
                                            ->badge()
                                            ->color('info')
                                            ->icon('heroicon-m-tag'),
                                    ]),
                            ])
                            ->columnSpan(['default' => 12])
                            ->extraAttributes(['class' => 'bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200']),

                        // Main Content Area
                        Grid::make(['default' => 1, 'lg' => 8])
                            ->columnSpan(['default' => 12, 'lg' => 8])
                            ->schema([
                                // Customer Information Card
                                Section::make('📍 Customer & Location Information')
                                    ->description('Customer details and site information')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2, 'lg' => 3])
                                            ->schema([
                                                TextEntry::make('Customer')
                                                    ->icon('heroicon-m-building-storefront')
                                                    ->weight(FontWeight::Bold)
                                                    ->color('primary'),
                                                
                                                TextEntry::make('Site_ID')
                                                    ->label('Site ID')
                                                    ->icon('heroicon-m-map-pin')
                                                    ->default('-')
                                                    ->getStateUsing(fn ($record) => $record->remote?->Site_ID ?? $record->Site_ID ?? '-')
                                                    ->copyable()
                                                    ->copyMessage('Site ID copied!'),
                                                
                                                TextEntry::make('Nama_Toko')
                                                    ->label('Store Address')
                                                    ->icon('heroicon-m-map')
                                                    ->default('-')
                                                    ->getStateUsing(fn ($record) => $record->remote?->Nama_Toko ?? '-')
                                                    ->columnSpan(['default' => 1, 'lg' => 1]),
                                                
                                                TextEntry::make('DC')
                                                    ->label('Data Center')
                                                    ->icon('heroicon-m-server-stack')
                                                    ->default('-')
                                                    ->getStateUsing(fn ($record) => $record->remote?->DC ?? '-'),
                                                
                                                TextEntry::make('IP_Address')
                                                    ->label('IP Address')
                                                    ->icon('heroicon-m-globe-alt')
                                                    ->default('-')
                                                    ->getStateUsing(function ($record) {
                                                        $ip = $record->remote?->IP_Address ?? '-';
                                                        return $ip !== '-' ? $ip : $ip;
                                                    })
                                                    ->url(function ($record) {
                                                        $ip = $record->remote?->IP_Address ?? '';
                                                        return $ip ? "http://{$ip}:8090" : null;
                                                    })
                                                    ->openUrlInNewTab()
                                                    ->copyable()
                                                    ->copyMessage('IP Address copied!'),
                                                
                                                TextEntry::make('Open_Level')
                                                    ->label('Opened At Level')
                                                    ->icon('heroicon-m-user-group')
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
                                                    })
                                                    ->badge()
                                                    ->color('info'),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->icon('heroicon-m-building-storefront'),

                                // Problem Details Card
                                Section::make('🔍 Problem Details')
                                    ->description('Detailed information about the reported issue')
                                    ->schema([
                                        TextEntry::make('Problem')
                                            ->label('Problem Description')
                                            ->columnSpanFull()
                                            ->html()
                                            ->extraAttributes(['class' => 'text-sm leading-relaxed'])
                                            ->icon('heroicon-m-exclamation-triangle'),
                                        
                                        Grid::make(['default' => 1, 'md' => 3])
                                            ->schema([
                                                TextEntry::make('Reported_By')
                                                    ->label('Reported By')
                                                    ->icon('heroicon-m-user')
                                                    ->default('-')
                                                    ->badge()
                                                    ->color('gray'),
                                                
                                                TextEntry::make('Pic')
                                                    ->label('Person In Charge')
                                                    ->icon('heroicon-m-identification')
                                                    ->getStateUsing(fn ($record) => $record->Pic ?? '-')
                                                    ->badge()
                                                    ->color('primary'),
                                                
                                                TextEntry::make('Tlp_Pic')
                                                    ->label('PIC Phone')
                                                    ->icon('heroicon-m-phone')
                                                    ->getStateUsing(fn ($record) => $record->Tlp_Pic ?? '-')
                                                    ->copyable()
                                                    ->copyMessage('Phone number copied!')
                                                    ->url(fn ($record) => $record->Tlp_Pic ? 'tel:' . $record->Tlp_Pic : null),
                                            ]),
                                        
                                            TextEntry::make('Problem_Summary')
                                            ->label('📋 Problem Summary')
                                            ->default('No summary available')
                                            ->columnSpanFull()
                                            ->formatStateUsing(fn (string $state): string => 
                                                $state ?: 'No summary available'
                                            )
                                            ->extraAttributes([
                                                'class' => 'problem-summary-display bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded-lg p-4 shadow-md',
                                                'style' => 'min-height: 3rem; font-size: 0.875rem; line-height: 1.5;',
                                            ])
                                            ->placeholder('Problem summary will appear here when available')
                                            ->visible(fn ($record) => $record->Status === 'CLOSED'),
                                        
                                        TextEntry::make('Classification')
                                            ->label('🏷️ Classification')
                                            ->default('Not classified')
                                            ->getStateUsing(function ($record) {
                                                $classifications = [
                                                    'Hardware' => '🖥️ Hardware',
                                                    'Fo / Fiber Optic' => '📡 Fiber Optic',
                                                    'GSM / Sim Card' => '📱 GSM / SIM Card',
                                                    'Listrik' => '⚡ Power/Electrical',
                                                    'Customer' => '👤 Customer Issue',
                                                ];
                                                return $record->Classification ? 
                                                    $classifications[$record->Classification] ?? $record->Classification : 
                                                    'Not classified';
                                            })
                                            ->badge()
                                            ->color(function ($state) {
                                                return match(true) {
                                                    str_contains($state, 'Hardware') => 'danger',
                                                    str_contains($state, 'Fiber') => 'warning',
                                                    str_contains($state, 'GSM') => 'info',
                                                    str_contains($state, 'Power') => 'success',
                                                    str_contains($state, 'Customer') => 'primary',
                                                    default => 'gray'
                                                };
                                            })
                                            ->visible(fn ($record) => $record->Status === 'CLOSED')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->icon('heroicon-m-document-text'),

                                // Current Escalation Level
                                Section::make('🚀 Current Escalation Level')
                                    ->description('Current handling level and escalation status')
                                    ->schema([
                                        TextEntry::make('Current_Escalation_Level')
                                            ->label('Current Level')
                                            ->getStateUsing(function ($record) {
                                                $levelOrder = [
                                                    'Level 1' => '👤 NOC',
                                                    'Level 2' => '👥 SPV NOC',
                                                    'Level 3' => '🔧 Teknisi',
                                                    'Level 4' => '⚙️ SPV Teknisi',
                                                    'Level 5' => '💻 Engineer',
                                                    'Level 6' => '🏢 Management',
                                                ];
                                                return $record->Current_Escalation_Level 
                                                    ? ($levelOrder[$record->Current_Escalation_Level] ?? $record->Current_Escalation_Level)
                                                    : '👤 NOC (Default)';
                                            })
                                            ->badge()
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->color(function ($record) {
                                                $level = $record->Current_Escalation_Level ?? 'Level 1';
                                                return match($level) {
                                                    'Level 1', 'Level 2' => 'success',
                                                    'Level 3', 'Level 4' => 'warning',
                                                    'Level 5', 'Level 6' => 'danger',
                                                    default => 'gray'
                                                };
                                            }),
                                    ])
                                    ->collapsible()
                                    ->icon('heroicon-m-arrow-trending-up'),

                                // Progress History
                                Section::make('📈 Progress Timeline')
                                    ->description('Complete history of actions taken on this ticket')
                                    ->schema([
                                        ViewEntry::make('progress_timeline')
                                            ->view('filament.resources.ticket-progress-timeline')
                                            ->viewData([
                                                'record' => $this->record,
                                                'actions' => $this->record->actions()->orderBy('Action_Time', 'desc')->get(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->icon('heroicon-m-clock'),
                            ]),

                        // Sidebar
                        Grid::make(['default' => 1, 'lg' => 4])
                            ->columnSpan(['default' => 12, 'lg' => 4])
                            ->schema([
                                // Timer Information
                                Section::make('⏱️ Timer Information')
                                    ->description('Real-time tracking of ticket duration')
                                    ->schema([
                                        ViewEntry::make('timer')
                                            ->view('livewire.ticket-timer')
                                            ->viewData(['record' => $this->record]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->extraAttributes(['class' => 'bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200']),

                                // Quick Stats
                                Section::make('📊 Quick Statistics')
                                    ->description('Key metrics and counts')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('total_actions')
                                                    ->label('Total Actions')
                                                    ->getStateUsing(fn () => $this->record->actions()->count())
                                                    ->badge()
                                                    ->color('primary')
                                                    ->icon('heroicon-m-list-bullet'),
                                                
                                                TextEntry::make('escalations_count')
                                                    ->label('Escalations')
                                                    ->getStateUsing(fn () => $this->record->actions()->where('Action_Taken', 'Escalation')->count())
                                                    ->badge()
                                                    ->color('warning')
                                                    ->icon('heroicon-m-arrow-trending-up'),
                                                
                                                TextEntry::make('evidence_count')
                                                    ->label('Evidence Files')
                                                    ->getStateUsing(fn () => $this->record->evidences()->count())
                                                    ->badge()
                                                    ->color('info')
                                                    ->icon('heroicon-m-paper-clip'),
                                                
                                                TextEntry::make('notes_count')
                                                    ->label('Notes')
                                                    ->getStateUsing(fn () => $this->record->actions()->where('Action_Taken', 'Note')->count())
                                                    ->badge()
                                                    ->color('success')
                                                    ->icon('heroicon-m-pencil-square'),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),

                                // Evidence Statistics
                                Section::make('📎 Evidence Overview')
                                    ->description('File upload statistics and breakdown')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('evidence_stats.images')
                                                    ->label('Images')
                                                    ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'image')->count())
                                                    ->badge()
                                                    ->color('info')
                                                    ->icon('heroicon-m-photo'),
                                                
                                                TextEntry::make('evidence_stats.videos')
                                                    ->label('Videos')
                                                    ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'video')->count())
                                                    ->badge()
                                                    ->color('warning')
                                                    ->icon('heroicon-m-video-camera'),
                                                
                                                TextEntry::make('evidence_stats.documents')
                                                    ->label('Documents')
                                                    ->getStateUsing(fn () => $this->record->evidences()->where('file_type', 'document')->count())
                                                    ->badge()
                                                    ->color('success')
                                                    ->icon('heroicon-m-document'),
                                                
                                                TextEntry::make('evidence_stats.total_size')
                                                    ->label('Total Size')
                                                    ->getStateUsing(function () {
                                                        $totalSize = $this->record->evidences()->sum('file_size');
                                                        if ($totalSize == 0) return '0 B';
                                                        
                                                        $units = ['B', 'KB', 'MB', 'GB'];
                                                        for ($i = 0; $totalSize > 1024 && $i < count($units) - 1; $i++) {
                                                            $totalSize /= 1024;
                                                        }
                                                        return round($totalSize, 2) . ' ' . $units[$i];
                                                    })
                                                    ->badge()
                                                    ->color('gray')
                                                    ->icon('heroicon-m-archive-box'),
                                            ]),
                                    ])
                                    ->visible(fn () => $this->record->evidences()->count() > 0)
                                    ->collapsible()
                                    ->collapsed(false),

                                // Timestamps
                                Section::make('🕐 Important Timestamps')
                                    ->description('Key dates and times')
                                    ->schema([
                                        TextEntry::make('Open_Time')
                                            ->label('Opened At')
                                            ->dateTime('M j, Y g:i A')
                                            ->icon('heroicon-m-play-circle')
                                            ->color('success'),
                                        
                                        TextEntry::make('Pending_Start')
                                            ->label('Last Pending')
                                            ->dateTime('M j, Y g:i A')
                                            ->icon('heroicon-m-pause-circle')
                                            ->color('warning')
                                            ->visible(fn ($record) => $record->Pending_Start),
                                        
                                        TextEntry::make('Closed_Time')
                                            ->label('Closed At')
                                            ->dateTime('M j, Y g:i A')
                                            ->icon('heroicon-m-check-circle')
                                            ->color('danger')
                                            ->visible(fn ($record) => $record->Closed_Time),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true),
                            ]),

                        // Evidence Files Section (Full Width)
                        Section::make('📂 Evidence Files')
                            ->description('All uploaded evidence files and documentation')
                            ->schema([
                                ViewEntry::make('evidence_management')
                                    ->view('filament.components.ticket-evidences')
                                    ->viewData([
                                        'record' => $this->record,
                                        'evidences' => $this->record->evidences()->latest()->get() ?? collect(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['default' => 12])
                            ->collapsible()
                            ->collapsed(fn () => $this->record->evidences()->count() === 0)
                            ->visible(fn () => $this->record->evidences()->count() > 0)
                            ->headerActions([
                                \Filament\Infolists\Components\Actions\Action::make('uploadMore')
                                    ->label('Upload More Files')
                                    ->icon('heroicon-m-plus')
                                    ->color('primary')
                                    ->button()
                                    ->action(function () {
                                        $this->mountAction('uploadEvidence');
                                    }),
                            ])
                            ->extraAttributes(['class' => 'mt-6 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200'])
                            ->icon('heroicon-m-folder-open'),
                    ]),
            ]);
    }
}
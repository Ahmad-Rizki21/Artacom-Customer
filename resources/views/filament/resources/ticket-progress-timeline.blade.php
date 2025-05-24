<div class="relative timeline-container">
    @if ($actions->count() > 0)
        @foreach ($actions as $action)
            <div class="flex items-start mb-6 relative group">
                <div class="flex-shrink-0 w-8">
                    <div @class([
                        'flex items-center justify-center w-8 h-8 rounded-full shadow-sm border-2 transition-colors duration-200',
                        'bg-success border-success' => $action->Action_Taken === 'Completed' || $action->Action_Taken === 'Closed',
                        'bg-info border-info' => $action->Action_Taken === 'Pending Clock',
                        'bg-primary border-primary' => $action->Action_Taken === 'Start Clock',
                        'bg-secondary border-secondary' => $action->Action_Taken === 'Note',
                        'bg-warning border-warning' => $action->Action_Taken === 'Escalation',
                        'bg-gray-100 border-gray-300' => !in_array($action->Action_Taken, ['Completed', 'Closed', 'Pending Clock', 'Start Clock', 'Note', 'Escalation'])
                    ])>
                        @switch($action->Action_Taken)
                            @case('Completed')
                            @case('Closed')
                                <svg class="w-4 h-4 text-success-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                @break
                            @case('Pending Clock')
                                <svg class="w-4 h-4 text-info-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @break
                            @case('Start Clock')
                                <svg class="w-4 h-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @break
                            @case('Note')
                                <svg class="w-4 h-4 text-secondary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                @break
                            @case('Escalation')
                                <svg class="w-4 h-4 text-warning-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                </svg>
                                @break
                            @default
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                        @endswitch
                    </div>
                    @if (!$loop->last)
                        <div class="w-0.5 h-full bg-gray-200 dark:bg-gray-600 absolute left-4 top-8 -bottom-6"></div>
                    @endif
                </div>

                <div class="ml-4 flex-grow">
                    <div class="mb-2 flex justify-between items-center">
                        @switch($action->Action_Taken)
                            @case('Completed')
                            @case('Closed')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-success text-success-foreground">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Closed {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Pending Clock')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-info text-info-foreground">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Pending {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Start Clock')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-primary text-primary-foreground">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                                    </svg>
                                    Open {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Note')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-secondary text-secondary-foreground">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                    </svg>
                                    Note {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Escalation')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-warning text-warning-foreground">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Escalation {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $action->Action_Taken }} {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                        @endswitch

                        @if(auth()->check() && (auth()->user()->name === $action->Action_By || auth()->user()->Level === 'Admin'))
                            <button 
                                wire:click="$dispatch('openEditModal', { actionId: '{{ $action->id }}', ticketId: '{{ $record->No_Ticket }}' })"
                                class="opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-gray-500 hover:text-primary-500"
                                title="Edit Action"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    <div @class([
                        'border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200',
                        'bg-success-50 border-success-200 dark:bg-success-900/20 dark:border-success-700' => $action->Action_Taken === 'Completed' || $action->Action_Taken === 'Closed',
                        'bg-info-50 border-info-200 dark:bg-info-900/20 dark:border-info-700' => $action->Action_Taken === 'Pending Clock',
                        'bg-primary-50 border-primary-200 dark:bg-primary-900/20 dark:border-primary-700' => $action->Action_Taken === 'Start Clock',
                        'bg-secondary-50 border-secondary-200 dark:bg-secondary-900/20 dark:border-secondary-700' => $action->Action_Taken === 'Note',
                        'bg-warning-50 border-warning-200 dark:bg-warning-900/20 dark:border-warning-700' => $action->Action_Taken === 'Escalation',
                        'bg-card border-card' => !in_array($action->Action_Taken, ['Completed', 'Closed', 'Pending Clock', 'Start Clock', 'Note', 'Escalation'])
                    ])>
                        @php
                            $isCommandOutput = preg_match('/(\r\n|\n|\r|\t)/', $action->Action_Description) || 
                                preg_match('/(C:\\\\|ping |\/home\/|reply from|ms TTL=|Pinging|PING|packets transmitted|received)/i', $action->Action_Description) ||
                                preg_match('/(tracert|traceroute|nslookup|dig @|whois|netstat|\$ |# |> |C:\\>)/i', $action->Action_Description);

                            // Extract escalation target level from Action_Description
                            $escalationTarget = null;
                            if ($action->Action_Taken === 'Escalation' && preg_match('/Escalated to: (.*)/', $action->Action_Description, $matches)) {
                                $escalationTarget = $matches[1];
                                // Remove the "Escalated to: ..." part from the description for display
                                $action->Action_Description = trim(str_replace($matches[0], '', $action->Action_Description));
                            }
                        @endphp

                        @if($isCommandOutput)
                            <div class="p-1">
                                <div class="filament-timeline-command-output rounded-md p-2 overflow-x-auto font-mono dark:bg-gray-800">
                                    <pre class="filament-timeline-command-text text-sm whitespace-pre-wrap break-all overflow-x-auto dark:text-gray-200">{{ $action->Action_Description }}</pre>
                                </div>
                            </div>
                        @else
                            <div class="p-4">
                                <div class="prose prose-sm max-w-none text-sm whitespace-pre-line text-gray-900 dark:text-gray-200">
                                    {{ $action->Action_Description }}
                                </div>
                            </div>
                        @endif

                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>{{ $action->Action_By }}</span>
                                @if($action->Action_Level)
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded-full text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $levelMapping[$action->Action_Level] ?? $action->Action_Level }}
                                    </span>
                                @endif
                                @if($action->Action_Taken === 'Escalation' && $escalationTarget)
                                    <span class="text-xs bg-yellow-100 px-2 py-1 rounded-full text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200">
                                        To: {{ $escalationTarget }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                                </svg>
                                <span>{{ $action->Action_Time->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="flex items-center justify-center p-6 text-gray-500 bg-white rounded-lg border-2 border-dashed dark:bg-gray-800 dark:text-gray-400">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-join="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No progress recorded</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new action.</p>
            </div>
        </div>
    @endif
</div>
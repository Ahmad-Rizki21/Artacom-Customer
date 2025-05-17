<div class="relative timeline-container">
    @if ($actions->count() > 0)
        @foreach ($actions as $action)
            <div class="flex items-start mb-6 relative" wire:key="action-{{ $action->id }}">
                {{-- Timeline Dot and Line --}}
                <div class="flex-shrink-0 w-8">
                    <div @class([
                        'flex items-center justify-center w-8 h-8 rounded-full shadow-sm border-2 transition-colors duration-200',
                        'bg-green-100 border-green-500 dark:bg-green-900/30 dark:border-green-600' => $action->Action_Taken === 'Completed' || $action->Action_Taken === 'Closed',
                        'bg-yellow-100 border-yellow-500 dark:bg-yellow-900/30 dark:border-yellow-600' => $action->Action_Taken === 'Pending Clock',
                        'bg-blue-100 border-blue-500 dark:bg-blue-900/30 dark:border-blue-600' => $action->Action_Taken === 'Start Clock',
                        'bg-purple-100 border-purple-500 dark:bg-purple-900/30 dark:border-purple-600' => $action->Action_Taken === 'Note',
                        'bg-gray-100 border-gray-300 dark:bg-gray-800 dark:border-gray-600' => !in_array($action->Action_Taken, ['Completed', 'Closed', 'Pending Clock', 'Start Clock', 'Note'])
                    ])>
                        @switch($action->Action_Taken)
                            @case('Completed')
                            @case('Closed')
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                @break
                            @case('Pending Clock')
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @break
                            @case('Start Clock')
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @break
                            @case('Note')
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                @break
                            @default
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                        @endswitch
                    </div>
                    @if (!$loop->last)
                        <div class="w-0.5 h-full bg-gray-200 dark:bg-gray-600 absolute left-4 top-8 -bottom-6"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="ml-4 flex-grow">
                    {{-- Status Badge --}}
                    <div class="mb-2">
                        @switch($action->Action_Taken)
                            @case('Completed')
                            @case('Closed')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Closed {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Pending Clock')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Pending {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Start Clock')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                                    </svg>
                                    Active {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @case('Note')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                    </svg>
                                    Note {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $action->Action_Taken }} {{ $action->Action_Time->format('d M Y H:i') }}
                                </span>
                        @endswitch
                    </div>

                    {{-- Content Card --}}
                    <div @class([
                        'border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200',
                        'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-700' => $action->Action_Taken === 'Completed' || $action->Action_Taken === 'Closed',
                        'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/30 dark:border-yellow-700' => $action->Action_Taken === 'Pending Clock',
                        'bg-blue-50 border-blue-200 dark:bg-blue-900/30 dark:border-blue-700' => $action->Action_Taken === 'Start Clock',
                        'bg-purple-50 border-purple-200 dark:bg-purple-900/30 dark:border-purple-700' => $action->Action_Taken === 'Note',
                        'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700' => !in_array($action->Action_Taken, ['Completed', 'Closed', 'Pending Clock', 'Start Clock', 'Note'])
                    ])>
                        @php
                            $isCommandOutput = 
                                preg_match('/(\\r\\n|\\n|\\r|\\t)/', $action->Action_Description) || 
                                preg_match('/(C:\\\\\\\\|ping |\\/home\\/|reply from|ms TTL=|Pinging|PING|packets transmitted|received)/i', $action->Action_Description) ||
                                preg_match('/(tracert|traceroute|nslookup|dig @|whois|netstat|\\$ |# |> |C:\\\\>)/i', $action->Action_Description);
                        @endphp

                        @if($isCommandOutput)
                            <div class="p-1">
                                <div class="bg-gray-900 text-gray-100 rounded-md p-2 overflow-x-auto font-mono">
                                    <pre class="text-sm whitespace-pre-wrap break-all overflow-x-auto text-gray-100">{{ $action->Action_Description }}</pre>
                                </div>
                            </div>
                        @else
                            <div class="p-4">
                                <div class="prose prose-sm max-w-none text-sm whitespace-pre-line text-gray-900 dark:text-gray-100">
                                    {{ $action->Action_Description }}
                                </div>
                            </div>
                        @endif

                        {{-- Footer --}}
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>{{ $action->Action_By }}</span>
                                @if ($action->Action_Level)
                                    <span class="ml-2 text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full text-gray-800 dark:text-gray-200">
                                        {{ $action->Action_Level }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                <span>{{ $action->Action_Time->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="flex items-center justify-center p-6 text-gray-500 bg-white rounded-lg border-2 border-dashed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Belum ada progress</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan action baru.</p>
            </div>
        </div>
    @endif

    {{-- Floating Add Action Button --}}
    <div class="fixed bottom-4 right-4 z-10">
        <button wire:click="$emit('openAddActionModal')" class="flex items-center justify-center w-14 h-14 rounded-full bg-primary-600 text-white shadow-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </button>
    </div>

    {{-- Add Action Modal --}}
    <div
        x-data="{ open: false }"
        x-show="open"
        @openAddActionModal.window="open = true"
        @closeAddActionModal.window="open = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity"
                aria-hidden="true"
            >
                <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
            </div>

            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
            >
                <div class="hidden sm:block absolute top-0 right-0 pt-4 pr-4">
                    <button
                        @click="open = false"
                        type="button"
                        class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            Tambah Action Baru
                        </h3>

                        <div class="mt-4">
                            <form wire:submit.prevent="addAction">
                                {{-- Form fields akan ditambahkan di sini --}}
                                <div class="space-y-4">
                                    <div>
                                        <label for="action_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Status Action
                                        </label>
                                        <select
                                            wire:model="newAction.status"
                                            id="action_status"
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                                        >
                                            <option value="">Pilih Status</option>
                                            <option value="Start Clock">Start Clock</option>
                                            <option value="Pending Clock">Pending Clock</option>
                                            <option value="Closed">Closed</option>
                                            <option value="Note">Note</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="action_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Deskripsi
                                        </label>
                                        <textarea
                                            wire:model="newAction.description"
                                            id="action_description"
                                            rows="4"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Simpan
                                    </button>
                                    <button
                                        @click="open = false"
                                        type="button"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
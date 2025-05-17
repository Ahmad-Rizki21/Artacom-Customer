@props(['actions'])

<div class="space-y-4">
    @foreach($actions as $action)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex gap-4">
                {{-- Status Icon --}}
                <div @class([
                    "flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center",
                    "bg-blue-50 text-blue-600" => $action->Action_Taken === 'Note',
                    "bg-yellow-50 text-yellow-600" => $action->Action_Taken === 'Pending Clock',
                    "bg-green-50 text-green-600" => $action->Action_Taken === 'Start Clock',
                    "bg-gray-50 text-gray-600" => !in_array($action->Action_Taken, ['Note', 'Pending Clock', 'Start Clock']),
                ])>
                    @switch($action->Action_Taken)
                        @case('Note')
                            <x-heroicon-o-document-text class="w-5 h-5"/>
                            @break
                        @case('Pending Clock')
                            <x-heroicon-o-clock class="w-5 h-5"/>
                            @break
                        @case('Start Clock')
                            <x-heroicon-o-play class="w-5 h-5"/>
                            @break
                        @default
                            <x-heroicon-o-information-circle class="w-5 h-5"/>
                    @endswitch
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-3">
                            <span @class([
                                "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
                                "bg-blue-50 text-blue-700" => $action->Action_Taken === 'Note',
                                "bg-yellow-50 text-yellow-700" => $action->Action_Taken === 'Pending Clock',
                                "bg-green-50 text-green-700" => $action->Action_Taken === 'Start Clock',
                                "bg-gray-50 text-gray-700" => !in_array($action->Action_Taken, ['Note', 'Pending Clock', 'Start Clock']),
                            ])>
                                {{ $action->Action_Taken }}
                            </span>
                            <span class="text-sm text-gray-600">
                                by {{ $action->Action_By }}
                            </span>
                        </div>
                        <time class="text-sm text-gray-500">
                            {{ $action->Action_Time->format('d M Y H:i') }}
                        </time>
                    </div>

                    <div class="mt-2 text-sm text-gray-700">
                        {{ $action->Action_Level }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
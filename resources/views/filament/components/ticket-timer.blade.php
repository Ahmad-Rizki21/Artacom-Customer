<div class="space-y-4">
    <div class="grid grid-cols-3 gap-4">
        {{-- Open Clock --}}
        <div class="flex items-center justify-between p-2 bg-white rounded-lg shadow">
            <div class="text-gray-600">Open Clock</div>
            <div class="text-xl font-mono tabular-nums text-gray-600">
                <span wire:poll.1s>{{ $getRecord()->open_duration }}</span>
            </div>
        </div>

        {{-- Pending Clock --}}
        @if($getRecord()->Status === 'PENDING' || ($getRecord()->Pending_Start && !$getRecord()->Pending_Stop))
            <div class="flex items-center justify-between p-2 bg-blue-50 rounded-lg shadow">
                <div class="text-blue-600">Pending Clock</div>
                <div class="text-xl font-mono tabular-nums text-blue-600">
                    <span wire:poll.1s>{{ $getRecord()->pending_duration }}</span>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg shadow">
                <div class="text-gray-400">Pending Clock</div>
                <div class="text-xl font-mono tabular-nums text-gray-400">
                    <span>00:00:00</span>
                </div>
            </div>
        @endif

        {{-- Total Duration --}}
        <div class="flex items-center justify-between p-2 bg-white rounded-lg shadow">
            <div class="text-gray-600">Total Duration</div>
            <div class="text-xl font-mono tabular-nums text-gray-600">
                <span wire:poll.1s>{{ $getRecord()->total_duration }}</span>
            </div>
        </div>
    </div>
</div>
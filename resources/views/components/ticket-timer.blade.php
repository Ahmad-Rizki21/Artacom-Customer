<div class="space-y-4" 
    x-data="{ 
        startTime: {{ $record->Open_Time?->timestamp ?? 'null' }},
        pendingStart: {{ $record->Pending_Start?->timestamp ?? 'null' }},
        pendingStop: {{ $record->Pending_Stop?->timestamp ?? 'null' }},
        closedTime: {{ $record->Closed_Time?->timestamp ?? 'null' }},
        status: '{{ $record->Status }}',
        statusColor: '{{ $statusColor ?? 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}',
        
        init() {
            this.updateTimer();
            if (this.status !== 'CLOSED') {
                setInterval(() => this.updateTimer(), 1000);
            }
        },

        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            let openSeconds = 0;
            let pendingSeconds = 0;
            let totalSeconds = 0;

            if (this.startTime) {
                if (this.status === 'CLOSED') {
                    openSeconds = this.closedTime - this.startTime;
                    if (this.pendingStart && this.pendingStop) {
                        openSeconds -= (this.pendingStop - this.pendingStart);
                    }
                } else if (this.status === 'PENDING') {
                    openSeconds = this.pendingStart - this.startTime;
                } else {
                    openSeconds = now - this.startTime;
                    if (this.pendingStart && this.pendingStop) {
                        openSeconds -= (this.pendingStop - this.pendingStart);
                    }
                }

                if (this.pendingStart) {
                    if (this.status === 'PENDING') {
                        pendingSeconds = now - this.pendingStart;
                    } else if (this.pendingStop) {
                        pendingSeconds = this.pendingStop - this.pendingStart;
                    }
                }

                totalSeconds = this.status === 'CLOSED' 
                    ? (this.closedTime - this.startTime)
                    : (openSeconds + pendingSeconds);
            }

            this.$refs.openClock.textContent = this.formatTime(Math.max(0, openSeconds));
            this.$refs.pendingClock.textContent = this.formatTime(Math.max(0, pendingSeconds));
            this.$refs.totalClock.textContent = this.formatTime(Math.max(0, totalSeconds));
        }
    }"
    x-init="init()"
    class="dark:text-gray-200"
>
    <div class="p-3 rounded-lg" :class="statusColor">
        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Open Clock</div>
        <div class="text-xl font-mono text-center" x-ref="openClock">
            {{ $record->getOpenDurationAttribute() }}
        </div>
    </div>

    <div class="p-3 rounded-lg" :class="statusColor">
        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Pending Clock</div>
        <div class="text-xl font-mono text-center" x-ref="pendingClock">
            {{ $record->getPendingDurationAttribute() }}
        </div>
    </div>

    <div class="p-3 rounded-lg" :class="statusColor">
        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Duration</div>
        <div class="text-xl font-mono text-center" x-ref="totalClock">
            {{ $record->getTotalDurationAttribute() }}
        </div>
    </div>

    <div class="mt-4 space-y-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Opened At</span>
            <span>{{ $record->Open_Time?->format('M j, Y H:i:s') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Opened By</span>
            <span>{{ $record->openedBy?->name ?? 'Unknown' }}</span>
        </div>
        @if($record->Status === 'CLOSED')
        <div class="flex justify-between text-success-600 dark:text-success-400 font-medium">
            <span>Closed At</span>
            <span>{{ $record->Closed_Time?->format('M j, Y H:i:s') }}</span>
        </div>
        @endif
    </div>
</div>
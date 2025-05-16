<div class="space-y-4" 
    x-data="{ 
        startTime: {{ $record->Open_Time?->timestamp ?? 'null' }},
        pendingStart: {{ $record->Pending_Start?->timestamp ?? 'null' }},
        pendingStop: {{ $record->Pending_Stop?->timestamp ?? 'null' }},
        status: '{{ $record->Status }}',
        
        init() {
            this.updateTimer();
            setInterval(() => this.updateTimer(), 1000);
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
                if (this.status === 'PENDING') {
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

                totalSeconds = openSeconds;
            }

            this.$refs.openClock.textContent = this.formatTime(Math.max(0, openSeconds));
            this.$refs.pendingClock.textContent = this.formatTime(Math.max(0, pendingSeconds));
            this.$refs.totalClock.textContent = this.formatTime(Math.max(0, totalSeconds));
        }
    }"
    x-init="init()"
>
    <div class="p-3 bg-blue-50 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-1">Open Clock</div>
        <div class="text-xl font-mono text-center" x-ref="openClock">
            {{ $record->getOpenDurationAttribute() }}
        </div>
    </div>

    <div class="p-3 bg-yellow-50 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-1">Pending Clock</div>
        <div class="text-xl font-mono text-center" x-ref="pendingClock">
            {{ $record->getPendingDurationAttribute() }}
        </div>
    </div>

    <div class="p-3 bg-green-50 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-1">Total Duration</div>
        <div class="text-xl font-mono text-center" x-ref="totalClock">
            {{ $record->getTotalDurationAttribute() }}
        </div>
    </div>

    <div class="mt-4 space-y-2 p-3 bg-white rounded-lg border">
        <div class="flex justify-between">
            <span class="text-gray-600">Opened At</span>
            <span>{{ $record->Open_Time?->format('M j, Y H:i:s') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Opened By</span>
            <span>{{ $record->openedBy?->name ?? 'Unknown' }}</span>
        </div>
    </div>
</div>
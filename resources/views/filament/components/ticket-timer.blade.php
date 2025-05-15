<div class="space-y-4" x-data="{
    openTime: @js($openTime),
    pendingStart: @js($pendingStart),
    pendingStop: @js($pendingStop),
    closedTime: @js($closedTime),
    status: @js($status),
    openDuration: '{{ $openDuration }}',
    pendingDuration: '{{ $pendingDuration }}',
    totalDuration: '{{ $totalDuration }}',
    updateDurations() {
        if (!this.openTime || !this.status) return;

        const openDate = new Date(this.openTime);
        const now = new Date();

        // Open Duration (stops at Pending_Start or Closed_Time)
        let openEndTime = now;
        if (this.pendingStart && this.status !== 'OPEN') {
            openEndTime = new Date(this.pendingStart);
        } else if (this.closedTime && this.status === 'CLOSED') {
            openEndTime = new Date(this.closedTime);
        }
        const openDiff = Math.max(0, Math.floor((openEndTime - openDate) / 1000));
        const openHours = Math.floor(openDiff / 3600);
        const openMinutes = Math.floor((openDiff % 3600) / 60);
        const openSeconds = openDiff % 60;
        this.openDuration = `${String(openHours).padStart(2, '0')}:${String(openMinutes).padStart(2, '0')}:${String(openSeconds).padStart(2, '0')}`;

        // Pending Duration (show even if CLOSED, if Pending_Stop exists)
        this.pendingDuration = '00:00:00';
        if (this.pendingStart) {
            let pendingEndTime = null;
            if (this.pendingStop) {
                pendingEndTime = new Date(this.pendingStop);
            } else if (this.status === 'PENDING') {
                pendingEndTime = now;
            }
            if (pendingEndTime) {
                const pendingStartDate = new Date(this.pendingStart);
                const pendingDiff = Math.max(0, Math.floor((pendingEndTime - pendingStartDate) / 1000));
                const pendingHours = Math.floor(pendingDiff / 3600);
                const pendingMinutes = Math.floor((pendingDiff % 3600) / 60);
                const pendingSeconds = pendingDiff % 60;
                this.pendingDuration = `${String(pendingHours).padStart(2, '0')}:${String(pendingMinutes).padStart(2, '0')}:${String(pendingSeconds).padStart(2, '0')}`;
            }
        }

        // Total Duration
        let totalEndTime = this.closedTime ? new Date(this.closedTime) : now;
        let totalDiff = Math.max(0, Math.floor((totalEndTime - openDate) / 1000));
        if (this.pendingStart && (this.pendingStop || this.status === 'PENDING')) {
            const pendingStartDate = new Date(this.pendingStart);
            const pendingEndDate = this.pendingStop ? new Date(this.pendingStop) : now;
            const pendingDiff = Math.max(0, Math.floor((pendingEndDate - pendingStartDate) / 1000));
            totalDiff -= pendingDiff;
        }
        const totalHours = Math.floor(totalDiff / 3600);
        const totalMinutes = Math.floor((totalDiff % 3600) / 60);
        const totalSeconds = totalDiff % 60;
        this.totalDuration = `${String(totalHours).padStart(2, '0')}:${String(totalMinutes).padStart(2, '0')}:${String(totalSeconds).padStart(2, '0')}`;
    },
    startTimer() {
        if (this.status) {
            this.updateDurations();
            setInterval(() => this.updateDurations(), 1000);
        }
    }
}" x-init="startTimer()">
    <div class="grid grid-cols-3 gap-4">
        {{-- Open Clock --}}
        <div class="flex items-center justify-between p-2 bg-white rounded-lg shadow">
            <div class="text-gray-600">Open Clock</div>
            <div class="text-xl font-mono tabular-nums text-gray-600">
                <span x-text="openDuration">{{ $openDuration ?? '00:00:00' }}</span>
            </div>
        </div>

        {{-- Pending Clock --}}
        @if($record && ($record->Pending_Start || $record->Status === 'PENDING'))
            <div class="flex items-center justify-between p-2 bg-blue-50 rounded-lg shadow">
                <div class="text-blue-600">Pending Clock</div>
                <div class="text-xl font-mono tabular-nums text-blue-600">
                    <span x-text="pendingDuration">{{ $pendingDuration ?? '00:00:00' }}</span>
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
                <span x-text="totalDuration">{{ $totalDuration ?? '00:00:00' }}</span>
            </div>
        </div>
    </div>
</div>
<div
    x-data="{
        startTime: {{ $record->Open_Time?->timestamp ?? 'null' }},
        pendingStart: {{ $record->Pending_Start?->timestamp ?? 'null' }},
        pendingStop: {{ $record->Pending_Stop?->timestamp ?? 'null' }},
        closedTime: {{ $record->Closed_Time?->timestamp ?? 'null' }},
        status: '{{ $record->Status }}',
        darkMode: localStorage.getItem('darkMode') === 'true' || false,
        openSeconds: {{ $record->getOpenDurationAttribute() ?? 0 }},
        pendingSeconds: {{ $record->getPendingDurationAttribute() ?? 0 }},
        totalSeconds: {{ $record->getTotalDurationAttribute() ?? 0 }},
        timerInterval: null,

        init() {
            this.updateTimer();
            if (this.status !== 'CLOSED') {
                this.startTimer();
            }
            $wire.on('statusUpdated', (newStatus) => {
                this.status = newStatus;
                this.updateTimestamps();
                if (newStatus === 'PENDING' && !this.timerInterval) {
                    this.pendingStart = this.pendingStart || Math.floor(Date.now() / 1000);
                    this.startTimer();
                } else if (newStatus === 'OPEN') {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.pendingStop = this.pendingStop || Math.floor(Date.now() / 1000);
                    this.startTimer();
                } else if (newStatus === 'CLOSED') {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.closedTime = Math.floor(Date.now() / 1000);
                    this.updateTimer();
                }
                this.updateTimer();
            });
            $wire.on('refresh', () => {
                this.updateTimestamps();
                this.updateTimer();
            });
        },

        startTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                this.updateTimer();
            }, 1000);
        },

        updateTimestamps() {
            $wire.get('record', (response) => {
                this.startTime = response.Open_Time?.timestamp ?? null;
                this.pendingStart = response.Pending_Start?.timestamp ?? null;
                this.pendingStop = response.Pending_Stop?.timestamp ?? null;
                this.closedTime = response.Closed_Time?.timestamp ?? null;
                this.status = response.Status;
                this.openSeconds = response.getOpenDurationAttribute ?? 0;
                this.pendingSeconds = response.getPendingDurationAttribute ?? 0;
                this.totalSeconds = response.getTotalDurationAttribute ?? 0;
            });
        },

        updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            let openSeconds = this.openSeconds;
            let pendingSeconds = this.pendingSeconds;

            if (this.startTime) {
                if (this.status === 'CLOSED') {
                    openSeconds = this.closedTime - this.startTime;
                    if (this.pendingStart && this.pendingStop) {
                        openSeconds -= (this.pendingStop - this.pendingStart);
                    }
                } else if (this.status === 'PENDING' && this.pendingStart) {
                    openSeconds = this.pendingStart - this.startTime;
                    if (!this.pendingStop) {
                        pendingSeconds = now - this.pendingStart; // Increment pending time in real-time
                    } else {
                        pendingSeconds = this.pendingStop - this.pendingStart;
                    }
                } else if (this.status === 'OPEN') {
                    openSeconds = now - this.startTime;
                    if (this.pendingStart && this.pendingStop) {
                        openSeconds -= (this.pendingStop - this.pendingStart);
                    }
                }
            }

            this.openSeconds = Math.max(0, openSeconds);
            this.pendingSeconds = Math.max(0, pendingSeconds);
            this.totalSeconds = this.openSeconds + this.pendingSeconds;

            this.$refs.openClock.textContent = this.formatTime(this.openSeconds);
            this.$refs.pendingClock.textContent = this.formatTime(this.pendingSeconds);
            this.$refs.totalClock.textContent = this.formatTime(this.totalSeconds);
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
        },

        formatTime(seconds) {
            const absSeconds = Math.abs(seconds);
            const hours = Math.floor(absSeconds / 3600);
            const minutes = Math.floor((absSeconds % 3600) / 60);
            const secs = absSeconds % 60;
            return [
                hours.toString().padStart(2, '0'),
                minutes.toString().padStart(2, '0'),
                secs.toString().padStart(2, '0')
            ].join(':');
        }
    }"
    :class="darkMode ? 'ticket-timer-dark' : 'ticket-timer-light'"
    class="ticket-timer-wrapper rounded-lg overflow-hidden shadow-lg transition-colors duration-300 p-4"
>
    <!-- Header with clock icon and mode toggle -->
    <div class="ticket-timer-header flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <svg class="ticket-timer-clock-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <h3 class="ticket-timer-title font-semibold">Ticket Timer</h3>
        </div>
        <button @click="toggleDarkMode()" class="ticket-timer-mode-toggle p-2 rounded-full focus:outline-none"
            :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'">
            <svg x-show="darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />
            </svg>
            <svg x-show="!darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
            </svg>
        </button>
    </div>

    <!-- Time display grid -->
    <div class="ticket-timer-grid grid grid-cols-3 gap-4 mb-4">
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-blue-400' : 'text-blue-700'">Open</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="openClock"
                :class="darkMode ? 'text-blue-300' : 'text-blue-600'">{{ $record->getOpenDurationAttribute() }}</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-yellow-400' : 'text-yellow-600'">Pending</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="pendingClock"
                :class="darkMode ? 'text-yellow-300' : 'text-yellow-600'">{{ $record->getPendingDurationAttribute() }}</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-purple-400' : 'text-purple-700'">Total</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="totalClock"
                :class="darkMode ? 'text-purple-300' : 'text-purple-700'">{{ $record->getTotalDurationAttribute() }}</div>
        </div>
    </div>

    <!-- Status and timestamps -->
    <div class="ticket-timer-status text-sm"
        :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <div class="mb-2">Opened At: {{ $record->Open_Time?->format('M d, Y H:i') ?? 'N/A' }}</div>
        <div class="mb-2">Opened By: {{ $record->openedBy?->name ?? 'Unknown' }}</div>
        @if($record->Status === 'CLOSED')
            <div class="text-green-500 dark:text-green-400">Closed At: {{ $record->Closed_Time?->format('M d, Y H:i') }}</div>
        @endif
    </div>
</div>

<style>
.ticket-timer-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    max-width: 100%;
}

.ticket-timer-dark {
    background-color: #1e293b;
    color: #e2e8f0;
    border: 1px solid #334155;
}

.ticket-timer-light {
    background-color: #f9fafb;
    color: #1e293b;
    border: 1px solid #e5e7eb;
}

.ticket-timer-header {
    padding: 8px 12px;
    border-bottom: 1px solid;
    border-color: v-bind(darkMode ? '#334155' : '#e5e7eb');
}

.ticket-timer-clock-icon {
    width: 18px;
    height: 18px;
}

.ticket-timer-title {
    font-size: 16px;
}

.ticket-timer-mode-toggle {
    transition: background-color 0.3s;
}

.ticket-timer-grid {
    border-bottom: 1px solid;
    border-color: v-bind(darkMode ? '#334155' : '#e5e7eb');
}

.ticket-timer-cell {
    border-right: 1px solid;
    border-color: v-bind(darkMode ? '#334155' : '#e5e7eb');
}

.ticket-timer-cell:last-child {
    border-right: none;
}

.ticket-timer-value {
    font-family: 'JetBrains Mono', monospace;
}
</style>
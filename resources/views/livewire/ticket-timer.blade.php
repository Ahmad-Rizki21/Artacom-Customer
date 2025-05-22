<div
    x-data="{
        startTime: {{ $record->Open_Time?->timestamp ?? 'null' }},
        pendingStart: {{ $record->Pending_Start?->timestamp ?? 'null' }},
        pendingStop: {{ $record->Pending_Stop?->timestamp ?? 'null' }},
        closedTime: {{ $record->Closed_Time?->timestamp ?? 'null' }},
        status: '{{ $record->Status }}',
        darkMode: localStorage.getItem('darkMode') === 'true' || false,
        openSeconds: {{ $openTimeSeconds ?? 0 }},
        pendingSeconds: {{ $pendingTimeSeconds ?? 0 }},
        totalSeconds: {{ $totalTimeSeconds ?? 0 }},
        timerInterval: null,
        isUpdating: false,
        lastUpdateTimestamp: 0,

        init() {
            console.log('Initial state:', {
                status: this.status,
                startTime: this.startTime,
                pendingStart: this.pendingStart,
                pendingStop: this.pendingStop,
                closedTime: this.closedTime,
                openSeconds: this.openSeconds,
                pendingSeconds: this.pendingSeconds,
                totalSeconds: this.totalSeconds
            });

            this.updateTimer();
            if (this.status !== 'CLOSED') {
                console.log('Starting timer since status is not CLOSED');
                this.startTimer();
            } else {
                console.log('Timer not started because status is CLOSED');
            }
            
            $wire.on('timerStateUpdated', (data) => {
                console.log('Received timerStateUpdated event:', data);

                if (this.isUpdating || (data.timestamp - this.lastUpdateTimestamp < 500)) {
                    return;
                }
                
                this.isUpdating = true;
                this.lastUpdateTimestamp = data.timestamp;
                
                this.status = data.status;
                this.openSeconds = data.openSeconds;
                this.pendingSeconds = data.pendingSeconds;
                this.totalSeconds = data.totalSeconds;
                this.startTime = data.startTime;
                this.pendingStart = data.pendingStart;
                this.pendingStop = data.pendingStop;
                this.closedTime = data.closedTime;
                
                console.log('Updated state after timerStateUpdated:', {
                    status: this.status,
                    startTime: this.startTime,
                    pendingStart: this.pendingStart,
                    pendingStop: this.pendingStop,
                    closedTime: this.closedTime,
                    openSeconds: this.openSeconds,
                    pendingSeconds: this.pendingSeconds,
                    totalSeconds: this.totalSeconds
                });

                this.updateTimer();
                if (this.status !== 'CLOSED') {
                    this.startTimer();
                } else {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                }
                
                this.updateTimerDisplay();
                this.isUpdating = false;
            });
            
            $wire.on('refresh', () => {
                this.updateTimestamps();
            });
        },

        startTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                console.log('Cleared existing timer interval');
            }
            
            this.timerInterval = setInterval(() => {
                this.updateTimer();
            }, 1000);
            console.log('Timer started with interval ID:', this.timerInterval);
        },

        updateTimestamps() {
            if (this.isUpdating) return;
            this.isUpdating = true;
            
            $wire.get('record', (response) => {
                if (response) {
                    this.startTime = response.Open_Time?.timestamp ?? null;
                    this.pendingStart = response.Pending_Start?.timestamp ?? null;
                    this.pendingStop = response.Pending_Stop?.timestamp ?? null;
                    this.closedTime = response.Closed_Time?.timestamp ?? null;
                    this.status = response.Status;
                }
                this.isUpdating = false;
                this.updateTimer();
                if (this.status !== 'CLOSED') {
                    this.startTimer();
                }
            });
        },

        updateTimer() {
            if (this.isUpdating) return;
            
            const now = Math.floor(Date.now() / 1000);
            let openSeconds = this.openSeconds;
            let pendingSeconds = this.pendingSeconds;

            console.log('Updating timer:', {
                now: now,
                startTime: this.startTime,
                status: this.status,
                pendingStart: this.pendingStart,
                pendingStop: this.pendingStop,
                closedTime: this.closedTime
            });

            if (this.startTime) {
                if (this.status === 'CLOSED') {
                    if (this.closedTime) {
                        openSeconds = this.closedTime - this.startTime;
                        if (this.pendingStart && this.pendingStop) {
                            pendingSeconds = this.pendingStop - this.pendingStart;
                            openSeconds -= pendingSeconds;
                        }
                    }
                } else if (this.status === 'PENDING' && this.pendingStart) {
                    openSeconds = this.pendingStart - this.startTime;
                    if (!this.pendingStop) {
                        pendingSeconds = now - this.pendingStart;
                    } else {
                        pendingSeconds = this.pendingStop - this.pendingStart;
                    }
                } else if (this.status === 'OPEN') {
                    openSeconds = now - this.startTime;
                    if (this.pendingStart && this.pendingStop) {
                        pendingSeconds = this.pendingStop - this.pendingStart;
                        openSeconds -= pendingSeconds;
                    }
                }
            } else {
                console.warn('startTime is not set, cannot update timer');
            }

            this.openSeconds = Math.max(0, openSeconds);
            this.pendingSeconds = Math.max(0, pendingSeconds);
            this.totalSeconds = this.openSeconds + this.pendingSeconds;
            
            console.log('Updated timer values:', {
                openSeconds: this.openSeconds,
                pendingSeconds: this.pendingSeconds,
                totalSeconds: this.totalSeconds
            });

            this.updateTimerDisplay();
        },
        
        updateTimerDisplay() {
            if (this.$refs.openClock) {
                this.$refs.openClock.textContent = this.formatTime(this.openSeconds);
            }
            if (this.$refs.pendingClock) {
                this.$refs.pendingClock.textContent = this.formatTime(this.pendingSeconds);
            }
            if (this.$refs.totalClock) {
                this.$refs.totalClock.textContent = this.formatTime(this.totalSeconds);
            }
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
    wire:ignore
>
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
            <svg x-show="darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0ambul>                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />
            </svg>
            <svg x-show="!darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
            </svg>
        </button>
    </div>

    <div class="ticket-timer-grid grid grid-cols-3 gap-4 mb-4">
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-blue-400' : 'text-blue-700'">Open</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="openClock"
                :class="darkMode ? 'text-blue-300' : 'text-blue-600'">00:00:00</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-yellow-400' : 'text-yellow-600'">Pending</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="pendingClock"
                :class="darkMode ? 'text-yellow-300' : 'text-yellow-600'">00:00:00</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50"
            :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2"
                :class="darkMode ? 'text-purple-400' : 'text-purple-700'">Total</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="totalClock"
                :class="darkMode ? 'text-purple-300' : 'text-purple-700'">00:00:00</div>
        </div>
    </div>

    <div class="ticket-timer-status text-sm"
        :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <div class="mb-2">Opened At: {{ $record->Open_Time?->format('M d, Y H:i') ?? 'N/A' }}</div>
        <div class="mb-2">Opened By: {{ $record->openedBy?->name ?? 'Unknown' }}</div>
        @if($record->Status === 'CLOSED')
            <div class="text-green-500 dark:text-green-400">Closed At: {{ $record->Closed_Time?->format('M d, Y H:i') }}</div>
        @endif
    </div>
</div>
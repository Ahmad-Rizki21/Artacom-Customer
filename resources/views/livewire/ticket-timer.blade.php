<!-- File: resources/views/livewire/ticket-timer.blade.php -->
<div
    x-data="{
        // Data awal dari backend
        startTime: {{ $record->Open_Time?->timestamp ?? 'null' }},
        pendingStart: {{ $record->Pending_Start?->timestamp ?? 'null' }},
        pendingStop: {{ $record->Pending_Stop?->timestamp ?? 'null' }},
        closedTime: {{ $record->Closed_Time?->timestamp ?? 'null' }},
        status: '{{ $record->Status }}',
        // Nilai timer awal dari backend (hasil getCurrentTimer)
        openSeconds: {{ $openTimeSeconds ?? 0 }},
        pendingSeconds: {{ $pendingTimeSeconds ?? 0 }},
        totalSeconds: {{ $totalTimeSeconds ?? 0 }},
        // Akumulasi durasi pending dari backend (hanya untuk perhitungan di frontend)
        pendingDurationSeconds: {{ $record->pending_duration_seconds ?? 0 }},

        // State internal frontend
        timerInterval: null,
        isUpdating: false,
        lastUpdateTimestamp: 0,
        darkMode: localStorage.getItem('darkMode') === 'true' || false,

        init() {
            console.log('Alpine Init - Initial state:', {
                status: this.status,
                startTime: this.startTime,
                pendingStart: this.pendingStart,
                pendingStop: this.pendingStop,
                closedTime: this.closedTime,
                openSeconds: this.openSeconds,
                pendingSeconds: this.pendingSeconds,
                totalSeconds: this.totalSeconds,
                pendingDurationSeconds: this.pendingDurationSeconds
            });

            this.updateTimerDisplay(); // Tampilkan nilai awal
            if (this.status !== 'CLOSED') {
                console.log('Alpine Init - Starting timer interval');
                this.startTimer();
            } else {
                console.log('Alpine Init - Timer not started (CLOSED)');
            }

            // Listener untuk update dari Livewire
            $wire.on('timerStateUpdated', (data) => {
                console.log('Alpine Event - Received timerStateUpdated:', data);

                // Optional: Throttle updates on frontend side too
                // if (this.isUpdating || (data.timestamp && this.lastUpdateTimestamp && (data.timestamp - this.lastUpdateTimestamp < 500))) {
                //     console.log('Alpine Event - Skipping update (throttled)');
                //     return;
                // }

                this.isUpdating = true; // Flag to prevent calculation conflicts
                this.lastUpdateTimestamp = data.timestamp;

                // Update state DARI BACKEND
                this.status = data.status;
                this.startTime = data.startTime || this.startTime;
                this.pendingStart = data.pendingStart || this.pendingStart;
                this.pendingStop = data.pendingStop || this.pendingStop;
                this.closedTime = data.closedTime || this.closedTime;
                this.pendingDurationSeconds = data.pendingDurationSeconds !== undefined ? data.pendingDurationSeconds : this.pendingDurationSeconds;

                // LANGSUNG GUNAKAN NILAI DARI BACKEND untuk sinkronisasi
                this.openSeconds = data.openSeconds !== undefined ? data.openSeconds : this.openSeconds;
                this.pendingSeconds = data.pendingSeconds !== undefined ? data.pendingSeconds : this.pendingSeconds;
                this.totalSeconds = data.totalSeconds !== undefined ? data.totalSeconds : this.totalSeconds;

                console.log('Alpine Event - Updated state:', {
                    status: this.status,
                    startTime: this.startTime,
                    pendingStart: this.pendingStart,
                    pendingStop: this.pendingStop,
                    closedTime: this.closedTime,
                    openSeconds: this.openSeconds,
                    pendingSeconds: this.pendingSeconds,
                    totalSeconds: this.totalSeconds,
                    pendingDurationSeconds: this.pendingDurationSeconds
                });

                // Update tampilan segera setelah menerima data backend
                this.updateTimerDisplay();

                // Atur ulang interval jika status berubah
                if (this.status !== 'CLOSED') {
                    this.startTimer(); // Pastikan timer berjalan jika belum CLOSED
                } else {
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                        console.log('Alpine Event - Timer stopped (CLOSED)');
                    }
                }
                this.isUpdating = false;
            });
        },

        startTimer() {
            // Hanya mulai interval jika belum ada dan status bukan CLOSED
            if (!this.timerInterval && this.status !== 'CLOSED') {
                this.timerInterval = setInterval(() => {
                    this.updateTimer(); // Panggil perhitungan frontend setiap detik
                }, 1000);
                console.log('Alpine Timer - Interval started:', this.timerInterval);
            } else if (this.timerInterval && this.status === 'CLOSED') {
                 // Hentikan jika status berubah jadi CLOSED
                 clearInterval(this.timerInterval);
                 this.timerInterval = null;
                 console.log('Alpine Timer - Interval stopped (CLOSED)');
            }
        },

        // Fungsi ini berjalan setiap detik di frontend untuk update visual
        updateTimer() {
            if (this.isUpdating || this.status === 'CLOSED' || !this.startTime) return;

            const now = Math.floor(Date.now() / 1000);
            let currentOpenSeconds = 0;
            let currentPendingSeconds = 0;
            let currentTotalSeconds = 0;

            const totalElapsedSeconds = now - this.startTime;
            const accumulatedPending = this.pendingDurationSeconds;

            if (this.status === 'PENDING' && this.pendingStart) {
                const currentPendingDuration = now - this.pendingStart;
                currentPendingSeconds = accumulatedPending + currentPendingDuration;
                currentOpenSeconds = totalElapsedSeconds - currentPendingSeconds;
                currentTotalSeconds = totalElapsedSeconds;
            } else { // Status OPEN atau lainnya (asumsi PENDING sudah ditangani)
                currentPendingSeconds = accumulatedPending;
                currentOpenSeconds = totalElapsedSeconds - currentPendingSeconds;
                currentTotalSeconds = totalElapsedSeconds;
            }

            // Update nilai state frontend (hanya untuk tampilan)
            this.openSeconds = Math.max(0, currentOpenSeconds);
            this.pendingSeconds = Math.max(0, currentPendingSeconds);
            this.totalSeconds = Math.max(0, currentTotalSeconds);

            // Update tampilan
            this.updateTimerDisplay();
        },

        updateTimerDisplay() {
            // Pastikan elemen ada sebelum mencoba update
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
            const absSeconds = Math.max(0, Math.floor(seconds)); // Pastikan integer non-negatif
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
    <!-- Bagian Tampilan HTML (tetap sama) -->
    <div class="ticket-timer-header flex items-center justify-between mb-4">
        <!-- ... header content ... -->
         <h3 class="ticket-timer-title font-semibold text-lg">Ticket Timer</h3>
         <!-- ... dark mode toggle ... -->
    </div>

    <div class="ticket-timer-grid grid grid-cols-3 gap-4 mb-4">
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2" :class="darkMode ? 'text-blue-400' : 'text-blue-700'">Open</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="openClock" :class="darkMode ? 'text-blue-300' : 'text-blue-600'">00:00:00</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2" :class="darkMode ? 'text-yellow-400' : 'text-yellow-600'">Pending</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="pendingClock" :class="darkMode ? 'text-yellow-300' : 'text-yellow-600'">00:00:00</div>
        </div>
        <div class="ticket-timer-cell text-center p-3 rounded-lg bg-opacity-50" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <div class="ticket-timer-label text-xs font-medium uppercase mb-2" :class="darkMode ? 'text-purple-400' : 'text-purple-700'">Total</div>
            <div class="ticket-timer-value text-xl font-mono" x-ref="totalClock" :class="darkMode ? 'text-purple-300' : 'text-purple-700'">00:00:00</div>
        </div>
    </div>

    <div class="ticket-timer-status text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
         <!-- ... status details ... -->
        <div class="mb-2">Opened At: {{ $record->Open_Time?->format('M d, Y H:i') ?? 'N/A' }}</div>
        <div class="mb-2">Opened By: {{ $record->openedBy?->name ?? 'Unknown' }}</div>
        @if($record->Status === 'CLOSED')
            <div class="text-green-500 dark:text-green-400">Closed At: {{ $record->Closed_Time?->format('M d, Y H:i') }}</div>
        @endif
    </div>
</div>

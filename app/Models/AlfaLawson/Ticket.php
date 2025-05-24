<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AlfaLawson\TableRemote;
use App\Models\User;
use App\Models\AlfaLawson\TicketAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'No_Ticket';
    public $incrementing = false;
    protected $keyType = 'string';

    const TICKET_PREFIX = 'TI-';
    const STATUS_OPEN = 'OPEN';
    const STATUS_PENDING = 'PENDING';
    const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'No_Ticket',
        'Customer',
        'Catagory',
        'Site_ID',
        'Problem',
        'Reported_By',
        'pic',
        'tlp_pic',
        'Status',
        'Open_By',
        'Open_Level',
        'Closed_By',
        'Closed_Time',
        'Closed_Level',
        'Open_Time',
        'Pending_Start',
        'Pending_Stop',
        'Pending_Reason',
        'Problem_Summary',
        'Classification',
        'Action_Summry',
        'pending_duration_seconds',
    ];

    protected $casts = [
        'Open_Time' => 'datetime',
        'Pending_Start' => 'datetime',
        'Pending_Stop' => 'datetime',
        'Closed_Time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('Status')) {
                $oldStatus = $model->getOriginal('Status');
                $newStatus = $model->Status;

                Log::info('Ticket status changing', [
                    'ticket' => $model->No_Ticket,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'current_pending_duration' => $model->pending_duration_seconds ?? 0,
                    'pending_start' => $model->Pending_Start?->toDateTimeString(),
                    'pending_stop' => $model->Pending_Stop?->toDateTimeString(),
                ]);

                switch ($newStatus) {
                    case self::STATUS_PENDING:
                        // Validasi alasan pending
                        if (empty(trim($model->Pending_Reason))) {
                            throw new \Exception('Mohon isi alasan pending ticket terlebih dahulu');
                        }

                        // Hitung durasi pending sebelumnya jika ada periode pending yang sudah selesai
                        if ($oldStatus === self::STATUS_OPEN && 
                            $model->Pending_Start && 
                            $model->Pending_Stop) {
                            
                            $pendingStart = Carbon::parse($model->Pending_Start);
                            $pendingStop = Carbon::parse($model->Pending_Stop);
                            
                            // Pastikan Pending_Stop lebih besar dari Pending_Start
                            if ($pendingStop->gt($pendingStart)) {
                                $additionalPendingSeconds = $pendingStop->timestamp - $pendingStart->timestamp;
                                $currentDuration = $model->pending_duration_seconds ?? 0;
                                $newDuration = max(0, $currentDuration + $additionalPendingSeconds);
                                
                                Log::debug('Adding previous pending duration', [
                                    'ticket' => $model->No_Ticket,
                                    'pending_start' => $pendingStart->toDateTimeString(),
                                    'pending_stop' => $pendingStop->toDateTimeString(),
                                    'additional_seconds' => $additionalPendingSeconds,
                                    'current_duration' => $currentDuration,
                                    'new_duration' => $newDuration,
                                ]);
                                
                                $model->pending_duration_seconds = $newDuration;
                            } else {
                                Log::warning('Invalid pending period detected, skipping duration calculation', [
                                    'ticket' => $model->No_Ticket,
                                    'pending_start' => $pendingStart->toDateTimeString(),
                                    'pending_stop' => $pendingStop->toDateTimeString(),
                                ]);
                            }
                        }

                        // Set periode pending baru
                        $model->Pending_Start = now();
                        $model->Pending_Stop = null;
                        break;

                    case self::STATUS_CLOSED:
                        // Validasi Action Summary
                        if (empty(trim($model->Action_Summry))) {
                            throw new \Exception('Mohon isi ringkasan tindakan (Action Summary) sebelum menutup ticket');
                        }

                        // Set data penutupan
                        $model->Closed_Time = now();
                        $model->Closed_By = Auth::id();

                        // Jika sebelumnya pending, hitung durasi pending saat ini
                        if ($oldStatus === self::STATUS_PENDING && $model->Pending_Start) {
                            $pendingStart = Carbon::parse($model->Pending_Start);
                            $now = now();
                            $additionalPendingSeconds = $now->timestamp - $pendingStart->timestamp;
                            $currentDuration = $model->pending_duration_seconds ?? 0;
                            $newDuration = max(0, $currentDuration + $additionalPendingSeconds);
                            
                            Log::debug('Adding current pending duration on close', [
                                'ticket' => $model->No_Ticket,
                                'pending_start' => $pendingStart->toDateTimeString(),
                                'now' => $now->toDateTimeString(),
                                'additional_seconds' => $additionalPendingSeconds,
                                'current_duration' => $currentDuration,
                                'new_duration' => $newDuration,
                            ]);
                            
                            $model->pending_duration_seconds = $newDuration;
                            $model->Pending_Stop = $now;
                        }
                        break;

                    case self::STATUS_OPEN:
                        // Jika sebelumnya pending, hitung durasi pending saat ini
                        if ($oldStatus === self::STATUS_PENDING && $model->Pending_Start) {
                            $pendingStart = Carbon::parse($model->Pending_Start);
                            $now = now();
                            $additionalPendingSeconds = $now->timestamp - $pendingStart->timestamp;
                            $currentDuration = $model->pending_duration_seconds ?? 0;
                            $newDuration = max(0, $currentDuration + $additionalPendingSeconds);
                            
                            Log::debug('Adding current pending duration on reopen', [
                                'ticket' => $model->No_Ticket,
                                'pending_start' => $pendingStart->toDateTimeString(),
                                'now' => $now->toDateTimeString(),
                                'additional_seconds' => $additionalPendingSeconds,
                                'current_duration' => $currentDuration,
                                'new_duration' => $newDuration,
                            ]);
                            
                            $model->pending_duration_seconds = $newDuration;
                            $model->Pending_Stop = $now;
                        }
                        break;
                }

                Log::info('Ticket status changed', [
                    'ticket' => $model->No_Ticket,
                    'final_status' => $newStatus,
                    'final_pending_duration' => $model->pending_duration_seconds ?? 0,
                    'pending_start' => $model->Pending_Start?->toDateTimeString(),
                    'pending_stop' => $model->Pending_Stop?->toDateTimeString(),
                ]);
            }
        });

        // Tambahkan event untuk memastikan pending_duration_seconds tidak pernah negatif
        static::saving(function ($model) {
            if (isset($model->attributes['pending_duration_seconds'])) {
                $model->attributes['pending_duration_seconds'] = max(0, (int) $model->attributes['pending_duration_seconds']);
            }
        });
    }

    // Helper method untuk set pending duration dengan validasi
    private function setPendingDurationSeconds($seconds)
    {
        $this->attributes['pending_duration_seconds'] = max(0, (int) $seconds);
    }

    // Duration calculations
    public function getOpenDurationAttribute()
    {
        if (!$this->Open_Time) return 0;

        $start = Carbon::parse($this->Open_Time)->timestamp;
        $now = Carbon::now()->timestamp;

        if ($this->Status === 'CLOSED' && $this->Closed_Time) {
            $end = Carbon::parse($this->Closed_Time)->timestamp;
            $duration = $end - $start;
            $duration -= $this->pending_duration_seconds ?? 0;
            return max(0, $duration);
        }

        if ($this->Status === 'PENDING' && $this->Pending_Start) {
            $end = Carbon::parse($this->Pending_Start)->timestamp;
            $duration = $end - $start;
            $duration -= ($this->pending_duration_seconds ?? 0);
            return max(0, $duration);
        }

        if ($this->Status === 'OPEN') {
            $duration = $now - $start;
            $duration -= ($this->pending_duration_seconds ?? 0);
            return max(0, $duration);
        }

        return 0;
    }

    public function getPendingDurationAttribute()
    {
        return max(0, $this->pending_duration_seconds ?? 0);
    }

    public function getCurrentTimer()
    {
        $now = now()->getTimestamp();
        $openSeconds = 0;
        $pendingSeconds = 0;

        if ($this->Open_Time) {
            $openStart = $this->Open_Time->getTimestamp();

            if ($this->Status === 'CLOSED' && $this->Closed_Time) {
                $openSeconds = $this->Closed_Time->getTimestamp() - $openStart;
            } else {
                $openSeconds = $now - $openStart;
            }

            // Ambil akumulasi pending dari database
            $pendingSeconds = max(0, $this->pending_duration_seconds ?? 0);

            // Jika sedang pending, tambahkan durasi pending saat ini
            if ($this->Status === 'PENDING' && $this->Pending_Start) {
                $currentPendingSeconds = $now - $this->Pending_Start->getTimestamp();
                $pendingSeconds += max(0, $currentPendingSeconds);
            }

            // Kurangi total pending dari open seconds
            $openSeconds = max(0, $openSeconds - ($this->pending_duration_seconds ?? 0));
        }

        $totalSeconds = $openSeconds + $pendingSeconds;

        Log::debug('getCurrentTimer result', [
            'ticket' => $this->No_Ticket,
            'openSeconds' => $openSeconds,
            'pendingSeconds' => $pendingSeconds,
            'totalSeconds' => $totalSeconds,
            'status' => $this->Status,
            'pending_duration_seconds' => $this->pending_duration_seconds ?? 0,
            'Pending_Start' => $this->Pending_Start?->timestamp,
            'now' => $now,
        ]);

        return [
            'open' => ['seconds' => max(0, $openSeconds)],
            'pending' => ['seconds' => max(0, $pendingSeconds)],
            'total' => ['seconds' => max(0, $totalSeconds)],
        ];
    }

    public function getTotalDurationAttribute()
    {
        return $this->getOpenDurationAttribute() + $this->getPendingDurationAttribute();
    }

    private function formatDuration(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', 
            floor($seconds / 3600), 
            floor(($seconds % 3600) / 60), 
            $seconds % 60
        );
    }

    // Relationships
    public function remote(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }

    public function customerData(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Customer', 'Customer');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Open_By', 'id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Closed_By', 'id');
    }

    public function actions()
    {
        return $this->hasMany(TicketAction::class, 'No_Ticket', 'No_Ticket');
    }

    // Method to update an action (for EditActionModal)
    public function updateAction($actionId, array $data)
    {
        // Validasi data yang masuk
        if (!isset($data['action_taken']) || !isset($data['action_description'])) {
            throw new \Exception('Action Taken dan Action Description harus diisi.');
        }

        // Cari action berdasarkan ID
        $action = $this->actions()->findOrFail($actionId);

        // Simpan nilai sebelumnya untuk perbandingan
        $oldActionTaken = $action->Action_Taken;
        $newActionTaken = $data['action_taken'];

        // Update data action
        $action->update([
            'Action_Taken' => $newActionTaken,
            'Action_Description' => $data['action_description'],
            'Action_Time' => now(),
            'Action_By' => Auth::user()->name,
            'Action_Level' => Auth::user()->Level ?? 'Level 1',
        ]);

        // Jika Action_Taken berubah, perbarui status ticket sesuai logika
        if ($oldActionTaken !== $newActionTaken) {
            switch ($newActionTaken) {
                case 'Pending Clock':
                    $this->Status = self::STATUS_PENDING;
                    $this->Pending_Reason = $data['action_description'];
                    // Pending_Start dan validasi akan ditangani oleh boot() method
                    break;

                case 'Start Clock':
                    $this->Status = self::STATUS_OPEN;
                    // Pending_Stop dan perhitungan durasi akan ditangani oleh boot() method
                    break;

                case 'Closed':
                    if (empty(trim($data['action_description']))) {
                        throw new \Exception('Mohon isi deskripsi aksi sebelum menutup ticket.');
                    }
                    $this->Status = self::STATUS_CLOSED;
                    $this->Action_Summry = $data['action_description'];
                    // Closed_Time, Closed_By, dan perhitungan durasi akan ditangani oleh boot() method
                    break;

                case 'Note':
                    // Tidak perlu ubah status ticket untuk Note
                    break;

                default:
                    throw new \Exception('Action Taken tidak valid: ' . $newActionTaken);
            }

            // Simpan perubahan status ticket
            $this->save();
        }

        Log::info("Action updated for ticket: {$this->No_Ticket}, Action ID: {$actionId}", [
            'Action_Taken' => $newActionTaken,
            'Action_Description' => $data['action_description'],
        ]);

        return $action;
    }

    // Helpers
    public function isOpen(): bool { return $this->Status === self::STATUS_OPEN; }
    public function isPending(): bool { return $this->Status === self::STATUS_PENDING; }
    public function isClosed(): bool { return $this->Status === self::STATUS_CLOSED; }

    public function getStatusColorAttribute(): string
    {
        return match($this->Status) {
            self::STATUS_OPEN => 'warning',
            self::STATUS_PENDING => 'info',
            self::STATUS_CLOSED => 'success',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeOpen($query) { return $query->where('Status', self::STATUS_OPEN); }
    public function scopePending($query) { return $query->where('Status', self::STATUS_PENDING); }
    public function scopeClosed($query) { return $query->where('Status', self::STATUS_CLOSED); }
    public function scopeByCustomer($query, $customer) { return $query->where('Customer', $customer); }
    public function scopeBySiteId($query, $siteId) { return $query->where('Site_ID', $siteId); }
    public function scopeCreatedToday($query) { return $query->whereDate('created_at', Carbon::today()); }

    // Static
    public static function generateTicketNumber(): string
    {
        $lastNumber = (int) substr(
            static::orderBy('created_at', 'desc')->value('No_Ticket') ?? self::TICKET_PREFIX.'0000000',
            3
        );
        return self::TICKET_PREFIX . str_pad($lastNumber + 1, 7, '0', STR_PAD_LEFT);
    }
}
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
        'Action_Summry'
    ];

    protected $casts = [
        'Open_Time' => 'datetime',
        'Pending_Start' => 'datetime',
        'Pending_Stop' => 'datetime',
        'Closed_Time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // Automatically create an "Open" action when ticket is created
            TicketAction::create([
                'No_Ticket' => $model->No_Ticket,
                'Action_Taken' => 'Start Clock',
                'Action_Time' => $model->Open_Time,
                'Action_By' => $model->openedBy->name ?? Auth::user()->name,
                'Action_Level' => $model->Open_Level, // Use the ticket's Open_Level
                'Action_Description' => $model->Problem
            ]);
        });

        static::creating(function ($model) {
            if (!$model->No_Ticket) {
                $model->No_Ticket = static::generateTicketNumber();
            }

            $model->Status = static::STATUS_OPEN;
            $model->Open_By = Auth::id();
            $model->Open_Time = now();
            $model->Reported_By = $model->Reported_By ?: Auth::id();
            
            // Set Open_Level from the user's Level
            $model->Open_Level = Auth::user()->Level ?? 'Level 1';

            Log::info("New ticket created: {$model->No_Ticket} by " . Auth::user()->name);
        });

        // static::updating(function ($model) {
        //         if ($model->isDirty('Status')) {
        //             $oldStatus = $model->getOriginal('Status');
        //             $newStatus = $model->Status;

        //             switch ($newStatus) {
        //                 case static::STATUS_PENDING:
        //                     $model->Pending_Start = now();
        //                     $model->Pending_Stop = null;
        //                     if (empty(trim($model->Pending_Reason))) {
        //                         throw new \Exception('Mohon isi alasan pending ticket terlebih dahulu');
        //                     }
        //                     break;

        //                 case static::STATUS_CLOSED:
        //                     if (empty(trim($model->Action_Summry))) {
        //                         throw new \Exception('Mohon isi ringkasan tindakan (Action Summary) sebelum menutup ticket');
        //                     }
        //                     // Remove the length check (strlen < 10)
        //                     $model->Closed_Time = now();
        //                     $model->Closed_By = Auth::id();
        //                     if ($oldStatus === static::STATUS_PENDING) {
        //                         $model->Pending_Stop = now();
        //                     }
        //                     break;

        //                 case static::STATUS_OPEN:
        //                     if ($oldStatus === static::STATUS_PENDING) {
        //                         $model->Pending_Stop = now();
        //                     }
        //                     break;
        //             }
        //         }
        //     });
        static::updating(function ($model) {
            if ($model->isDirty('Status')) {
                $oldStatus = $model->getOriginal('Status');
                $newStatus = $model->Status;

                switch ($newStatus) {
                    case static::STATUS_PENDING:
                        $model->Pending_Start = now();
                        $model->Pending_Stop = null;
                        if (empty(trim($model->Pending_Reason))) {
                            throw new \Exception('Mohon isi alasan pending ticket terlebih dahulu');
                        }
                        break;

                    case static::STATUS_CLOSED:
                        if (empty(trim($model->Action_Summry))) {
                            throw new \Exception('Mohon isi ringkasan tindakan (Action Summary) sebelum menutup ticket');
                        }
                        $model->Closed_Time = now();
                        $model->Closed_By = Auth::id();
                        if ($oldStatus === static::STATUS_PENDING) {
                            $model->Pending_Stop = now();
                        }
                        break;

                    case static::STATUS_OPEN:
                        if ($oldStatus === static::STATUS_PENDING) {
                            $model->Pending_Stop = now();
                        }
                        break;
                }
            }
        });
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

            if ($this->Pending_Start && $this->Pending_Stop) {
                $pendingDuration = Carbon::parse($this->Pending_Stop)->timestamp - Carbon::parse($this->Pending_Start)->timestamp;
                $duration -= $pendingDuration;
            }
            return max(0, $duration);
        }

        if ($this->Status === 'PENDING' && $this->Pending_Start) {
            $end = Carbon::parse($this->Pending_Start)->timestamp;
            return max(0, $end - $start);
        }

        if ($this->Status === 'OPEN') {
            $duration = $now - $start;
            if ($this->Pending_Start && $this->Pending_Stop) {
                $pendingDuration = Carbon::parse($this->Pending_Stop)->timestamp - Carbon::parse($this->Pending_Start)->timestamp;
                $duration -= $pendingDuration;
            }
            return max(0, $duration);
        }

        return 0;
    }
public function getPendingDurationAttribute()
    {
        if (!$this->Pending_Start) return 0;

        $start = Carbon::parse($this->Pending_Start)->timestamp;
        $now = Carbon::now()->timestamp;

        if ($this->Pending_Stop) {
            $end = Carbon::parse($this->Pending_Stop)->timestamp;
            return max(0, $end - $start);
        }

        if ($this->Status === 'PENDING') {
            return max(0, $now - $start);
        }

        return 0;
    }

    public function getCurrentTimer()
{
    $now = now()->getTimestamp();
    $openSeconds = 0;
    $pendingSeconds = 0;

    if ($this->Open_Time) {
        $openStart = $this->Open_Time->getTimestamp();

        if ($this->Status === 'CLOSED' && $this->Closed_Time) {
            // Hitung durasi open hingga Closed_Time
            $openSeconds = $this->Closed_Time->getTimestamp() - $openStart;
        } else {
            // Hitung durasi open hingga sekarang
            $openSeconds = $now - $openStart;
        }

        if ($this->Pending_Start) {
            $pendingStart = $this->Pending_Start->getTimestamp();
            if ($this->Pending_Stop) {
                // Jika Pending_Stop ada, hitung durasi pending hingga Pending_Stop
                $pendingSeconds = $this->Pending_Stop->getTimestamp() - $pendingStart;
                if ($this->Status !== 'PENDING') {
                    // Kurangi durasi pending dari durasi open
                    $openSeconds -= $pendingSeconds;
                }
            } elseif ($this->Status === 'PENDING') {
                // Jika masih PENDING, hitung durasi pending hingga sekarang
                $pendingSeconds = $now - $pendingStart;
            }
        }
    }

    $totalSeconds = $openSeconds + $pendingSeconds;

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

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Reported_By', 'id');
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
            'Action_Time' => now(), // Update waktu aksi
            'Action_By' => Auth::user()->name,
            'Action_Level' => Auth::user()->Level ?? 'Level 1',
        ]);

        // Jika Action_Taken berubah, perbarui status ticket sesuai logika
        if ($oldActionTaken !== $newActionTaken) {
            switch ($newActionTaken) {
                case 'Pending Clock':
                    $this->Status = self::STATUS_PENDING;
                    $this->Pending_Reason = $data['action_description'];
                    $this->Pending_Start = now();
                    $this->Pending_Stop = null;
                    break;

                case 'Start Clock':
                    $this->Status = self::STATUS_OPEN;
                    if ($oldActionTaken === 'Pending Clock') {
                        $this->Pending_Stop = now();
                    }
                    break;

                case 'Closed':
                    if (empty(trim($data['action_description']))) {
                        throw new \Exception('Mohon isi deskripsi aksi sebelum menutup ticket.');
                    }
                    $this->Status = self::STATUS_CLOSED;
                    $this->Action_Summry = $data['action_description'];
                    $this->Closed_Time = now();
                    $this->Closed_By = Auth::id();
                    $this->Closed_Level = Auth::user()->Level ?? 'Level 1';
                    if ($oldActionTaken === 'Pending Clock') {
                        $this->Pending_Stop = now();
                    }
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
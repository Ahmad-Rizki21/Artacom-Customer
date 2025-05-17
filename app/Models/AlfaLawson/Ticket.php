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

    // Revisi perhitungan durasi
    public function getOpenDurationAttribute(): string
    {
        if (!$this->Open_Time) {
            return '00:00:00';
        }

        $endTime = now();

        if ($this->Status === self::STATUS_PENDING) {
            $endTime = $this->Pending_Start ?? now();
        } elseif ($this->Status === self::STATUS_CLOSED) {
            $endTime = $this->Closed_Time;
        }

        $seconds = $this->Open_Time->diffInSeconds($endTime);

        if ($this->Status === self::STATUS_OPEN && $this->Pending_Start && $this->Pending_Stop) {
            $pendingSeconds = $this->Pending_Start->diffInSeconds($this->Pending_Stop);
            $seconds = max(0, $seconds - $pendingSeconds);
        }

        return $this->formatDuration($seconds);
    }

    public function getPendingDurationAttribute(): string
    {
        if (!$this->Pending_Start) {
            return '00:00:00';
        }

        $totalPendingSeconds = 0;

        if ($this->Status === self::STATUS_PENDING) {
            $totalPendingSeconds = $this->Pending_Start->diffInSeconds(now());
        } elseif ($this->Pending_Stop) {
            $totalPendingSeconds = $this->Pending_Start->diffInSeconds($this->Pending_Stop);
        }

        return $this->formatDuration($totalPendingSeconds);
    }

    public function getTotalDurationAttribute(): string
    {
        if (!$this->Open_Time) {
            return '00:00:00';
        }

        $endTime = match($this->Status) {
            self::STATUS_CLOSED => $this->Closed_Time,
            default => now()
        };

        $totalSeconds = $this->Open_Time->diffInSeconds($endTime);

        if ($this->Status === self::STATUS_PENDING && $this->Pending_Start) {
            $pendingSeconds = $this->Pending_Start->diffInSeconds(now());
            $totalSeconds = max(0, $totalSeconds - $pendingSeconds);
        } elseif ($this->Pending_Start && $this->Pending_Stop) {
            $pendingSeconds = $this->Pending_Start->diffInSeconds($this->Pending_Stop);
            $totalSeconds = max(0, $totalSeconds - $pendingSeconds);
        }

        return $this->formatDuration($totalSeconds);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->No_Ticket) {
                $model->No_Ticket = static::generateTicketNumber();
            }

            $model->Status = static::STATUS_OPEN;
            $model->Open_By = Auth::id();
            $model->Open_Time = now();
            $model->Reported_By = $model->Reported_By ?: Auth::id();

            Log::info("New ticket created: {$model->No_Ticket} by " . Auth::user()->name);
        });

        static::updating(function ($model) {
            if ($model->isDirty('Status')) {
                $oldStatus = $model->getOriginal('Status');
                $newStatus = $model->Status;

                switch ($newStatus) {
                    case static::STATUS_PENDING:
                        $model->Pending_Start = now();
                        $model->Pending_Stop = null;
                        Log::info("Pending Reason: " . ($model->Pending_Reason ?? 'null'));
                        if (empty(trim($model->Pending_Reason))) {
                            throw new \Exception('Mohon isi alasan pending ticket terlebih dahulu');
                        }
                        break;

                    case static::STATUS_CLOSED:
                        if (empty(trim($model->Action_Summry))) {
                            throw new \Exception('Mohon isi ringkasan tindakan (Action Summary) sebelum menutup ticket');
                        }
                        if (strlen(trim($model->Action_Summry)) < 10) {
                            throw new \Exception('Action Summary terlalu singkat, mohon berikan penjelasan yang lebih detail');
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

                Log::info("Ticket {$model->No_Ticket} status changed from {$oldStatus} to {$newStatus} by " . Auth::user()->name);
            }
        });
    }

    // Static Methods
    public static function generateTicketNumber(): string
    {
        $lastTicket = static::orderBy('created_at', 'desc')->first();
        if (!$lastTicket) {
            return self::TICKET_PREFIX . '0000001';
        }

        $lastNumber = intval(substr($lastTicket->No_Ticket, 3));
        $newNumber = str_pad($lastNumber + 1, 7, '0', STR_PAD_LEFT);
        return self::TICKET_PREFIX . $newNumber;
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

    public function getOpenedByNameAttribute(): string
    {
        return $this->openedBy?->name ?? 'Unknown User';
    }

    // Accessors & Mutators
    public function getStatusColorAttribute(): string
    {
        return match($this->Status) {
            static::STATUS_OPEN => 'warning',
            static::STATUS_PENDING => 'info',
            static::STATUS_CLOSED => 'success',
            default => 'secondary'
        };
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->Status === static::STATUS_PENDING;
    }

    public function isClosed(): bool
    {
        return $this->Status === static::STATUS_CLOSED;
    }

    public function isOpen(): bool
    {
        return $this->Status === static::STATUS_OPEN;
    }

    public function getResolutionTimeAttribute(): ?string
    {
        if (!$this->Open_Time || !$this->Closed_Time) {
            return null;
        }

        $duration = $this->Open_Time->diffInMinutes($this->Closed_Time);
        if ($this->Pending_Start && $this->Pending_Stop) {
            $pendingDuration = $this->Pending_Start->diffInMinutes($this->Pending_Stop);
            $duration -= $pendingDuration;
        }

        return Carbon::createFromTimestamp(0)
            ->addMinutes($duration)
            ->format('H:i:s');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('Status', static::STATUS_OPEN);
    }

    public function scopePending($query)
    {
        return $query->where('Status', static::STATUS_PENDING);
    }

    public function scopeClosed($query)
    {
        return $query->where('Status', static::STATUS_CLOSED);
    }

    public function scopeByCustomer($query, $customer)
    {
        return $query->where('Customer', $customer);
    }

    public function scopeBySiteId($query, $siteId)
    {
        return $query->where('Site_ID', $siteId);
    }


    public function actions()
    {
        return $this->hasMany(TicketAction::class, 'No_Ticket', 'No_Ticket');
    }

    public function scopeCreatedToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }
}
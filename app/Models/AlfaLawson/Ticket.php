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
            }
        });
    }

    // Duration calculations
    public function getOpenDurationAttribute(): string
    {
        if (!$this->Open_Time) return '00:00:00';

        $endTime = match($this->Status) {
            self::STATUS_PENDING => $this->Pending_Start ?? now(),
            self::STATUS_CLOSED => $this->Closed_Time,
            default => now()
        };

        $seconds = $this->Open_Time->diffInSeconds($endTime);

        if ($this->Status === self::STATUS_OPEN && $this->Pending_Start && $this->Pending_Stop) {
            $seconds -= $this->Pending_Start->diffInSeconds($this->Pending_Stop);
        }

        return $this->formatDuration(max(0, $seconds));
    }

    public function getPendingDurationAttribute(): string
    {
        if (!$this->Pending_Start) return '00:00:00';

        $seconds = $this->Status === self::STATUS_PENDING
            ? $this->Pending_Start->diffInSeconds(now())
            : ($this->Pending_Stop ? $this->Pending_Start->diffInSeconds($this->Pending_Stop) : 0);

        return $this->formatDuration($seconds);
    }

    public function getTotalDurationAttribute(): string
    {
        if (!$this->Open_Time) return '00:00:00';

        $endTime = $this->Status === self::STATUS_CLOSED 
            ? $this->Closed_Time 
            : now();

        $seconds = $this->Open_Time->diffInSeconds($endTime);

        if ($this->Pending_Start) {
            $pendingEnd = $this->Status === self::STATUS_PENDING 
                ? now() 
                : ($this->Pending_Stop ?: $this->Open_Time);
            $seconds -= $this->Pending_Start->diffInSeconds($pendingEnd);
        }

        return $this->formatDuration(max(0, $seconds));
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
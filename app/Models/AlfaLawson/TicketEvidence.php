<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class TicketEvidence extends Model
{
    protected $table = 'ticket_evidences';

    protected $fillable = [
    'No_Ticket',
    'file_name',
    'file_path',
    'file_type',
    'mime_type',
    'file_size',
    'description',
    'uploaded_by',
    'upload_stage',
];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // File type constants
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_DOCUMENT = 'document';

    // Upload stage constants
    const STAGE_INITIAL = 'initial';
    const STAGE_INVESTIGATION = 'investigation';
    const STAGE_RESOLUTION = 'resolution';
    const STAGE_CLOSED = 'closed';

    /**
     * Relationship to Ticket
     */
    public function ticket(): BelongsTo
{
    return $this->belongsTo(Ticket::class, 'No_Ticket', 'No_Ticket');
}

    /**
     * Relationship to User who uploaded
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is image
     */
    public function isImage(): bool
    {
        return $this->file_type === self::TYPE_IMAGE;
    }

    /**
     * Check if file is video
     */
    public function isVideo(): bool
    {
        return $this->file_type === self::TYPE_VIDEO;
    }

    /**
     * Check if file is document
     */
    public function isDocument(): bool
    {
        return $this->file_type === self::TYPE_DOCUMENT;
    }

    /**
     * Get file type from MIME type
     */
    public static function getFileTypeFromMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return self::TYPE_IMAGE;
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return self::TYPE_VIDEO;
        }
        
        return self::TYPE_DOCUMENT;
    }

    /**
     * Get file icon based on type
     */
    public function getFileIconAttribute(): string
    {
        return match ($this->file_type) {
            self::TYPE_IMAGE => 'heroicon-o-photo',
            self::TYPE_VIDEO => 'heroicon-o-video-camera',
            self::TYPE_DOCUMENT => 'heroicon-o-document',
            default => 'heroicon-o-paper-clip',
        };
    }

    /**
     * Get upload stage label
     */
    public function getUploadStageLabelAttribute(): string
    {
        return match ($this->upload_stage) {
            self::STAGE_INITIAL => 'Initial Report',
            self::STAGE_INVESTIGATION => 'Investigation',
            self::STAGE_RESOLUTION => 'Resolution',
            self::STAGE_CLOSED => 'Closed',
            default => ucfirst($this->upload_stage),
        };
    }

    /**
     * Get upload stage color for badges
     */
    public function getUploadStageColorAttribute(): string
    {
        return match ($this->upload_stage) {
            self::STAGE_INITIAL => 'info',
            self::STAGE_INVESTIGATION => 'warning',
            self::STAGE_RESOLUTION => 'success',
            self::STAGE_CLOSED => 'gray',
            default => 'secondary',
        };
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file when evidence is deleted
        static::deleting(function ($evidence) {
            if (Storage::disk('public')->exists($evidence->file_path)) {
                Storage::disk('public')->delete($evidence->file_path);
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeImages($query)
    {
        return $query->where('file_type', self::TYPE_IMAGE);
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', self::TYPE_VIDEO);
    }

    public function scopeDocuments($query)
    {
        return $query->where('file_type', self::TYPE_DOCUMENT);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('upload_stage', $stage);
    }
}
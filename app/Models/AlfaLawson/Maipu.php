<?php

namespace App\Models\AlfaLawson;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AlfaLawson\RemoteAtmbsi;
use App\Models\AlfaLawson\MaipuHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maipu extends Model
{
    protected $table = 'table_maipu';
    protected $primaryKey = 'SN';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'SN', 'Model', 'Kepemilikan', 'tgl_beli', 'garansi', 'Site_ID', 'Status', 'Deskripsi'
    ];

    protected $casts = [
        'tgl_beli' => 'date',
    ];

    public function remoteAtmbsi(): BelongsTo
    {
        return $this->belongsTo(RemoteAtmbsi::class, 'Site_ID', 'Site_ID');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaipuHistory::class, 'maipu_sn', 'SN')
            ->with('user')
            ->orderBy('changed_at', 'desc');
    }

    protected static function booted()
    {
        static::created(function ($maipu) {
            try {
                MaipuHistory::create([
                    'maipu_sn' => $maipu->SN,
                    'action' => 'created',
                    'status' => $maipu->Status,
                    'note' => 'Perangkat Maipu baru ditambahkan.',
                    'old_values' => null,
                    'new_values' => $maipu->only($maipu->getFillable()),
                    'changed_by' => auth()->id() ?? null,
                    'changed_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to log Maipu history on create', ['SN' => $maipu->SN, 'error' => $e->getMessage()]);
            }
        });

        static::updated(function ($maipu) {
            try {
                $changes = $maipu->getChanges();
                if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                    return; // Skip jika tidak ada perubahan berarti
                }

                $note = 'Perubahan: ' . implode(', ', array_keys(array_diff_key($changes, ['updated_at' => true])));
                if (isset($changes['Status'])) {
                    $note .= ' (Status diubah ke ' . $changes['Status'] . ')';
                }

                MaipuHistory::create([
                    'maipu_sn' => $maipu->SN,
                    'action' => 'updated',
                    'status' => $maipu->Status,
                    'note' => $note,
                    'old_values' => array_intersect_key($maipu->getRawOriginal(), array_flip($maipu->getFillable())),
                    'new_values' => $maipu->only($maipu->getFillable()),
                    'changed_by' => auth()->id() ?? null,
                    'changed_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to log Maipu history on update', ['SN' => $maipu->SN, 'error' => $e->getMessage()]);
            }
        });

        static::deleted(function ($maipu) {
            try {
                MaipuHistory::create([
                    'maipu_sn' => $maipu->SN,
                    'action' => 'deleted',
                    'status' => $maipu->Status,
                    'note' => 'Perangkat Maipu dihapus.',
                    'old_values' => $maipu->only($maipu->getFillable()),
                    'new_values' => null,
                    'changed_by' => auth()->id() ?? null,
                    'changed_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to log Maipu history on delete', ['SN' => $maipu->SN, 'error' => $e->getMessage()]);
            }
        });
    }
}

<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemoteAtmbsi extends Model
{
    protected $table = 'remote_atmbsi';
    protected $primaryKey = 'Site_ID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'Site_ID', 'Site_Name', 'Branch', 'IP_Address', 'Vlan', 'Controller',
        'Link', 'Customer', 'Status', 'Online_Date', 'Keterangan'
    ];

    protected $casts = [
        'Online_Date' => 'date',
    ];

    // Event Handlers untuk History
    protected static function booted()
    {
        static::created(function ($remote) {
            try {
                if (class_exists('App\Models\AlfaLawson\RemoteAtmbsiHistory')) {
                    RemoteAtmbsiHistory::create([
                        'remote_id' => $remote->Site_ID,
                        'action' => 'created',
                        'status' => $remote->Status,
                        'note' => 'Remote ATM BSI baru ditambahkan.',
                        'old_values' => null,
                        'new_values' => array_intersect_key($remote->getAttributes(), array_fill_keys($remote->getFillable(), true)),
                        'changed_by' => auth()->id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to log history on create for RemoteAtmbsi', [
                    'Site_ID' => $remote->Site_ID,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updated(function ($remote) {
            try {
                $changes = $remote->getChanges();
                if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                    return; // Skip if no meaningful changes (only updated_at)
                }

                $note = 'Perubahan: ' . implode(', ', array_keys(array_diff_key($changes, ['updated_at' => true])));
                if (isset($changes['Status'])) {
                    $note .= ' (Status diubah ke ' . $changes['Status'] . ')';
                }

                if (class_exists('App\Models\AlfaLawson\RemoteAtmbsiHistory')) {
                    RemoteAtmbsiHistory::create([
                        'remote_id' => $remote->Site_ID,
                        'action' => 'updated',
                        'status' => $remote->Status,
                        'note' => $note,
                        'old_values' => array_intersect_key($remote->getRawOriginal(), array_flip($remote->getFillable())),
                        'new_values' => array_intersect_key($remote->getAttributes(), array_fill_keys($remote->getFillable(), true)),
                        'changed_by' => auth()->id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to log history on update for RemoteAtmbsi', [
                    'Site_ID' => $remote->Site_ID,
                    'changes' => $changes,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleted(function ($remote) {
            try {
                if (class_exists('App\Models\AlfaLawson\RemoteAtmbsiHistory')) {
                    RemoteAtmbsiHistory::create([
                        'remote_id' => $remote->Site_ID,
                        'action' => 'deleted',
                        'status' => $remote->Status,
                        'note' => 'Remote ATM BSI dihapus.',
                        'old_values' => array_intersect_key($remote->getAttributes(), array_fill_keys($remote->getFillable(), true)),
                        'new_values' => null,
                        'changed_by' => auth()->id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to log history on delete for RemoteAtmbsi', [
                    'Site_ID' => $remote->Site_ID,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RemoteAtmbsiHistory::class, 'remote_id', 'Site_ID')
            ->withCasts(['old_values' => 'array', 'new_values' => 'array']);
    }

    public function getHistoriesAttribute()
    {
        try {
            return $this->hasMany(RemoteAtmbsiHistory::class, 'remote_id')
                ->withCasts(['old_values' => 'array', 'new_values' => 'array'])
                ->where('remote_id', $this->Site_ID)
                ->orderBy('changed_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve histories for RemoteAtmbsi', [
                'Site_ID' => $this->Site_ID,
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    // Relasi ke TableSimcard (banyak simcard untuk satu Site_ID)
    public function simcards(): HasMany
    {
        return $this->hasMany(TableSimcard::class, 'Site_ID', 'Site_ID');
    }

    // Relasi ke TableFo (banyak FO untuk satu Site_ID)
    public function fos(): HasMany
    {
        return $this->hasMany(TableFo::class, 'Site_ID', 'Site_ID');
    }

    
}
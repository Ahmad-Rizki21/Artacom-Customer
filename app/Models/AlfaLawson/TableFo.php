<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableFo extends Model
{
    protected $table = 'table_fo';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'CID',
        'Provider',
        'Register_Name',
        'Site_ID',
        'Status',
    ];

    protected static function booted()
    {
        static::created(function ($fo) {
            try {
                if (class_exists('App\Models\AlfaLawson\TableFoHistory')) {
                    TableFoHistory::create([
                        'fo_id' => $fo->ID, // Use ID instead of CID
                        'action' => 'created',
                        'status' => $fo->Status,
                        'note' => 'Fiber Optic connection baru ditambahkan.',
                        'old_values' => null,
                        'new_values' => array_intersect_key($fo->getAttributes(), array_fill_keys($fo->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on create for TableFo', [
                    'ID' => $fo->ID,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updated(function ($fo) {
            try {
                $changes = $fo->getChanges();
                if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                    return; // Skip if no meaningful changes (only updated_at)
                }

                $note = 'Perubahan: ' . implode(', ', array_keys(array_diff_key($changes, ['updated_at' => true])));
                if (isset($changes['Status'])) {
                    $note .= ' (Status diubah ke ' . $changes['Status'] . ')';
                }

                if (class_exists('App\Models\AlfaLawson\TableFoHistory')) {
                    TableFoHistory::create([
                        'fo_id' => $fo->ID, // Use ID instead of CID
                        'action' => 'updated',
                        'status' => $fo->Status,
                        'note' => $note,
                        'old_values' => array_intersect_key($fo->getRawOriginal(), array_flip($fo->getFillable())),
                        'new_values' => array_intersect_key($fo->getAttributes(), array_fill_keys($fo->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on update for TableFo', [
                    'ID' => $fo->ID,
                    'changes' => $changes,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleted(function ($fo) {
            try {
                if (class_exists('App\Models\AlfaLawson\TableFoHistory')) {
                    TableFoHistory::create([
                        'fo_id' => $fo->ID, // Use ID instead of CID
                        'action' => 'deleted',
                        'status' => $fo->Status,
                        'note' => 'Fiber Optic connection dihapus.',
                        'old_values' => array_intersect_key($fo->getAttributes(), array_fill_keys($fo->getFillable(), true)),
                        'new_values' => null,
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on delete for TableFo', [
                    'ID' => $fo->ID,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function remote(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TableFoHistory::class, 'fo_id', 'ID')
            ->withCasts(['old_values' => 'array', 'new_values' => 'array']);
    }

    public function getHistoriesAttribute()
    {
        try {
            return $this->hasMany(TableFoHistory::class, 'fo_id')
                ->withCasts(['old_values' => 'array', 'new_values' => 'array'])
                ->where('fo_id', $this->ID)
                ->orderBy('changed_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve histories for TableFo', [
                'ID' => $this->ID,
                'error' => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection to prevent breaking the app
        }
    }
}
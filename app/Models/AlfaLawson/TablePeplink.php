<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TablePeplink extends Model
{
    protected $table = 'table_peplink';
    protected $primaryKey = 'SN';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'SN',
        'Model',
        'Kepemilikan',
        'tgl_beli',
        'garansi',
        'Site_ID',
        'Status',
        'Deskripsi',
    ];

    protected $dates = ['tgl_beli'];

    // Mutator to format SN with dashes if not already present
    public function setSNAttribute($value)
    {
        $value = strtoupper($value); // Ubah ke uppercase

        if (strlen($value) === 12 && strpos($value, '-') === false) {
            $value = substr($value, 0, 4) . '-' . substr($value, 4, 4) . '-' . substr($value, 8, 4);
        }

        $this->attributes['SN'] = $value;
    }

    // Accessor to return SN as is
    public function getSNAttribute($value)
    {
        return $value;
    }

    // Override resolveRouteBinding to handle SN with or without dashes
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        $cleanedValue = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));

        // Coba cari dengan SN tanpa dash
        $record = $this->where($field, $cleanedValue)->first();

        if (!$record) {
            // Jika tidak ketemu, coba cari dengan SN dengan dash
            $record = $this->where($field, strtoupper($value))->first();
        }

        return $record ?? abort(404);
    }

    // Ensure route key uses the raw SN value (with dashes if present)
    public function getRouteKey()
    {
        return $this->getRawOriginal('SN') ?? $this->attributes['SN'];
    }

    public function getRouteKeyName()
    {
        return 'SN';
    }

    protected static function booted()
    {
        static::created(function ($peplink) {
            try {
                $sn = $peplink->SN;

                Log::info('Creating TablePeplinkHistory with SN: ' . $sn, [
                    'attributes' => $peplink->getAttributes(),
                ]);

                if (!$sn) {
                    Log::warning('SN is null during create for TablePeplink', [
                        'attributes' => $peplink->getAttributes(),
                    ]);
                    return;
                }

                if (class_exists('App\Models\AlfaLawson\TablePeplinkHistory')) {
                    TablePeplinkHistory::create([
                        'peplink_sn' => $sn,
                        'action' => 'created',
                        'status' => $peplink->Status,
                        'note' => 'Perangkat baru ditambahkan.',
                        'old_values' => null,
                        'new_values' => array_intersect_key($peplink->getAttributes(), array_fill_keys($peplink->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on create for TablePeplink', [
                    'SN' => $sn ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updated(function ($peplink) {
            try {
                $changes = $peplink->getChanges();
                if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                    return; // Skip if no meaningful changes (only updated_at)
                }

                $note = 'Perubahan: ' . implode(', ', array_keys(array_diff_key($changes, ['updated_at' => true])));
                if (isset($changes['Status'])) {
                    $note .= ' (Status diubah ke ' . $changes['Status'] . ')';
                }

                if (class_exists('App\Models\AlfaLawson\TablePeplinkHistory')) {
                    TablePeplinkHistory::create([
                        'peplink_sn' => $peplink->getRawOriginal('SN'),
                        'action' => 'updated',
                        'status' => $peplink->Status,
                        'note' => $note,
                        'old_values' => array_intersect_key($peplink->getRawOriginal(), array_flip($peplink->getFillable())),
                        'new_values' => array_intersect_key($peplink->getAttributes(), array_fill_keys($peplink->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on update for TablePeplink', [
                    'SN' => $peplink->getRawOriginal('SN'),
                    'changes' => $changes,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleted(function ($peplink) {
            try {
                if (class_exists('App\Models\AlfaLawson\TablePeplinkHistory')) {
                    TablePeplinkHistory::create([
                        'peplink_sn' => $peplink->getRawOriginal('SN'),
                        'action' => 'deleted',
                        'status' => $peplink->Status,
                        'note' => 'Perangkat dihapus.',
                        'old_values' => array_intersect_key($peplink->getAttributes(), array_fill_keys($peplink->getFillable(), true)),
                        'new_values' => null,
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on delete for TablePeplink', [
                    'SN' => $peplink->getRawOriginal('SN'),
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function remote()
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }

    public function histories()
    {
        return $this->hasMany(TablePeplinkHistory::class, 'peplink_sn', 'SN')
            ->withCasts(['old_values' => 'array', 'new_values' => 'array']);
    }

    public function getHistoriesAttribute()
    {
        try {
            return $this->hasMany(TablePeplinkHistory::class, 'peplink_sn')
                ->withCasts(['old_values' => 'array', 'new_values' => 'array'])
                ->where('peplink_sn', $this->getRawOriginal('SN'))
                ->orderBy('changed_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve histories for TablePeplink', [
                'SN' => $this->getRawOriginal('SN'),
                'error' => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection to prevent breaking the app
        }
    }
}

<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TableSimcard extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'table_simcard';
    protected $primaryKey = 'Sim_Number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'Sim_Number',
        'Provider',
        'Site_ID',
        'Informasi_Tambahan',
        'SN_Card',
        'Status'
    ];

    protected static function booted()
    {
        static::created(function ($simcard) {
            try {
                if (class_exists('App\Models\AlfaLawson\TableSimcardHistory')) {
                    TableSimcardHistory::create([
                        'sim_number' => $simcard->Sim_Number,
                        'action' => 'created',
                        'status' => $simcard->Status,
                        'note' => 'SIM Card baru ditambahkan.',
                        'old_values' => null,
                        'new_values' => array_intersect_key($simcard->getAttributes(), array_fill_keys($simcard->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on create for TableSimcard', [
                    'Sim_Number' => $simcard->Sim_Number,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updated(function ($simcard) {
            try {
                $changes = $simcard->getChanges();
                if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                    return; // Skip if no meaningful changes (only updated_at)
                }

                $note = 'Perubahan: ' . implode(', ', array_keys(array_diff_key($changes, ['updated_at' => true])));
                if (isset($changes['Status'])) {
                    $note .= ' (Status diubah ke ' . $changes['Status'] . ')';
                }

                if (class_exists('App\Models\AlfaLawson\TableSimcardHistory')) {
                    TableSimcardHistory::create([
                        'sim_number' => $simcard->Sim_Number,
                        'action' => 'updated',
                        'status' => $simcard->Status,
                        'note' => $note,
                        'old_values' => array_intersect_key($simcard->getRawOriginal(), array_flip($simcard->getFillable())),
                        'new_values' => array_intersect_key($simcard->getAttributes(), array_fill_keys($simcard->getFillable(), true)),
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on update for TableSimcard', [
                    'Sim_Number' => $simcard->Sim_Number,
                    'changes' => $changes,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleted(function ($simcard) {
            try {
                if (class_exists('App\Models\AlfaLawson\TableSimcardHistory')) {
                    TableSimcardHistory::create([
                        'sim_number' => $simcard->Sim_Number,
                        'action' => 'deleted',
                        'status' => $simcard->Status,
                        'note' => 'SIM Card dihapus.',
                        'old_values' => array_intersect_key($simcard->getAttributes(), array_fill_keys($simcard->getFillable(), true)),
                        'new_values' => null,
                        'changed_by' => Auth::id() ?? null,
                        'changed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log history on delete for TableSimcard', [
                    'Sim_Number' => $simcard->Sim_Number,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function remoteAtmbsi()
    {
        return $this->belongsTo(RemoteAtmbsi::class, 'Site_ID', 'Site_ID');
    }
    public function remote()
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID')->withDefault();
    }

    public function histories()
    {
        return $this->hasMany(TableSimcardHistory::class, 'sim_number', 'Sim_Number')
            ->withCasts(['old_values' => 'array', 'new_values' => 'array']);
    }

    public function getHistoriesAttribute()
    {
        try {
            return $this->hasMany(TableSimcardHistory::class, 'sim_number')
                ->withCasts(['old_values' => 'array', 'new_values' => 'array'])
                ->where('sim_number', $this->Sim_Number)
                ->orderBy('changed_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve histories for TableSimcard', [
                'Sim_Number' => $this->Sim_Number,
                'error' => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection to prevent breaking the app
        }
    }
}
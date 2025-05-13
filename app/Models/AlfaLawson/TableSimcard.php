<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableSimcard extends Model
{
    use HasFactory;

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

    public function remote(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }
}
<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableSimcard extends Model
{
    use HasFactory;

    protected $table = 'table_simcard';

    // Specify the custom primary key
    protected $primaryKey = 'Sim_Number';

    // If Sim_Number is not an auto-incrementing integer, set this to false
    public $incrementing = false;

    // Specify the key type if it's not an integer (e.g., string for Sim_Number)
    protected $keyType = 'string';

    protected $fillable = [
        'Sim_Number',
        'Provider',
        'Site_ID',
        'Informasi_Tambahan',
        'SN_Card',
        'Status',
    ];

    public function remote(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }
}
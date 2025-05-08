<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // Relasi dengan TableRemote
    public function remote(): BelongsTo
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }
}
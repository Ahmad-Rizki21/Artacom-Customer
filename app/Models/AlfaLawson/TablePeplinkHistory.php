<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TablePeplinkHistory extends Model
{
    protected $table = 'table_peplink_histories';

    protected $fillable = [
        'peplink_sn',
        'action',
        'status',
        'note',
        'old_values',
        'new_values',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime',
    ];

    // Allow null for changed_by if no user is authenticated
    protected $attributes = [
        'changed_by' => null,
    ];

    public function peplink()
    {
        return $this->belongsTo(TablePeplink::class, 'peplink_sn', 'SN');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
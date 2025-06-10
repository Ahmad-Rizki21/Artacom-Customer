<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TableFoHistory extends Model
{
    protected $table = 'table_fo_histories';

    protected $fillable = [
        'fo_id',
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

    protected $attributes = [
        'changed_by' => null,
    ];

    public function fo()
    {
        return $this->belongsTo(TableFo::class, 'fo_id', 'ID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
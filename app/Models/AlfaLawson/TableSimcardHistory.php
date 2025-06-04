<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TableSimcardHistory extends Model
{
    protected $table = 'table_simcard_histories';

    protected $fillable = [
        'sim_number',
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

    public function simcard()
    {
        return $this->belongsTo(TableSimcard::class, 'sim_number', 'Sim_Number');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
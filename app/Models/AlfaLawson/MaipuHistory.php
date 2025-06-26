<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use App\Models\AlfaLawson\Maipu;

class MaipuHistory extends Model
{
    protected $table = 'table_maipu_histories';

    public $timestamps = false;

    protected $fillable = [
        'maipu_sn',
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

    public function maipu()
    {
        return $this->belongsTo(Maipu::class, 'maipu_sn', 'SN');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}

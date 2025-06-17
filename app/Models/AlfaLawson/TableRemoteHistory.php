<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TableRemoteHistory extends Model
{
    protected $table = 'table_remote_histories';

    protected $fillable = [
        'remote_id',      // foreign key ke TableRemote (Site_ID)
        'action',         // created, updated, deleted
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

    public $timestamps = false;

    public function remote()
    {
        return $this->belongsTo(TableRemote::class, 'remote_id', 'Site_ID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}

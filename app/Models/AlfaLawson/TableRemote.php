<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableRemote extends Model
{
    protected $table = 'table_remote';
    protected $primaryKey = 'Site_ID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'Site_ID',
        'Nama_Toko',
        'DC',
        'IP_Address',
        'Vlan',
        'Controller',
        'Customer',
        'Online_Date',
        'Link',
        'Status',
        'Keterangan',
    ];

    protected $casts = [
        'Online_Date' => 'date',
    ];

    // Relationships
    // public function simcards(): HasMany
    // {
    //     return $this->hasMany(TableSimcard::class, 'Site_ID', 'Site_ID');
    // }

    // public function fo(): HasMany
    // {
    //     return $this->hasMany(TableFo::class, 'Site_ID', 'Site_ID');
    // }

    // public function peplink(): HasMany
    // {
    //     return $this->hasMany(TablePeplink::class, 'Site_ID', 'Site_ID');
    // }
}
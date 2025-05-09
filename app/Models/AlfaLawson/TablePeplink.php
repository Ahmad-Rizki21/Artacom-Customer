<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TablePeplink extends Model
{
    protected $table = 'table_peplink';
    protected $primaryKey = 'SN';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'SN', 
        'Model', 
        'Kepemilikan', 
        'tgl_beli', 
        'garansi', 
        'Site_ID', 
        'Status',
    ];
    protected $dates = ['tgl_beli'];

    public function remote()
    {
        return $this->belongsTo(TableRemote::class, 'Site_ID', 'Site_ID');
    }
}
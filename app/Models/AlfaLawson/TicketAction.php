<?php

namespace App\Models\AlfaLawson;

use Illuminate\Database\Eloquent\Model;

class TicketAction extends Model
{
    protected $table = 'ticket_actions';

    protected $fillable = ['No_Ticket', 'Action_Taken', 'Action_Time', 'Action_By', 'Action_Level'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'No_Ticket', 'No_Ticket');
    }

    public function actionBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'Action_By', 'id');
    }
}
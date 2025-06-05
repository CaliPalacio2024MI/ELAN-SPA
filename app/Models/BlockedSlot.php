<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    protected $fillable = [
        'spa_id', 
        'anfitrion_id', 
        'fecha', 
        'hora', 
        'duracion', 
        'motivo'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabina extends Model
{
    use HasFactory;

    protected $fillable = [
        'spa_id',
        'nombre',
        'clase',
        'clases_actividad',
        'activo',
    ];


    public function spa()
    {
        return $this->belongsTo(Spa::class, 'spa_id');
    }

    protected $casts = [
        'clases_actividad' => 'array',
    ];

    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'cabina_id');
    }
}

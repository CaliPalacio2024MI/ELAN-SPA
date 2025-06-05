<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'spa_id',
        'codigo',
        'nombre',
        'descripcion',
        'clase',
        'duracion',
        'precio',
        'color',     
        'activo',
    ];

    public function spa()
    {
        return $this->belongsTo(Spa::class, 'spa_id');
    }

    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'experiencia_id');
    }
}

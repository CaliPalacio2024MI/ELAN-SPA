<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'nombre_unidad',
        'color_unidad',
        'logo_superior',
        'logo_unidad',
        'logo_inferior',
        'spa_id',
    ];

    /**
     * Obtiene el registro de Spa al que pertenece esta Unidad.
     */
    public function spa()
    {
        return $this->belongsTo(Spa::class, 'spa_id');
    }
}

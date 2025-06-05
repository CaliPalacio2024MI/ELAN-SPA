<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GimnasioRegistroMenor extends Model
{
    use HasFactory;

    protected $table = 'gimnasio_registros_menores';
    protected $fillable = [
        'fk_id_hotel',
        'origen_registro',
        'nombre_menor',
        'edad',
        'nombre_tutor',
        'telefono_tutor',
        'firma_tutor',
        'nombre_anfitrion',
        'firma_anfitrion',
    ];

    // Relación hotel
    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GimnasioRegistroAdulto extends Model
{
    use HasFactory;

    protected $table = 'gimnasio_registros_adultos';
    protected $fillable = [
        'fk_id_hotel',
        'origen_registro',
        'nombre_huesped',
        'firma_huesped',
    ];

    // Relación hotel
    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GimnasioQrcode extends Model
{
    use HasFactory;

    protected $table = 'gimnasio_qrcode';
    
    protected $fillable = [
        'token',
        'fk_id_hotel',
        'contexto',
        'fecha_expiracion',
        'activo',
    ];

    // Relación hotel
    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}
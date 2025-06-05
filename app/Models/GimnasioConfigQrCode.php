<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GimnasioConfigQrCode extends Model
{
    use HasFactory;

    protected $table = 'gimnasio_config_qr_code';
    protected $fillable = [
        'fk_id_hotel',
        'tiempo_renovacion_qr',
        'tiempo_validez_qr',
    ];

    // Relación hotel
    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}
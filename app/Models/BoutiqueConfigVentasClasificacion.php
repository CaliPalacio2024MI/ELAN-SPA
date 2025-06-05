<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoutiqueConfigVentasClasificacion extends Model
{
    protected $table = 'boutique_config_ventas_clasificacion';

    protected $fillable = [
        'nombre',
        'minimo_ventas',
        'fk_id_hotel',
    ];

    public $timestamps = true;

    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'reservacion_id',
        'cliente_id',
        'tipo_persona',
        'razon_social',
        'rfc',
        'direccion_fiscal',
        'correo_factura',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function reservacion()
    {
        return $this->belongsTo(Reservation::class, 'reservacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }
}

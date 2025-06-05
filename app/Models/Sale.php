<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'grupo_reserva_id',
        'cliente_id',
        'spa_id',
        'reservacion_id',
        'forma_pago',
        'referencia_pago',
        'subtotal',
        'impuestos',
        'propina',
        'total',
        'cobrado',
    ];

    public function grupoReserva()
    {
        return $this->belongsTo(GrupoReserva::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class);
    }

    public function spa()
    {
        return $this->belongsTo(Spa::class);
    }

    public function reservacion()
{
    return $this->belongsTo(Reservation::class, 'reservacion_id');
}


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoReserva extends Model
{
    use HasFactory;

    protected $table = 'grupo_reservas';

    protected $fillable = [
        'cliente_id',
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'grupo_reserva_id');
    }

        public function sale()
{
    return $this->hasOne(Sale::class, 'grupo_reserva_id');
}
}

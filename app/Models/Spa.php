<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
    ];

    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'spa_id');
    }

    public function ventas()
    {
        return $this->hasMany(Sale::class, 'spa_id');
    }

    public function clientes()
    {
        return $this->hasMany(Client::class, 'spa_id');
    }

    public function experiencias()
    {
        return $this->hasMany(Experience::class, 'spa_id');
    }

    public function anfitriones()
    {
        return $this->hasMany(Anfitrion::class, 'spa_id');
    }

    public function cabinas()
    {
        return $this->hasMany(Cabina::class, 'spa_id');
    }
}

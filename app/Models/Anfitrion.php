<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Anfitrion extends Authenticatable
{
    use HasFactory;

    protected $table = 'anfitriones';

    protected $fillable = [
        'spa_id',
        'RFC',
        'apellido_paterno',
        'apellido_materno',
        'nombre_usuario',
        'password',
        'rol',
        'accesos',
        'activo',
    ];

    protected $casts = [
        'accesos' => 'array', // Permite acceso como array desde JSON
    ];

    // Relación al spa principal del anfitrión
    public function spa()
    {
        return $this->belongsTo(Spa::class, 'spa_id');
    }

    // Relación uno a uno con datos operativos del anfitrión
    public function operativo()
    {
        return $this->hasOne(AnfitrionOperativo::class);
    }

    // Reservaciones asociadas al anfitrión
    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'anfitrion_id');
    }

    // Ventas asociadas al anfitrión
    public function ventas()
    {
        return $this->hasMany(Sale::class, 'anfitrion_id');
    }

    // Horario asignado al anfitrión
    public function horario()
    {
        return $this->hasOne(HorarioAnfitrion::class);
    }
}

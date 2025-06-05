<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'correo',
        'telefono',
        'tipo_visita',
    ];

    public function reservaciones()
    {
        return $this->hasMany(Reservation::class, 'cliente_id');
    }

    public function evaluationForms()
    {
        return $this->hasMany(EvaluationForm::class, 'cliente_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

}

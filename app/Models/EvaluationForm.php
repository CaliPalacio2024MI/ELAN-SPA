<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'cliente_id',
        'preguntas_respuestas',
        'observaciones',
        'firma_paciente_url',
        'firma_tutor_url',
        'firma_doctor_url',
        'firma_testigo1_url',
        'firma_testigo2_url',
        'firma_padre_url',
        'firma_terapeuta_url',
    ];

    protected $casts = [
        'preguntas_respuestas' => 'array',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }
}

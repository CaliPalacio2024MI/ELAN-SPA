<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnfitrionOperativo extends Model
{
    use HasFactory;

    protected $table = 'anfitrion_operativo';

    protected $fillable = [
        'anfitrion_id',
        'departamento',
        'clases_actividad',
    ];

    protected $casts = [
        'clases_actividad' => 'array',
    ];

    public function anfitrion()
    {
        return $this->belongsTo(Anfitrion::class);
    }
}

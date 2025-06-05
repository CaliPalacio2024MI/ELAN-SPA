<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioAnfitrion extends Model
{
    use HasFactory;

    protected $table = 'horarios_anfitrion';

    protected $fillable = [
        'anfitrion_id',
        'horarios'
    ];

    protected $casts = [
        'horarios' => 'array'
    ];

    public function anfitrion()
    {
        return $this->belongsTo(Anfitrion::class);
    }
}

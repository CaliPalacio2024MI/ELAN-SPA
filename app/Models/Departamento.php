<?php

namespace App\Models;

use App\Models\Departamento;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = [
        'spa_id',
        'nombre',
        'activo',
    ];

    public function spa()
    {
        return $this->belongsTo(Spa::class);
    }
}

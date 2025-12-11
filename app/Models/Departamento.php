<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Spa;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
        'spa_id',
        'activo',
        'slug',
    ];

    public function spa()
    {
        return $this->belongsTo(Spa::class);
    }
}

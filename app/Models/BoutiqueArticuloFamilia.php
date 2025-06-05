<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueArticuloFamilia extends Model
{
    use HasFactory;

    protected $table = 'boutique_articulos_familias';
    
    protected $fillable = [
        'nombre',
        'fk_id_hotel',
    ];

    /**
     * Obtener los artículos asociados a esta familia
     */
    public function articulos()
    {
        return $this->hasMany(BoutiqueArticulo::class, 'fk_id_familia');
    }

    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
}
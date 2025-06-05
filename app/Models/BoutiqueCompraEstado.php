<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BoutiqueCompraEstado extends Model
{
    use HasFactory;
    
    protected $table = 'boutique_compras_estados';
    
    protected $fillable = [
        'estado'
    ];
    
    public function compras()
    {
        return $this->hasMany(BoutiqueCompra::class, 'fk_id_estado');
    }
}

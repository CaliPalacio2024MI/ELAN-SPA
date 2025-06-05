<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueInventario extends Model
{
    use HasFactory;
    
    protected $table = 'boutique_inventario';
    
    protected $fillable = [
        'fk_id_compra',
        'cantidad_actual'
    ];
    
    /**
     * Obtener la compra asociada a este inventario
     */
    public function compra()
    {
        return $this->belongsTo(BoutiqueCompra::class, 'fk_id_compra');
    }
}

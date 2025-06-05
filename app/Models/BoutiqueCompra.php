<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueCompra extends Model
{
    use HasFactory;
    
    protected $table = 'boutique_compras';
    
    protected $fillable = [
        'tipo_compra',
        'folio_orden_compra',
        'folio_factura',
        'fk_id_articulo',
        'costo_proveedor_unidad',
        'cantidad_recibida',
        'fecha_caducidad',
    ];

    /**
     * Obtener el artículo asociado a esta compra
     */
    public function articulo()
    {
        return $this->belongsTo(BoutiqueArticulo::class, 'fk_id_articulo');
    }
    
    /**
     * Obtener el inventario asociado a esta compra
     */
    public function inventarios()
    {
        return $this->hasMany(BoutiqueInventario::class, 'fk_id_compra');
    }
    
    public function ventasDetalles()
    {
        return $this->hasMany(BoutiqueVentaDetalle::class, 'fk_id_compra');
    }
}

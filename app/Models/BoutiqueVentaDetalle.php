<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueVentaDetalle extends Model
{
    use HasFactory;
    
    protected $table = 'boutique_ventas_detalles';
    public $timestamps = false;
    
    protected $fillable = [
        'fk_id_folio',
        'fk_id_compra',
        'fk_id_anfitrion',
        'cantidad',
        'descuento',
        'subtotal',
        'observaciones'
    ];

    // Este es el accessor para el precio unitario
    protected function getPrecioUnitarioAttribute()
    {
        // Si no hay cantidad, evitamos división por cero
        if ($this->cantidad == 0) {
            return 0;
        }
        
        // Calculamos el precio unitario basado en subtotal y cantidad
        // Si hay descuento, lo consideramos en el cálculo
        // descuento es un porcentaje (por ejemplo, 10 para 10%)
        $precioTotalSinDescuento = $this->subtotal / (1 - ($this->descuento / 100));
        return $precioTotalSinDescuento / $this->cantidad;
    }
    
    public function venta()
    {
        return $this->belongsTo(BoutiqueVenta::class, 'fk_id_folio');
    }
    
    public function compra()
    {
        return $this->belongsTo(BoutiqueCompra::class, 'fk_id_compra');
    }
    
    public function anfitrion()
    {
        return $this->belongsTo(Anfitrion::class, 'fk_id_anfitrion');
    }
}

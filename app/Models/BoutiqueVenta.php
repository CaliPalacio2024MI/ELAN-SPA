<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueVenta extends Model
{
    use HasFactory;
    
    protected $table = 'boutique_ventas';
    
    
    protected $fillable = [
        'fk_id_hotel',
        'fk_id_forma_pago',
        'folio_venta',
        'referencia_pago',
        'fecha_venta',
        'hora_venta',
    ];

    // Relación directa con la forma de pago
    public function formaPago()
    {
        return $this->belongsTo(BoutiqueFormasPago::class, 'fk_id_forma_pago');
    }
    
    /**
     * Obtener el hotel asociado a esta venta
     */
    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }
    
    /**
     * Obtener los detalles de esta venta
     */
    public function detalles()
    {
        return $this->hasMany(BoutiqueVentaDetalle::class, 'fk_id_folio');
    }
}

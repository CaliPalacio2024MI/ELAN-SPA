<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoutiqueArticulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'boutique_articulos';

    protected $fillable = [
        'numero_auxiliar',
        'nombre_articulo',
        'descripcion',
        'precio_publico_unidad',
        'fk_id_familia',
        'fk_id_hotel', // Nuevo campo
    ];

    /**
     * Obtener la familia a la que pertenece este artículo
     */
    public function familia()
    {
        return $this->belongsTo(BoutiqueArticuloFamilia::class, 'fk_id_familia');
    }

    /**
     * Obtener las compras asociadas a este artículo
     */
    public function compras()
    {
        return $this->hasMany(BoutiqueCompra::class, 'fk_id_articulo');
    }

    public function hotel()
    {
        return $this->belongsTo(Spa::class, 'fk_id_hotel');
    }

    /**
     * Calcula el tipo de movimiento del artículo por hotel basado en ventas de los últimos 6 meses
     */
    public function getTiposMovimientoPorHotelAttribute()
    {
        $hoteles = [1, 2, 3]; // IDs de los tres hoteles
        $resultados = [];

        // Carga la configuración de clasificación y ordena por mínimo descendente
        $clasificaciones = BoutiqueConfigVentasClasificacion::orderByDesc('minimo_ventas')->get();

        foreach ($hoteles as $idHotel) {
            \Log::debug("Calculando tipo de movimiento para artículo {$this->id} en hotel {$idHotel}");

            $ventas = BoutiqueVentaDetalle::whereHas('venta', function ($query) use ($idHotel) {
                $query->where('fk_id_hotel', $idHotel)
                    ->where('fecha_venta', '>=', now()->subMonths(6));
            })
                ->whereHas('compra', function ($query) {
                    $query->where('fk_id_articulo', $this->id);
                })
                ->sum('cantidad');

            \Log::debug("Ventas encontradas: {$ventas}");

            // Determinar el tipo de movimiento basado en la configuración
            $tipo = 'Desconocido'; // Por si no coincide con ninguna
            foreach ($clasificaciones as $clasificacion) {
                if ($ventas >= $clasificacion->minimo_ventas) {
                    $tipo = $clasificacion->nombre;
                    break;
                }
            }

            $resultados["$idHotel"] = $tipo;
        }

        return $resultados;
    }
}

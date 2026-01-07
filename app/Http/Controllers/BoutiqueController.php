<?php

namespace App\Http\Controllers;

use App\Models\BoutiqueInventario;
use App\Models\BoutiqueArticulo;
use App\Models\BoutiqueCompra;
use App\Models\BoutiqueVenta;
use Illuminate\Support\Facades\Auth;
use App\Models\BoutiqueVentaDetalle;
use App\Models\Anfitrion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use Carbon\Carbon;

class BoutiqueController extends Controller
{
    /* ----- Vistas ----- */
    public function venta()
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        $anfitriones = Anfitrion::where('spa_id', $hotelId)
            ->select('id', 'apellido_paterno', 'apellido_materno', 'nombre_usuario', 'RFC')
            ->get();

        $settings = [
            'searchEngine' => 'loose',
            'showAllOnFocus' => true,
            'ignoreAccents' => true
        ];

        // Consulta corregida según tu nueva estructura de tablas
        $compra = DB::table('boutique_inventario')
            ->join('boutique_compras', 'boutique_inventario.fk_id_compra', '=', 'boutique_compras.id')
            ->join('boutique_articulos', 'boutique_compras.fk_id_articulo', '=', 'boutique_articulos.id')
            ->where('boutique_articulos.fk_id_hotel', $hotelId) // Cambiado: ahora el hotel está en la tabla de artículos
            ->where('boutique_inventario.cantidad_actual', '>', 0) // Solo artículos con stock
            ->orderBy('boutique_compras.fecha_caducidad', 'asc') // Ordenar por caducidad más próxima primero (FIFO)
            ->select(
                'boutique_articulos.numero_auxiliar',
                'boutique_articulos.nombre_articulo',
                'boutique_articulos.descripcion',
                'boutique_articulos.precio_publico_unidad', // Cambiado: ahora el precio está en la tabla de artículos
                'boutique_compras.fecha_caducidad',
                'boutique_inventario.cantidad_actual',
                'boutique_compras.id as compra_id' // Necesario para las ventas
            )
            ->get();

        $articulos = $compra->map(function ($item) {
            return [
                'value' => str_pad($item->numero_auxiliar, 10, '0', STR_PAD_LEFT) . ' ' . $item->nombre_articulo,
                'key' => $item->numero_auxiliar
            ];
        })->unique('key')->values();

        $formas_pago = DB::table('boutique_formas_pago')->get()->map(function ($item) {
            return [
                'value' => $item->nombre,
                'key' => $item->id
            ];
        });

        // Generar folio con las primeras 3 letras del hotel
        $prefijoHotel = strtoupper(substr($hotel->nombre, 0, 3));
        $year = Carbon::now()->year;

        // Buscar el último folio del hotel y año actual
        $ultimoFolio = DB::table('boutique_ventas')
            ->where('folio_venta', 'like', "$prefijoHotel-VEN-$year-%")
            ->orderByDesc('id')
            ->value('folio_venta');

        // Obtener el último número
        if ($ultimoFolio) {
            $numero = (int) substr($ultimoFolio, -5); // extrae los últimos 5 dígitos
            $nuevoNumero = str_pad($numero + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nuevoNumero = '00001'; // primer folio del año para este hotel
        }

        // Generar nuevo folio: PAL-VEN-2025-00001
        $folioVenta = "$prefijoHotel-VEN-$year-$nuevoNumero";

        return view('boutique.venta', compact(
            'anfitriones',
            'settings',
            'articulos',
            'compra',
            'formas_pago',
            'folioVenta',
            'hotel' // Agregado: para mostrar información del hotel en la vista
        ));
    }

    public function inventario(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Consulta para la tabla de Inventario (todos los compras)
        $compras = DB::table('boutique_compras')
            ->join('boutique_inventario', 'boutique_compras.id', '=', 'boutique_inventario.fk_id_compra')
            ->join('boutique_articulos', 'boutique_compras.fk_id_articulo', '=', 'boutique_articulos.id')
            ->select(
                'boutique_compras.id as compra_id',
                'boutique_compras.costo_proveedor_unidad',
                'boutique_articulos.precio_publico_unidad',
                'boutique_compras.cantidad_recibida',
                'boutique_compras.fecha_caducidad',
                'boutique_articulos.fk_id_hotel',
                'boutique_inventario.cantidad_actual',
                'boutique_articulos.nombre_articulo',
                'boutique_articulos.numero_auxiliar',
            )
            ->where('boutique_articulos.fk_id_hotel', $hotelId) // Filtrar por hotel desde boutique_articulos
            ->where('boutique_inventario.cantidad_actual', '>', 0)
            ->get();

        $articulos = DB::table('boutique_articulos')
            ->leftJoin('boutique_compras', 'boutique_articulos.id', '=', 'boutique_compras.fk_id_articulo')
            ->leftJoin('boutique_inventario', 'boutique_compras.id', '=', 'boutique_inventario.fk_id_compra')
            ->join('boutique_articulos_familias', 'boutique_articulos.fk_id_familia', '=', 'boutique_articulos_familias.id')
            ->select(
                'boutique_articulos.id',
                'boutique_articulos.nombre_articulo',
                'boutique_articulos.numero_auxiliar',
                'boutique_articulos.precio_publico_unidad',
                'boutique_articulos_familias.nombre as familia',
                DB::raw('COALESCE(SUM(boutique_inventario.cantidad_actual), 0) as total_cantidad')
            )
            ->where('boutique_articulos.fk_id_hotel', $hotelId)
            ->groupBy(
                'boutique_articulos.id',
                'boutique_articulos.nombre_articulo',
                'boutique_articulos.numero_auxiliar',
                'boutique_articulos.precio_publico_unidad',
                'boutique_articulos_familias.nombre'
            )
            ->havingRaw('COALESCE(SUM(boutique_inventario.cantidad_actual), 0) > 0')
            ->get();

        $settings = [
            'searchEngine' => 'loose',
            'showAllOnFocus' => true,
            'ignoreAccents' => true
        ];

        // Artículos disponibles solo del hotel actual
        $articulos_disponibles = DB::table('boutique_articulos')
            ->select('numero_auxiliar', 'nombre_articulo')
            ->where('fk_id_hotel', $hotelId) // Solo artículos del hotel actual
            ->get()
            ->map(function ($item) {
                return [
                    'value' => str_pad($item->numero_auxiliar, 10, '0', STR_PAD_LEFT) . ' ' . $item->nombre_articulo,
                    'key' => $item->numero_auxiliar
                ];
            });

        // Familias disponibles solo del hotel actual
        $familias_disponibles = DB::table('boutique_articulos_familias')
            ->where('fk_id_hotel', $hotelId) // Solo familias del hotel actual
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->nombre,
                    'key' => $item->id
                ];
            });

        $tipo_compra = [
            ['key' => 'normal', 'value' => 'Normal'],
            ['key' => 'directa', 'value' => 'Directa']
        ];

        // Retornar la vista con ambos conjuntos de datos
        return view('boutique.inventario', compact('compras', 'articulos', 'articulos_disponibles', 'familias_disponibles', 'settings', 'hotelName', 'tipo_compra'));
    }

    public function reporteo(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener fechas del request o establecer el último mes por defecto
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $hoy = Carbon::today();

        // Convertir a instancias de Carbon (seguras)
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin) : null;

        // Validar fechas proporcionadas
        if ($inicio && $fin) {
            if ($inicio->gt($fin)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
            }

            if ($inicio->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede estar en el futuro.']);
            }

            if ($fin->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_fin' => 'La fecha de fin no puede estar en el futuro.']);
            }
        }

        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now()->format('Y-m-d');
            $fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }

        $clasificacion_actual = strtolower($request->input('select_tipo_compra') ?? 'todos');
        $familia_actual = strtolower($request->input('select_familia') ?? 'todos');

        // Obtener clasificaciones
        $clasificacionesDB = Cache::remember("clasificaciones_boutique_hotel_$hotelId", 3600, function () use ($hotelId) {
            return \App\Models\BoutiqueConfigVentasClasificacion::where('fk_id_hotel', $hotelId)
                ->orderByDesc('minimo_ventas')
                ->get();
        });

        $clasificaciones_opciones = [
            ['key' => 'todos', 'value' => 'Todos']
        ];

        foreach ($clasificacionesDB as $clasificacion) {
            $clasificaciones_opciones[] = [
                'key' => strtolower($clasificacion->nombre),
                'value' => $clasificacion->nombre
            ];
        }

        // Obtener familias
        $familiasDB = Cache::remember("familias_boutique_hotel_$hotelId", 3600, function () use ($hotelId) {
            return DB::table('boutique_articulos_familias')
                ->where('fk_id_hotel', $hotelId)
                ->orderBy('nombre')
                ->get();
        });

        $familias_opciones = [
            ['key' => 'todos', 'value' => 'Todos']
        ];

        foreach ($familiasDB as $familia) {
            $familias_opciones[] = [
                'key' => strtolower($familia->nombre),
                'value' => $familia->nombre
            ];
        }

        // CONSULTA PRINCIPAL: Obtener artículos con sus datos básicos e inventario
        $query = DB::table('boutique_articulos as a')
            ->join('boutique_articulos_familias as f', 'a.fk_id_familia', '=', 'f.id')
            ->leftJoin('boutique_compras as c', 'a.id', '=', 'c.fk_id_articulo')
            ->leftJoin('boutique_inventario as i', 'c.id', '=', 'i.fk_id_compra')
            ->select(
                'a.id',
                'a.nombre_articulo',
                'a.numero_auxiliar',
                'a.precio_publico_unidad',
                'f.nombre as familia',
                DB::raw('COALESCE(SUM(i.cantidad_actual), 0) as total_cantidad')
            )
            ->where('a.fk_id_hotel', $hotelId)
            ->where('f.fk_id_hotel', $hotelId);

        // Aplicar filtro por familia si no es "todos"
        if ($familia_actual !== 'todos') {
            $query->where(DB::raw('LOWER(f.nombre)'), $familia_actual);
        }

        $articulosRaw = $query
            ->groupBy('a.id', 'a.nombre_articulo', 'a.numero_auxiliar', 'a.precio_publico_unidad', 'f.nombre')
            ->orderBy('f.nombre')
            ->orderBy('a.nombre_articulo')
            ->get();

        // OPTIMIZACIÓN: Cargar modelos en lote para evitar N+1
        $articuloIds = $articulosRaw->pluck('id')->toArray();
        $modelosArticulos = BoutiqueArticulo::whereIn('id', $articuloIds)->get()->keyBy('id');

        // Obtener datos de ventas para el período especificado
        $ventasPorArticulo = DB::table('boutique_ventas_detalles as vd')
            ->join('boutique_ventas as v', 'vd.fk_id_folio', '=', 'v.id')
            ->join('boutique_compras as c', 'vd.fk_id_compra', '=', 'c.id')
            ->select(
                'c.fk_id_articulo',
                DB::raw('SUM(vd.cantidad) as articulos_vendidos'),
                DB::raw('SUM(vd.subtotal) as ingreso_total'),
                DB::raw('SUM(vd.cantidad * c.costo_proveedor_unidad) as costo_total')
            )
            ->where('v.fk_id_hotel', $hotelId)
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            ->whereIn('c.fk_id_articulo', $articuloIds)
            ->groupBy('c.fk_id_articulo')
            ->get()
            ->keyBy('fk_id_articulo');

        // Agregar después de obtener ventasPorArticulo
        $ventasTotales = DB::table('boutique_ventas as v')
            ->join('boutique_ventas_detalles as vd', 'v.id', '=', 'vd.fk_id_folio')
            ->join('boutique_compras as c', 'vd.fk_id_compra', '=', 'c.id')
            ->where('v.fk_id_hotel', $hotelId)
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            ->whereIn('c.fk_id_articulo', $articuloIds)
            ->distinct('v.id')
            ->count();

        // Obtener datos de caducidad por artículo
        $caducidadesPorArticulo = DB::table('boutique_compras as c')
            ->join('boutique_inventario as i', 'c.id', '=', 'i.fk_id_compra')
            ->select(
                'c.fk_id_articulo',
                'c.id as id_compra',
                'c.fecha_caducidad',
                'i.cantidad_actual',
                DB::raw('CASE 
                WHEN c.fecha_caducidad IS NULL THEN 999999
                WHEN c.fecha_caducidad < CURDATE() THEN -1
                ELSE DATEDIFF(c.fecha_caducidad, CURDATE())
            END as dias_restantes')
            )
            ->whereIn('c.fk_id_articulo', $articuloIds)
            ->where('i.cantidad_actual', '>', 0)
            ->orderBy('c.fk_id_articulo')
            ->orderBy('dias_restantes')
            ->get()
            ->groupBy('fk_id_articulo');

        // Convertir a colección y agregar campos calculados
        $articulos = collect($articulosRaw)->map(function ($articuloRaw) use ($hotelId, $modelosArticulos, $ventasPorArticulo, $caducidadesPorArticulo) {
            $articulo = $modelosArticulos->get($articuloRaw->id);
            $ventaData = $ventasPorArticulo->get($articuloRaw->id);
            $caducidadData = $caducidadesPorArticulo->get($articuloRaw->id, collect());

            // Clasificación actual
            $clasificacionActual = $articulo->tipos_movimiento_por_hotel[$hotelId] ?? 'Desconocido';
            $articuloRaw->clasificacion_actual = $clasificacionActual;

            // Datos de ventas
            $articuloRaw->articulos_vendidos = $ventaData->articulos_vendidos ?? 0;
            $articuloRaw->ingreso_total = $ventaData->ingreso_total ?? 0;
            $articuloRaw->costo_total = $ventaData->costo_total ?? 0;
            $articuloRaw->utilidad_bruta = $articuloRaw->ingreso_total - $articuloRaw->costo_total;

            // Valor de inventario (cantidad actual * precio público)
            $articuloRaw->valor_inventario = $articuloRaw->total_cantidad * ($articuloRaw->precio_publico_unidad ?? 0);

            // Datos de caducidad
            if ($caducidadData->isNotEmpty()) {
                $caducidadMasCercana = $caducidadData->first();
                $articuloRaw->dias_restantes_minimo = $caducidadMasCercana->dias_restantes;
                $articuloRaw->fecha_caducidad_cercana = $caducidadMasCercana->fecha_caducidad;

                // Preparar tooltip con todas las compras y sus caducidades
                $tooltipData = $caducidadData->map(function ($compra) {
                    if ($compra->fecha_caducidad === null) {
                        return "Compra #{$compra->id_compra}: No caduca ({$compra->cantidad_actual} unidades)";
                    } elseif ($compra->dias_restantes == -1) {
                        return "Compra #{$compra->id_compra}: Caducado ({$compra->cantidad_actual} unidades)";
                    } else {
                        return "Compra #{$compra->id_compra}: {$compra->dias_restantes} días ({$compra->cantidad_actual} unidades)";
                    }
                })->toArray();

                $articuloRaw->tooltip_compras = implode('<br>', $tooltipData);

                // Color del indicador de caducidad
                if ($caducidadMasCercana->dias_restantes == -1) {
                    $articuloRaw->color_caducidad = '#ff4444'; // Rojo - caducado
                } elseif ($caducidadMasCercana->dias_restantes <= 7) {
                    $articuloRaw->color_caducidad = '#ff8c00'; // Naranja - 0-7 días
                } elseif ($caducidadMasCercana->dias_restantes <= 30) {
                    $articuloRaw->color_caducidad = '#ffd700'; // Amarillo - menos de 1 mes
                } elseif ($caducidadMasCercana->dias_restantes == 999999) {
                    $articuloRaw->color_caducidad = '#ffffff'; // Blanco - no caduca
                } else {
                    $articuloRaw->color_caducidad = '#90ee90'; // Verde - más de 1 mes
                }

                // Texto a mostrar
                if ($caducidadMasCercana->dias_restantes == 999999) {
                    $articuloRaw->texto_caducidad = 'No caduca';
                } elseif ($caducidadMasCercana->dias_restantes == -1) {
                    $articuloRaw->texto_caducidad = 'Caducado';
                } else {
                    $articuloRaw->texto_caducidad = $caducidadMasCercana->dias_restantes . ' días';
                }
            } else {
                // Sin inventario
                $articuloRaw->dias_restantes_minimo = 999999;
                $articuloRaw->fecha_caducidad_cercana = null;
                $articuloRaw->tooltip_compras = 'Sin inventario disponible';
                $articuloRaw->color_caducidad = '#cccccc'; // Gris
                $articuloRaw->texto_caducidad = 'Sin inventario';
            }

            return $articuloRaw;
        });

        // Aplicar filtro por clasificación si no es "todos"
        if ($clasificacion_actual !== 'todos') {
            $articulos = $articulos->filter(function ($a) use ($clasificacion_actual) {
                return strtolower($a->clasificacion_actual) === $clasificacion_actual;
            })->values();
        }

        // Calcular totales para las tarjetas
        $totales = [
            'ventas_totales' => $ventasTotales,
            'articulos_vendidos' => $articulos->sum('articulos_vendidos'),
            'ingreso_total' => $articulos->sum('ingreso_total'),
            'valor_inventario' => $articulos->sum('valor_inventario'),
            'utilidad_bruta' => $articulos->sum('utilidad_bruta')
        ];

        // Crear explicación de códigos de colores para el tooltip
        $codigosColores = [
            ['color' => '#ff4444', 'texto' => 'Rojo: Artículo caducado'],
            ['color' => '#ff8c00', 'texto' => 'Naranja: Caduca en 0-7 días'],
            ['color' => '#ffd700', 'texto' => 'Amarillo: Caduca en 8-30 días'],
            ['color' => '#90ee90', 'texto' => 'Verde: Caduca en más de 30 días'],
            ['color' => '#ffffff', 'texto' => 'Blanco: No caduca'],
            ['color' => '#cccccc', 'texto' => 'Gris: Sin inventario']
        ];

        // Variable para las configuraciones del tooltip
        $clasificaciones_config = [];

        // Para las configuraciones del tooltip
        foreach ($clasificacionesDB as $clasificacion) {
            $clasificaciones_config[] = [
                'nombre' => $clasificacion->nombre,
                'minimo_ventas' => $clasificacion->minimo_ventas
            ];
        }

        return view('boutique.reporteo', compact(
            'articulos',
            'fechaInicio',
            'fechaFin',
            'clasificacion_actual',
            'clasificaciones_opciones',
            'familia_actual',
            'familias_opciones',
            'totales',
            'codigosColores',
            'clasificaciones_config'
        ));
    }

    public function venta_historial(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener fechas del request o establecer el último mes por defecto
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $hoy = Carbon::today();

        // Convertir a instancias de Carbon (seguras)
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin) : null;

        // Validar fechas proporcionadas
        if ($inicio && $fin) {
            if ($inicio->gt($fin)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
            }

            if ($inicio->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede estar en el futuro.']);
            }

            if ($fin->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_fin' => 'La fecha de fin no puede estar en el futuro.']);
            }
        }

        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now()->format('Y-m-d');
            $fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }

        $ventas = DB::table('boutique_ventas as v')
            ->join('boutique_ventas_detalles as vd', 'v.id', '=', 'vd.fk_id_folio')
            ->join('boutique_compras as c', 'vd.fk_id_compra', '=', 'c.id')
            ->join('boutique_articulos as a', 'c.fk_id_articulo', '=', 'a.id')
            ->join('boutique_formas_pago as fp', 'v.fk_id_forma_pago', '=', 'fp.id')
            ->join('boutique_articulos_familias as f', 'a.fk_id_familia', '=', 'f.id')
            ->join('anfitriones as anf', 'vd.fk_id_anfitrion', '=', 'anf.id')

            // CAMBIO IMPORTANTE: Filtrar por hotel tanto en ventas como en artículos
            ->where('v.fk_id_hotel', $hotelId) // Hotel de la venta
            ->where('a.fk_id_hotel', $hotelId) // Hotel del artículo
            ->where('f.fk_id_hotel', $hotelId) // Hotel de la familia (por consistencia)

            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            ->select([
                'v.folio_venta',
                'v.fecha_venta',
                'v.hora_venta',
                'fp.nombre as forma_pago',
                'v.referencia_pago',
                'a.numero_auxiliar',
                'a.nombre_articulo',
                DB::raw("CONCAT(COALESCE(anf.nombre_usuario, ''), ' ', COALESCE(anf.apellido_paterno, ''), ' ', COALESCE(anf.apellido_materno, '')) as anfitrion_nombre"),
                'f.nombre as familia_nombre',
                'vd.cantidad',
                'vd.subtotal',
                'vd.descuento',
                'vd.observaciones'
            ])
            ->orderBy('v.fecha_venta', 'desc')
            ->orderBy('v.hora_venta', 'desc')
            ->orderBy('v.folio_venta')
            ->get();

        return view('boutique.historial_venta', compact('ventas', 'fechaInicio', 'fechaFin'));
    }

    public function inventario_historial(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener fechas del request o establecer el último mes por defecto
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $hoy = Carbon::today();

        // Convertir a instancias de Carbon (seguras)
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin) : null;

        // Validar fechas proporcionadas
        if ($inicio && $fin) {
            if ($inicio->gt($fin)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
            }

            if ($inicio->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede estar en el futuro.']);
            }

            if ($fin->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_fin' => 'La fecha de fin no puede estar en el futuro.']);
            }
        }

        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now()->format('Y-m-d');
            $fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }

        $compras = DB::table('boutique_compras as c')
            ->join('boutique_articulos as a', function ($join) use ($hotelId) {
                $join->on('c.fk_id_articulo', '=', 'a.id')
                    ->where('a.fk_id_hotel', '=', $hotelId); // Aplicar filtro del hotel aquí
            })
            ->join('boutique_articulos_familias as f', 'a.fk_id_familia', '=', 'f.id')
            ->whereBetween(DB::raw('DATE(c.created_at)'), [$fechaInicio, $fechaFin])
            ->select([
                'c.id',
                'c.tipo_compra',
                'c.folio_orden_compra',
                'c.folio_factura',
                DB::raw('DATE(c.created_at) as fecha_compra'),
                'a.numero_auxiliar',
                'a.nombre_articulo',
                'f.nombre as familia_nombre',
                'c.cantidad_recibida',
                'c.costo_proveedor_unidad',
                'c.fecha_caducidad'
            ])
            ->orderBy('c.created_at', 'desc')
            ->orderBy('c.folio_factura')
            ->get();

        return view('boutique.historial_compra', compact('compras', 'fechaInicio', 'fechaFin'));
    }

    public function inventario_eliminaciones(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener fechas del request o establecer el último mes por defecto
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $hoy = Carbon::today();

        // Convertir a instancias de Carbon (seguras)
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin) : null;

        // Validar fechas proporcionadas
        if ($inicio && $fin) {
            if ($inicio->gt($fin)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede ser mayor que la fecha de fin.']);
            }

            if ($inicio->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_inicio' => 'La fecha de inicio no puede estar en el futuro.']);
            }

            if ($fin->gt($hoy)) {
                return redirect()->back()->withErrors(['fecha_fin' => 'La fecha de fin no puede estar en el futuro.']);
            }
        }

        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now()->format('Y-m-d');
            $fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }

        $eliminaciones = DB::table('boutique_compras_eliminadas as e')
            ->join('boutique_compras as c', 'e.fk_id_compra', '=', 'c.id')
            ->join('boutique_articulos as a', function ($join) use ($hotelId) {
                $join->on('c.fk_id_articulo', '=', 'a.id')
                    ->where('a.fk_id_hotel', '=', $hotelId); // Aplicar filtro del hotel aquí
            })
            ->join('boutique_articulos_familias as f', 'a.fk_id_familia', '=', 'f.id')
            ->whereBetween(DB::raw('DATE(e.created_at)'), [$fechaInicio, $fechaFin])
            ->select([
                'c.id',
                'c.tipo_compra',
                'c.folio_orden_compra',
                'c.folio_factura',
                DB::raw('DATE(c.created_at) as fecha_compra'),
                DB::raw('DATE(e.created_at) as fecha_eliminacion'),
                'a.numero_auxiliar',
                'a.nombre_articulo',
                'f.nombre as familia_nombre',
                'c.cantidad_recibida',
                'e.cantidad_eliminada',
                'c.costo_proveedor_unidad',
                'c.fecha_caducidad',
                'e.motivo',
                'e.usuario_elimino'
            ])
            ->orderBy('e.created_at', 'desc')
            ->get();

        return view('boutique.historial_eliminaciones', compact('eliminaciones', 'fechaInicio', 'fechaFin'));
    }

    public function exportar_eliminaciones(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener fechas del request o establecer el último mes por defecto
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now()->format('Y-m-d');
            $fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }

        $eliminaciones = DB::table('boutique_compras_eliminadas as e')
            ->join('boutique_compras as c', 'e.fk_id_compra', '=', 'c.id')
            ->join('boutique_articulos as a', function ($join) use ($hotelId) {
                $join->on('c.fk_id_articulo', '=', 'a.id')
                    ->where('a.fk_id_hotel', '=', $hotelId); // Aplicar filtro del hotel aquí
            })
            ->join('boutique_articulos_familias as f', 'a.fk_id_familia', '=', 'f.id')
            ->whereBetween(DB::raw('DATE(e.created_at)'), [$fechaInicio, $fechaFin])
            ->select([
                'c.id',
                'c.tipo_compra',
                'c.folio_orden_compra',
                'c.folio_factura',
                DB::raw('DATE(c.created_at) as fecha_compra'),
                DB::raw('DATE(e.created_at) as fecha_eliminacion'),
                'a.numero_auxiliar',
                'a.nombre_articulo',
                'f.nombre as familia_nombre',
                'c.cantidad_recibida',
                'e.cantidad_eliminada',
                'c.costo_proveedor_unidad',
                'c.fecha_caducidad',
                'e.motivo',
                'e.usuario_elimino'
            ])
            ->orderBy('e.created_at', 'desc')
            ->get();

        $filename = "historial_eliminaciones_" . now()->format('Ymd_His') . ".xls";

        $headers = [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $content = '
        <table border="1">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Folio Orden</th>
                    <th>Folio Factura</th>
                    <th>Fecha Compra</th>
                    <th>Fecha Eliminación</th>
                    <th>No. Auxiliar</th>
                    <th>Nombre</th>
                    <th>Familia</th>
                    <th>Cant. Recibida</th>
                    <th>Cant. Eliminada</th>
                    <th>Precio Prov.</th>
                    <th>Caducidad</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($eliminaciones as $eliminacion) {
            $content .= '
                <tr>
                    <td>' . $eliminacion->id . '</td>
                    <td>' . ($eliminacion->usuario_elimino ?? '-') . '</td>
                    <td>' . ucfirst($eliminacion->tipo_compra) . '</td>
                    <td>' . ($eliminacion->folio_orden_compra ?? '-') . '</td>
                    <td>' . $eliminacion->folio_factura . '</td>
                    <td>' . \Carbon\Carbon::parse($eliminacion->fecha_compra)->format('d/m/Y') . '</td>
                    <td>' . \Carbon\Carbon::parse($eliminacion->fecha_eliminacion)->format('d/m/Y') . '</td>
                    <td>' . str_pad($eliminacion->numero_auxiliar, 10, '0', STR_PAD_LEFT) . '</td>
                    <td>' . $eliminacion->nombre_articulo . '</td>
                    <td>' . ($eliminacion->familia_nombre ?? '-') . '</td>
                    <td>' . $eliminacion->cantidad_recibida . '</td>
                    <td>' . $eliminacion->cantidad_eliminada . '</td>
                    <td>$' . number_format($eliminacion->costo_proveedor_unidad, 2) . '</td>
                    <td>' . ($eliminacion->fecha_caducidad ? \Carbon\Carbon::parse($eliminacion->fecha_caducidad)->format('d/m/Y') : '-') . '</td>
                    <td>' . $eliminacion->motivo . '</td>
                </tr>';
        }

        $content .= '
            </tbody>
        </table>';

        return response($content, 200, $headers);
    }

    public function gestionar_familias()
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        $familias = DB::table('boutique_articulos_familias as f')
            ->leftJoin('boutique_articulos as a', function ($join) use ($hotelId) {
                $join->on('f.id', '=', 'a.fk_id_familia')
                    ->where('a.fk_id_hotel', '=', $hotelId)
                    ->whereNull('a.deleted_at');
            })
            ->where('f.fk_id_hotel', $hotelId)  // ← ESTE ES EL CAMBIO PRINCIPAL
            ->select('f.id', 'f.nombre', DB::raw('COUNT(a.id) as total_articulos'))
            ->groupBy('f.id', 'f.nombre')
            ->orderBy('f.nombre')
            ->get();

        return view('gestores.gestor_familias', compact('familias', 'hotelId'));
    }

    /* ----- Funciones ----- */
    public function guardarVenta(Request $request)
    {
        // Validar los datos de la venta
        $request->validate([
            'venta.folio_venta' => 'required|string',
            'venta.forma_pago' => 'required|integer|exists:boutique_formas_pago,id',
            'venta.referencia_pago' => 'nullable|string',
            'venta.fecha_venta' => 'required|date',
            'venta.hora_venta' => 'required|date_format:H:i',
        ]);

        // Validar los detalles de la venta
        $request->validate([
            'ventaDetalles' => 'required|array',
            'ventaDetalles.*.numero_auxiliar' => 'required|integer|exists:boutique_articulos,numero_auxiliar',
            'ventaDetalles.*.cantidad' => 'required|integer|min:1',
            'ventaDetalles.*.descuento' => 'nullable|numeric|min:0|max:100',
            'ventaDetalles.*.subtotal' => 'required|numeric|min:0',
            'ventaDetalles.*.anfitrion' => 'required|string|exists:anfitriones,RFC',
            'ventaDetalles.*.observacion' => 'nullable|string',
        ]);

        DB::beginTransaction();

        /* ----- Se obtiene el hotel (SPA) usando la nueva estructura ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        $venta = $request->venta;

        try {
            // Crear la venta (folio principal)
            $nuevaVenta = BoutiqueVenta::create([
                'fk_id_hotel' => $hotelId,
                'fecha_venta' => $venta['fecha_venta'],
                'hora_venta' => $venta['hora_venta'],
                'folio_venta' => $venta['folio_venta'],
                'fk_id_forma_pago' => $venta['forma_pago'],
                'referencia_pago' => $venta['referencia_pago'] ?? null,
            ]);

            foreach ($request->ventaDetalles as $ventaDetalle) {
                // Buscar el artículo primero
                $articulo = DB::table('boutique_articulos')
                    ->where('numero_auxiliar', $ventaDetalle['numero_auxiliar'])
                    ->where('fk_id_hotel', $hotelId) // Asegurar que pertenece al hotel actual
                    ->first();

                if (!$articulo) {
                    throw new Exception("Artículo {$ventaDetalle['numero_auxiliar']} no encontrado.");
                }

                // Buscar inventario disponible con FIFO (primero los que caducan antes)
                $inventarioDisponible = DB::table('boutique_inventario')
                    ->join('boutique_compras', 'boutique_inventario.fk_id_compra', '=', 'boutique_compras.id')
                    ->where('boutique_compras.fk_id_articulo', $articulo->id)
                    ->where('boutique_inventario.cantidad_actual', '>', 0)
                    ->orderByRaw('ISNULL(boutique_compras.fecha_caducidad), boutique_compras.fecha_caducidad ASC') // FIFO: primero los que caducan antes
                    ->select(
                        'boutique_inventario.*',
                        'boutique_compras.fecha_caducidad'
                    )
                    ->get();

                if ($inventarioDisponible->isEmpty()) {
                    throw new Exception("No hay stock disponible para el artículo: {$articulo->nombre_articulo}");
                }

                // Verificar que hay suficiente stock total
                $stockTotal = $inventarioDisponible->sum('cantidad_actual');
                if ($stockTotal < $ventaDetalle['cantidad']) {
                    throw new Exception("Stock insuficiente para el artículo: {$articulo->nombre_articulo}. Disponible: {$stockTotal}, Solicitado: {$ventaDetalle['cantidad']}");
                }

                // Obtener el anfitrión
                $anfitrion = Anfitrion::where('RFC', $ventaDetalle['anfitrion'])->first();
                if (!$anfitrion) {
                    throw new Exception("El anfitrión con RFC {$ventaDetalle['anfitrion']} no existe.");
                }

                // Procesar la venta usando FIFO
                $cantidadRestante = $ventaDetalle['cantidad'];

                foreach ($inventarioDisponible as $inventario) {
                    if ($cantidadRestante <= 0)
                        break;

                    $cantidadAUsar = min($cantidadRestante, $inventario->cantidad_actual);

                    // Crear el detalle de venta
                    BoutiqueVentaDetalle::create([
                        'fk_id_folio' => $nuevaVenta->id,
                        'fk_id_compra' => $inventario->fk_id_compra,
                        'cantidad' => $cantidadAUsar,
                        'descuento' => $ventaDetalle['descuento'] ?? 0,
                        'subtotal' => ($ventaDetalle['subtotal'] / $ventaDetalle['cantidad']) * $cantidadAUsar, // Proporcional
                        'fk_id_anfitrion' => $anfitrion->id,
                        'observaciones' => $ventaDetalle['observacion'] ?? null,
                    ]);

                    // Actualizar el inventario
                    DB::table('boutique_inventario')
                        ->where('id', $inventario->id)
                        ->decrement('cantidad_actual', $cantidadAUsar);

                    $cantidadRestante -= $cantidadAUsar;
                }

                // Verificar que se procesó toda la cantidad
                if ($cantidadRestante > 0) {
                    throw new Exception("Error al procesar la venta del artículo: {$articulo->nombre_articulo}");
                }
            }

            DB::commit(); // Confirmar los cambios si todo salió bien

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada correctamente.',
                'folio' => $venta['folio_venta']
            ]);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function nuevoArticulo(Request $request)
    {
        $request->validate([
            'nombre_articulo' => 'required|string',
            'numero_auxiliar' => 'required|string|max:10',
            'familia_id' => 'required|integer|exists:boutique_articulos_familias,id',
            'descripcion' => 'nullable|string',
            'precio_publico_unidad' => 'nullable|numeric|min:0', // Agregar validación para precio público
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Verificar que el número auxiliar sea único por hotel
        $request->validate([
            'numero_auxiliar' => 'unique:boutique_articulos,numero_auxiliar,NULL,id,fk_id_hotel,' . $hotelId,
        ]);

        // Verificar que la familia pertenezca al hotel actual
        $familia = DB::table('boutique_articulos_familias')
            ->where('id', $request->familia_id)
            ->where('fk_id_hotel', $hotelId)
            ->first();

        if (!$familia) {
            return response()->json(['success' => false, 'message' => 'La familia seleccionada no pertenece a este hotel.'], 400);
        }

        DB::beginTransaction();

        try {
            // Crear el nuevo artículo
            $articulo = BoutiqueArticulo::create([
                'nombre_articulo' => $request->nombre_articulo,
                'numero_auxiliar' => $request->numero_auxiliar,
                'fk_id_familia' => $request->familia_id,
                'descripcion' => $request->descripcion ?? null,
                'precio_publico_unidad' => $request->precio_publico_unidad ?? null,
                'fk_id_hotel' => $hotelId, // Agregar hotel ID
            ]);

            DB::commit(); // Confirmar los cambios si todo salió bien

            return response()->json([
                'success' => true,
                'message' => 'Artículo registrado correctamente.',
                'articulo' => [
                    'id' => $articulo->id,
                    'nombre' => $articulo->nombre_articulo,
                    'numero_auxiliar' => $articulo->numero_auxiliar
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function nuevaCompra(Request $request)
    {
        $request->validate([
            'tipo_compra' => 'required|in:normal,directa',
            'folio_orden_compra' => 'nullable|required_if:tipo_compra,normal|string',
            'folio_factura' => 'required|string',
            'numero_auxiliar' => 'required|integer',
            'costo_proveedor_unidad' => 'required|numeric|min:0',
            'fechas_cantidades' => 'required|array|min:1',
            'fechas_cantidades.*.cantidad' => 'required|integer|min:1',
            'fechas_cantidades.*.fecha_caducidad' => 'nullable|date',
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        DB::beginTransaction();

        try {
            // Buscar el artículo por número auxiliar Y hotel
            $articulo = BoutiqueArticulo::where('numero_auxiliar', $request->numero_auxiliar)
                ->where('fk_id_hotel', $hotelId)
                ->first();

            if (!$articulo) {
                throw new Exception('El artículo no existe en este hotel.');
            }

            // Procesar cada grupo de artículos con su respectiva cantidad y fecha de caducidad
            foreach ($request->fechas_cantidades as $item) {
                // Crear el registro de compra (sin hotel ni precio público)
                $compra = BoutiqueCompra::create([
                    'tipo_compra' => $request->tipo_compra,
                    'folio_orden_compra' => $request->tipo_compra === 'normal' ? $request->folio_orden_compra : null,
                    'folio_factura' => $request->folio_factura,
                    'fk_id_articulo' => $articulo->id,
                    'costo_proveedor_unidad' => $request->costo_proveedor_unidad,
                    'cantidad_recibida' => $item['cantidad'],
                    'fecha_caducidad' => $item['fecha_caducidad'],
                ]);

                // Crear el registro en el inventario (sin hotel)
                BoutiqueInventario::create([
                    'fk_id_compra' => $compra->id,
                    'cantidad_actual' => $item['cantidad'],
                ]);
            }

            DB::commit(); // Confirmar los cambios si todo salió bien

            return response()->json(['success' => true, 'message' => 'Compra registrada correctamente.']);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function editarCompra(Request $request)
    {
        $request->validate([
            'compra_id' => 'required|integer|exists:boutique_compras,id',
            'nueva_cantidad' => 'required|integer|min:1',
            'nueva_fecha_caducidad' => 'nullable|date',
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        DB::beginTransaction();

        try {
            // Verificar que la compra existe y pertenece al hotel
            $compra = DB::table('boutique_compras')
                ->join('boutique_articulos', 'boutique_compras.fk_id_articulo', '=', 'boutique_articulos.id')
                ->where('boutique_compras.id', $request->compra_id)
                ->where('boutique_articulos.fk_id_hotel', $hotelId)
                ->select('boutique_compras.*')
                ->first();

            if (!$compra) {
                throw new Exception('La compra no existe o no pertenece a este hotel.');
            }

            // Verificar que no existan ventas de esta compra
            $ventasExistentes = DB::table('boutique_ventas_detalles')
                ->where('fk_id_compra', $request->compra_id)
                ->count();

            if ($ventasExistentes > 0) {
                throw new Exception('No se puede editar esta compra porque ya tiene ventas asociadas.');
            }

            // Actualizar la compra
            DB::table('boutique_compras')
                ->where('id', $request->compra_id)
                ->update([
                    'cantidad_recibida' => $request->nueva_cantidad,
                    'fecha_caducidad' => $request->nueva_fecha_caducidad,
                    'updated_at' => now()
                ]);

            // Actualizar el inventario
            DB::table('boutique_inventario')
                ->where('fk_id_compra', $request->compra_id)
                ->update([
                    'cantidad_actual' => $request->nueva_cantidad,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra actualizada correctamente.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }


    public function eliminarCompra(Request $request)
    {
        $request->validate([
            'compra_id' => 'required|integer|exists:boutique_compras,id',
            'motivo' => 'required|string|max:255',
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Obtener información del usuario
        $usuarioRFC = Auth::user()->RFC;

        DB::beginTransaction();

        try {
            // Verificar que la compra existe y pertenece al hotel
            $compra = DB::table('boutique_compras')
                ->join('boutique_articulos', 'boutique_compras.fk_id_articulo', '=', 'boutique_articulos.id')
                ->join('boutique_inventario', 'boutique_compras.id', '=', 'boutique_inventario.fk_id_compra')
                ->where('boutique_compras.id', $request->compra_id)
                ->where('boutique_articulos.fk_id_hotel', $hotelId)
                ->select('boutique_compras.*', 'boutique_inventario.cantidad_actual')
                ->first();

            if (!$compra) {
                throw new Exception('La compra no existe o no pertenece a este hotel.');
            }

            // Verificar si aún hay inventario disponible
            if ($compra->cantidad_actual <= 0) {
                throw new Exception('Esta compra ya no tiene inventario disponible para eliminar.');
            }

            // Registrar la eliminación en la tabla de eliminaciones
            DB::table('boutique_compras_eliminadas')->insert([
                'fk_id_compra' => $request->compra_id,
                'motivo' => $request->motivo,
                'cantidad_eliminada' => $compra->cantidad_actual,
                'usuario_elimino' => $usuarioRFC,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Vaciar el inventario (poner cantidad_actual en 0)
            DB::table('boutique_inventario')
                ->where('fk_id_compra', $request->compra_id)
                ->update(['cantidad_actual' => 0, 'updated_at' => now()]);

            // La compra permanece en boutique_compras para mantener el historial
            $mensaje = 'La compra se ha eliminado del inventario. El registro histórico se mantiene para auditoría.';

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function editarArticulo(Request $request)
    {
        $request->validate([
            'numero_auxiliar_original' => 'required|string',
            'nuevo_numero_auxiliar' => 'required|string|max:10',
            'nuevo_nombre' => 'required|string',
            'nueva_familia_id' => 'required|integer|exists:boutique_articulos_familias,id',
            'nuevo_precio_publico' => 'nullable|numeric|min:0',
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        DB::beginTransaction();

        try {
            // Buscar el artículo original
            $articulo = DB::table('boutique_articulos')
                ->where('numero_auxiliar', $request->numero_auxiliar_original)
                ->where('fk_id_hotel', $hotelId)
                ->first();

            if (!$articulo) {
                throw new Exception('El artículo no existe o no pertenece a este hotel.');
            }

            // Si el número auxiliar cambió, verificar que no exista otro con el nuevo número
            if ($request->numero_auxiliar_original != $request->nuevo_numero_auxiliar) {
                $existeNumero = DB::table('boutique_articulos')
                    ->where('numero_auxiliar', $request->nuevo_numero_auxiliar)
                    ->where('fk_id_hotel', $hotelId)
                    ->where('id', '!=', $articulo->id)
                    ->exists();

                if ($existeNumero) {
                    throw new Exception('Ya existe otro artículo con ese número auxiliar.');
                }
            }

            // Verificar que la familia pertenezca al hotel
            $familia = DB::table('boutique_articulos_familias')
                ->where('id', $request->nueva_familia_id)
                ->where('fk_id_hotel', $hotelId)
                ->first();

            if (!$familia) {
                throw new Exception('La familia seleccionada no pertenece a este hotel.');
            }

            // Actualizar el artículo
            DB::table('boutique_articulos')
                ->where('id', $articulo->id)
                ->update([
                    'numero_auxiliar' => $request->nuevo_numero_auxiliar,
                    'nombre_articulo' => $request->nuevo_nombre,
                    'fk_id_familia' => $request->nueva_familia_id,
                    'precio_publico_unidad' => $request->nuevo_precio_publico,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Artículo actualizado correctamente.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function eliminarArticulo(Request $request)
    {
        $request->validate([
            'numero_auxiliar' => 'required|string',
        ]);

        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        DB::beginTransaction();

        try {
            // Buscar el artículo
            $articulo = DB::table('boutique_articulos')
                ->where('numero_auxiliar', $request->numero_auxiliar)
                ->where('fk_id_hotel', $hotelId)
                ->first();

            if (!$articulo) {
                throw new Exception('El artículo no existe o no pertenece a este hotel.');
            }

            // Verificar si existen compras del artículo
            $comprasExistentes = DB::table('boutique_compras')
                ->where('fk_id_articulo', $articulo->id)
                ->count();

            if ($comprasExistentes === 0) {
                // No hay compras, eliminar completamente (hard delete)
                DB::table('boutique_articulos')
                    ->where('id', $articulo->id)
                    ->delete();

                $mensaje = 'El artículo se ha eliminado completamente.';
            } else {
                // Verificar si aún hay inventario actual
                $inventarioActual = DB::table('boutique_compras')
                    ->join('boutique_inventario', 'boutique_compras.id', '=', 'boutique_inventario.fk_id_compra')
                    ->where('boutique_compras.fk_id_articulo', $articulo->id)
                    ->sum('boutique_inventario.cantidad_actual');

                if ($inventarioActual > 0) {
                    throw new Exception('No se puede eliminar este artículo porque aún tiene inventario disponible. Primero debe vender o eliminar todas las compras asociadas.');
                }

                // No hay inventario actual, usar soft delete
                DB::table('boutique_articulos')
                    ->where('id', $articulo->id)
                    ->update([
                        'deleted_at' => now(),
                        'updated_at' => now()
                    ]);

                $mensaje = 'El artículo se ha marcado como eliminado (soft delete) porque tenía compras asociadas pero ya no tiene inventario.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function agregar_familia(Request $request)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Error con la ubicación del hotel']);
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        $request->validate([
            'nombre_familia' => 'required|string|max:50'
        ], [
            'nombre_familia.required' => 'El nombre de la familia es obligatorio',
            'nombre_familia.max' => 'El nombre no puede exceder los 50 caracteres'
        ]);

        // Verificar que no exista la familia en este hotel específico
        $existeFamilia = DB::table('boutique_articulos_familias')
            ->where('nombre', $request->nombre_familia)
            ->where('fk_id_hotel', $hotelId)
            ->exists();

        if ($existeFamilia) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una familia con ese nombre en este hotel'
            ]);
        }

        try {
            DB::table('boutique_articulos_familias')->insert([
                'nombre' => $request->nombre_familia,
                'fk_id_hotel' => $hotelId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Familia agregada correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la familia: ' . $e->getMessage()
            ]);
        }
    }

    public function obtener_articulos_familia($familiaId)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Error con la ubicación del hotel']);
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Verificar que la familia pertenezca al hotel actual
        $familia = DB::table('boutique_articulos_familias')
            ->where('id', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->first();

        if (!$familia) {
            return response()->json([
                'success' => false,
                'message' => 'La familia no existe o no pertenece a este hotel'
            ]);
        }

        $articulos = DB::table('boutique_articulos')
            ->where('fk_id_familia', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->whereNull('deleted_at')
            ->select('numero_auxiliar', 'nombre_articulo', 'descripcion')
            ->orderBy('numero_auxiliar')
            ->get();

        return response()->json([
            'success' => true,
            'articulos' => $articulos,
            'familia_nombre' => $familia->nombre
        ]);
    }

    public function editar_familia(Request $request, $familiaId)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Error con la ubicación del hotel']);
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        $request->validate([
            'nombre_familia' => 'required|string|max:50'
        ]);

        // Verificar que la familia a editar pertenezca al hotel actual
        $familiaExiste = DB::table('boutique_articulos_familias')
            ->where('id', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->exists();

        if (!$familiaExiste) {
            return response()->json([
                'success' => false,
                'message' => 'La familia no existe o no pertenece a este hotel'
            ]);
        }

        // Verificar que no exista otra familia con el mismo nombre en este hotel
        $existeFamilia = DB::table('boutique_articulos_familias')
            ->where('nombre', $request->nombre_familia)
            ->where('fk_id_hotel', $hotelId)
            ->where('id', '!=', $familiaId)
            ->exists();

        if ($existeFamilia) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una familia con ese nombre en este hotel'
            ]);
        }

        DB::table('boutique_articulos_familias')
            ->where('id', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->update([
                'nombre' => $request->nombre_familia,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Familia actualizada correctamente'
        ]);
    }

    public function eliminar_familia($familiaId)
    {
        /* ----- Se obtiene el hotel (SPA) ----- */
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Error con la ubicación del hotel']);
        }

        $hotelId = $hotel->id;
        /* ------------------------------------- */

        // Verificar que la familia pertenezca al hotel actual
        $familiaExiste = DB::table('boutique_articulos_familias')
            ->where('id', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->exists();

        if (!$familiaExiste) {
            return response()->json([
                'success' => false,
                'message' => 'La familia no existe o no pertenece a este hotel'
            ]);
        }

        // Verificar si tiene artículos asociados (solo necesitamos verificar en este hotel)
        $tieneArticulos = DB::table('boutique_articulos')
            ->where('fk_id_familia', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->whereNull('deleted_at')
            ->exists();

        if ($tieneArticulos) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la familia porque tiene artículos asociados. Primero debe eliminar o reasignar los artículos.'
            ]);
        }

        DB::table('boutique_articulos_familias')
            ->where('id', $familiaId)
            ->where('fk_id_hotel', $hotelId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Familia eliminada correctamente'
        ]);
    }
}

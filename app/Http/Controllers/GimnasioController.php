<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\GimnasioRegistroAdulto;
use App\Models\GimnasioRegistroMenor;
use Carbon\Carbon;

class GimnasioController extends Controller
{
    public function obtenerHotelId()
    {
        $hotelName = session('current_spa');
        $hotel = DB::table('spas')
            ->where('nombre', 'LIKE', '%' . ucfirst(strtolower($hotelName)) . '%')
            ->first();

        if (!$hotel) {
            throw new \Exception("Hay un problema con la ubicación del hotel, por favor contacte a soporte técnico.");
        }

        return $hotel->id;
    }

    // Función auxiliar para obtener tiempo actual consistente
    private function obtenerTiempoActual()
    {
        return Carbon::now(config('app.timezone', 'America/Mexico_City'));
    }

    public function obtenerConfiguracion($hotelId)
    {
        $config = DB::table('gimnasio_config_qr_code')
            ->where('fk_id_hotel', $hotelId)
            ->first();

        if (!$config) {
            // Crear configuración por defecto si no existe
            $tiempoActual = $this->obtenerTiempoActual();

            DB::table('gimnasio_config_qr_code')->insert([
                'fk_id_hotel' => $hotelId,
                'tiempo_renovacion_qr' => 60,
                'tiempo_validez_qr' => 60,
                'created_at' => $tiempoActual,
                'updated_at' => $tiempoActual
            ]);

            return $this->obtenerConfiguracion($hotelId);
        }

        return $config;
    }

    public function generarToken($hotelId, $contexto = 'externo')
    {
        $config = $this->obtenerConfiguracion($hotelId);
        $tiempoActual = $this->obtenerTiempoActual();

        // Limpiar tokens expirados
        $this->limpiarTokensExpirados();

        // Para tokens internos (tablet), duran todo el día
        if ($contexto === 'interno') {
            $tiempoValidez = $tiempoActual->copy()->endOfDay();
            $tiempoRenovacion = $tiempoActual->copy()->endOfDay();
        } else {
            // Asegurar valores mínimos y válidos
            $minutosValidez = max($config->tiempo_validez_qr ?? 60, 5);
            $minutosRenovacion = max($config->tiempo_renovacion_qr ?? 60, 5);

            $tiempoValidez = $tiempoActual->copy()->addMinutes($minutosValidez);
            $tiempoRenovacion = $tiempoActual->copy()->addMinutes($minutosRenovacion);
        }

        // Buscar token vigente existente para este contexto
        $tokenExistente = DB::table('gimnasio_qrcodes')
            ->where('fk_id_hotel', $hotelId)
            ->where('contexto', $contexto)
            ->where('activo', true)
            ->where('created_at', '>', $tiempoActual->copy()->subMinutes($config->tiempo_renovacion_qr ?? 60))
            ->first();

        if ($tokenExistente) {
            // Convertir fecha de expiración a Carbon con zona horaria
            $fechaExpiracion = \Carbon\Carbon::parse($tokenExistente->fecha_expiracion, config('app.timezone'));

            if ($fechaExpiracion->greaterThan($tiempoActual)) {
                return $tokenExistente->token;
            }
        }

        // Desactivar tokens anteriores de este hotel y contexto
        DB::table('gimnasio_qrcodes')
            ->where('fk_id_hotel', $hotelId)
            ->where('contexto', $contexto)
            ->update([
                'activo' => false,
                'updated_at' => $tiempoActual
            ]);

        // Generar nuevo token
        $nuevoToken = Str::uuid()->toString();

        DB::table('gimnasio_qrcodes')->insert([
            'token' => $nuevoToken,
            'fk_id_hotel' => $hotelId,
            'contexto' => $contexto,
            'fecha_expiracion' => $tiempoValidez->toDateTimeString(),
            'activo' => true,
            'created_at' => $tiempoActual->toDateTimeString(),
            'updated_at' => $tiempoActual->toDateTimeString()
        ]);

        return $nuevoToken;
    }

    public function validarToken($token)
    {
        $tiempoActual = $this->obtenerTiempoActual();

        $resultado = DB::table('gimnasio_qrcodes as gq')
            ->join('spas as s', 'gq.fk_id_hotel', '=', 's.id')
            ->join('gimnasio_config_qr_code as gc', 'gq.fk_id_hotel', '=', 'gc.fk_id_hotel')
            ->where('gq.token', $token)
            ->where('gq.activo', true)
            ->where('gq.fecha_expiracion', '>', $tiempoActual->toDateTimeString())
            ->select(
                'gq.fk_id_hotel as hotel_id',
                'gq.contexto',
                's.nombre as hotel_nombre',
                'gc.tiempo_validez_qr',
                'gq.fecha_expiracion'
            )
            ->first();

        return $resultado;
    }

    private function limpiarTokensExpirados()
    {
        $tiempoActual = $this->obtenerTiempoActual();

        DB::table('gimnasio_qrcodes')
            ->where('fecha_expiracion', '<', $tiempoActual->toDateTimeString())
            ->delete();
    }

    public function qr_code($token = null)
    {
        $tiempoActual = $this->obtenerTiempoActual();

        // Si viene con token, validar si aún es válido
        if ($token) {
            $tokenData = $this->validarToken($token);

            if ($tokenData && $tokenData->contexto === 'interno') {
                // Token válido, verificar si necesita renovar el token externo
                $config = $this->obtenerConfiguracion($tokenData->hotel_id);

                // Buscar token externo actual
                $tokenExternoActual = DB::table('gimnasio_qrcodes')
                    ->where('fk_id_hotel', $tokenData->hotel_id)
                    ->where('contexto', 'externo')
                    ->where('activo', true)
                    ->first();

                $tokenExterno = null;

                if (!$tokenExternoActual) {
                    // No hay token externo, crear uno nuevo
                    $tokenExterno = $this->generarToken($tokenData->hotel_id, 'externo');
                } else {
                    // Convertir fecha de creación a Carbon con zona horaria
                    $fechaCreacion = Carbon::parse($tokenExternoActual->created_at, config('app.timezone'));
                    $fechaExpiracion = Carbon::parse($tokenExternoActual->fecha_expiracion, config('app.timezone'));

                    // Verificar si el token expiró o necesita renovación
                    if ($fechaExpiracion->lessThanOrEqualTo($tiempoActual)) {
                        // Token expirado, crear uno nuevo
                        $tokenExterno = $this->generarToken($tokenData->hotel_id, 'externo');
                    } else {
                        // Verificar si necesita renovación basado en tiempo transcurrido
                        $tiempoTranscurrido = $tiempoActual->diffInMinutes($fechaCreacion);

                        if ($tiempoTranscurrido >= ($config->tiempo_renovacion_qr ?? 60)) {
                            // Necesita renovación
                            $tokenExterno = $this->generarToken($tokenData->hotel_id, 'externo');
                        } else {
                            // Usar el token existente
                            $tokenExterno = $tokenExternoActual->token;
                        }
                    }
                }

                $urlMovil = route('gimnasio.registro', ['token' => $tokenExterno]);
                $tokenInterno = $token; // Usar el token que vino como parámetro
                $hotelName = $tokenData->hotel_nombre;

                return view('gimnasio.qr_code', compact(
                    'urlMovil',
                    'tokenInterno',
                    'hotelName',
                ));
            }

            // Token expirado o inválido, verificar si hay sesión para regenerar
            if (!session('current_spa')) {
                return response()->view('gimnasio.token_invalido', [
                    'mensaje' => 'Token expirado o inválido. Por favor, solicite un nuevo código.'
                ], 400);
            }
        }

        $user = Auth::user();

        // No hay token válido, verificar sesión
        if (!$user || ($user->rol !== 'generador_de_tokens_gimnasio' && $user->rol !== 'master' && $user->rol !== 'administrador')) {
            return response()->view('gimnasio.token_invalido', [
                'mensaje' => 'Se requiere un enlace válido para acceder al gimnasio. Por favor, inicie sesión con un usuario autorizado.'
            ], 400);
        }

        try {
            // Hay sesión válida, proceder con la lógica normal
            $hotelId = $this->obtenerHotelId();
            $hotelName = session('current_spa');

            // Generar token externo para el QR (para móviles)
            $tokenExterno = $this->generarToken($hotelId, 'externo');

            // Generar token interno para el formulario de la tablet
            $tokenInterno = $this->generarToken($hotelId, 'interno');

            // URL que irá en el código QR
            $urlMovil = route('gimnasio.registro', ['token' => $tokenExterno]);

            // Redirigir a la URL con el token interno para mantener consistencia
            return redirect()->route('gimnasio.qr_code', ['token' => $tokenInterno]);

        } catch (\Exception $e) {
            return view('gimnasio.token_invalido', [
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function registro($token = null)
    {
        if (!$token) {
            return response()->view('gimnasio.token_invalido', [
                'mensaje' => 'Token requerido para acceder al formulario.'
            ], 400);
        }

        $tokenData = $this->validarToken($token);

        if (!$tokenData) {
            return response()->view('gimnasio.token_invalido', [
                'mensaje' => 'Código QR expirado o inválido. Solicite un nuevo código.'
            ], 400);
        }

        return view('gimnasio.registro_externo', [
            'token' => $token,
            'hotelName' => $tokenData->hotel_nombre,
        ]);
    }

    public function verificarYRenovarQr($tokenInterno)
    {
        $tiempoActual = $this->obtenerTiempoActual();

        // Validar que el token interno sea válido
        $tokenData = $this->validarToken($tokenInterno);

        if (!$tokenData || $tokenData->contexto !== 'interno') {
            return response()->json(['error' => 'Token interno inválido'], 400);
        }

        $config = $this->obtenerConfiguracion($tokenData->hotel_id);

        // Buscar el token externo actual
        $tokenExternoActual = DB::table('gimnasio_qrcodes')
            ->where('fk_id_hotel', $tokenData->hotel_id)
            ->where('contexto', 'externo')
            ->where('activo', true)
            ->first();

        $necesitaRenovacion = false;

        if (!$tokenExternoActual) {
            // No hay token externo, necesita crear uno
            $necesitaRenovacion = true;
        } else {
            // Convertir fechas a Carbon con zona horaria correcta
            $fechaExpiracion = \Carbon\Carbon::parse($tokenExternoActual->fecha_expiracion, config('app.timezone'));
            $fechaCreacion = \Carbon\Carbon::parse($tokenExternoActual->created_at, config('app.timezone'));

            // Verificar si el token ya expiró
            if ($fechaExpiracion->lessThanOrEqualTo($tiempoActual)) {
                $necesitaRenovacion = true;
            } else {
                // Verificar si ha pasado el tiempo de renovación desde la creación
                $tiempoTranscurrido = $tiempoActual->diffInMinutes($fechaCreacion);

                if ($tiempoTranscurrido >= ($config->tiempo_renovacion_qr ?? 60)) {
                    $necesitaRenovacion = true;
                }
            }
        }

        if ($necesitaRenovacion) {
            // Generar nuevo token externo
            $nuevoTokenExterno = $this->generarToken($tokenData->hotel_id, 'externo');
            $nuevaUrlMovil = route('gimnasio.registro', ['token' => $nuevoTokenExterno]);

            return response()->json([
                'renovado' => true,
                'nueva_url' => $nuevaUrlMovil,
                'token_externo' => $nuevoTokenExterno,
                'tiempo_restante' => ($config->tiempo_validez_qr ?? 60) * 60 // en segundos
            ]);
        } else {
            // No necesita renovación, devolver la URL actual
            $urlActual = route('gimnasio.registro', ['token' => $tokenExternoActual->token]);

            // Calcular tiempo restante hasta expiración
            $fechaExpiracion = \Carbon\Carbon::parse($tokenExternoActual->fecha_expiracion, config('app.timezone'));
            $tiempoRestante = max(0, $tiempoActual->diffInSeconds($fechaExpiracion, false));

            return response()->json([
                'renovado' => false,
                'url_actual' => $urlActual,
                'tiempo_restante' => $tiempoRestante
            ]);
        }
    }

    public function guardarRegistro(Request $request)
    {
        $token = $request->input('token');

        // Validar que el token sea válido
        if ($token) {
            $tokenData = $this->validarToken($token);
            if (!$tokenData) {
                return response()->json(['success' => false, 'message' => 'Token inválido o expirado.'], 400);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 400);
        }

        // Obtener ID del hotel y origen del registro
        // Ambos se obtienen buscando el token en la base de datos y luego con su data se ob tiene el hotel y el origen
        $fk_id_hotel = $tokenData->hotel_id;
        $origen = $tokenData->contexto;

        if ($request->tipo === 'adulto') {
            // Validar datos del adulto
            $validated = $request->validate([
                'nombre_huesped' => 'required|string|max:100',
                'firma_huesped' => 'required|string',
            ]);

            GimnasioRegistroAdulto::create([
                'fk_id_hotel' => $fk_id_hotel,
                'origen_registro' => $origen,
                'nombre_huesped' => $validated['nombre_huesped'],
                'firma_huesped' => $validated['firma_huesped'],
            ]);

            return response()->json(['success' => true, 'message' => 'Registro de adulto guardado.']);

        } else {
            // Validar datos del tutor y anfitrión
            $validated = $request->validate([
                'nombres_menores' => 'required|json',
                'edades_menores' => 'required|json',
                'nombre_tutor' => 'required|string|max:100',
                'telefono_tutor' => 'required|string|max:20',
                'firma_tutor' => 'required|string',
                'nombre_anfitrion' => 'required|string|max:100',
                'firma_anfitrion' => 'required|string',
            ]);

            $nombres = json_decode($request->nombres_menores, true);
            $edades = json_decode($request->edades_menores, true);

            if (!is_array($nombres) || !is_array($edades) || count($nombres) !== count($edades)) {
                return response()->json(['success' => false, 'message' => 'Datos de menores inválidos.'], 422);
            }

            foreach ($nombres as $index => $nombreMenor) {
                GimnasioRegistroMenor::create([
                    'fk_id_hotel' => $fk_id_hotel,
                    'origen_registro' => $origen,
                    'nombre_menor' => $nombreMenor,
                    'edad' => $edades[$index],
                    'nombre_tutor' => $validated['nombre_tutor'],
                    'telefono_tutor' => $validated['telefono_tutor'],
                    'firma_tutor' => $validated['firma_tutor'],
                    'nombre_anfitrion' => $validated['nombre_anfitrion'],
                    'firma_anfitrion' => $validated['firma_anfitrion'],
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Registro de menores guardado.']);
        }
    }

    public function reporteo(Request $request)
    {
        // Obtener el ID del hotel
        $hotelId = $this->obtenerHotelId();

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
            $fechaInicio = Carbon::now()->subDays(7)->format('Y-m-d');
        }

        // Generar reporte por días
        $reporteDias = $this->generarReportePorDias($fechaInicio, $fechaFin, $hotelId);
        
        // Calcular totales para las cards
        $totales = $this->calcularTotales($reporteDias);

        return view('gimnasio.reporteo_entradas', compact(
            'reporteDias', 
            'totales', 
            'fechaInicio', 
            'fechaFin'
        ));

    }

    private function generarReportePorDias($fechaInicio, $fechaFin, $hotelId)
    {
        // Crear rango de fechas
        $fechas = [];
        $fecha = Carbon::parse($fechaInicio);
        $fechaFinCarbon = Carbon::parse($fechaFin);

        while ($fecha->lte($fechaFinCarbon)) {
            $fechas[] = $fecha->format('Y-m-d');
            $fecha->addDay();
        }

        $reporteDias = collect();

        foreach ($fechas as $fechaStr) {
            $fecha = Carbon::parse($fechaStr);
            
            // Query para adultos del día
            $adultos = DB::table('gimnasio_registros_adultos')
                ->where('fk_id_hotel', $hotelId)
                ->whereDate('created_at', $fechaStr)
                ->get();

            // Query para menores del día
            $menores = DB::table('gimnasio_registros_menores')
                ->where('fk_id_hotel', $hotelId)
                ->whereDate('created_at', $fechaStr)
                ->get();

            // Combinar todos los registros
            $todosRegistros = collect();
            
            // Agregar adultos
            foreach ($adultos as $adulto) {
                $todosRegistros->push((object)[
                    'tipo' => 'adulto',
                    'origen_registro' => $adulto->origen_registro,
                    'created_at' => $adulto->created_at
                ]);
            }
            
            // Agregar menores
            foreach ($menores as $menor) {
                $todosRegistros->push((object)[
                    'tipo' => 'menor',
                    'origen_registro' => $menor->origen_registro,
                    'created_at' => $menor->created_at
                ]);
            }

            // Calcular métricas del día
            $totalVisitantes = $todosRegistros->count();
            $adultosDelDia = $adultos->count();
            $menoresDelDia = $menores->count();
            $totalInternos = $todosRegistros->where('origen_registro', 'interno')->count();
            $totalExternos = $todosRegistros->where('origen_registro', 'externo')->count();

            // Calcular visitas por horario
            $visitasManana = $this->contarVisitasPorHorario($todosRegistros, '00:00:00', '11:59:59');
            $visitasMediodia = $this->contarVisitasPorHorario($todosRegistros, '12:00:00', '15:59:59');
            $visitasTarde = $this->contarVisitasPorHorario($todosRegistros, '16:00:00', '23:59:59');

            $reporteDias->push((object)[
                'fecha' => $fechaStr,
                'dia_semana' => $this->obtenerDiaSemana($fecha),
                'total_visitantes' => $totalVisitantes,
                'adultos_dia' => $adultosDelDia,
                'menores_dia' => $menoresDelDia,
                'total_internos' => $totalInternos,
                'total_externos' => $totalExternos,
                'visitas_manana' => $visitasManana,
                'visitas_mediodia' => $visitasMediodia,
                'visitas_tarde' => $visitasTarde
            ]);
        }

        return $reporteDias;
    }

    private function contarVisitasPorHorario($registros, $horaInicio, $horaFin)
    {
        return $registros->filter(function ($registro) use ($horaInicio, $horaFin) {
            $horaRegistro = Carbon::parse($registro->created_at)->format('H:i:s');
            return $horaRegistro >= $horaInicio && $horaRegistro <= $horaFin;
        })->count();
    }

    private function obtenerDiaSemana($fecha)
    {
        $diasSemana = [
            'Sunday' => 'Domingo',
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado'
        ];

        return $diasSemana[$fecha->format('l')];
    }

    private function calcularTotales($reporteDias)
    {
        $totalDias = $reporteDias->count();
        
        if ($totalDias == 0) {
            return [
                'total_visitantes' => 0,
                'promedio_diario' => 0,
                'promedio_manana' => 0,
                'promedio_mediodia' => 0,
                'promedio_tarde' => 0,
                // Sumas para la fila de totales
                'suma_total_visitantes' => 0,
                'suma_adultos' => 0,
                'suma_menores' => 0,
                'suma_internos' => 0,
                'suma_externos' => 0,
                'suma_manana' => 0,
                'suma_mediodia' => 0,
                'suma_tarde' => 0
            ];
        }

        $totalVisitantes = $reporteDias->sum('total_visitantes');
        $totalManana = $reporteDias->sum('visitas_manana');
        $totalMediodia = $reporteDias->sum('visitas_mediodia');
        $totalTarde = $reporteDias->sum('visitas_tarde');

        return [
            // Para las cards (promedios)
            'total_visitantes' => $totalVisitantes,
            'promedio_diario' => $totalVisitantes / $totalDias,
            'promedio_manana' => $totalManana / $totalDias,
            'promedio_mediodia' => $totalMediodia / $totalDias,
            'promedio_tarde' => $totalTarde / $totalDias,
            
            // Para la fila de totales (sumas)
            'suma_total_visitantes' => $reporteDias->sum('total_visitantes'),
            'suma_adultos' => $reporteDias->sum('adultos_dia'),
            'suma_menores' => $reporteDias->sum('menores_dia'),
            'suma_internos' => $reporteDias->sum('total_internos'),
            'suma_externos' => $reporteDias->sum('total_externos'),
            'suma_manana' => $reporteDias->sum('visitas_manana'),
            'suma_mediodia' => $reporteDias->sum('visitas_mediodia'),
            'suma_tarde' => $reporteDias->sum('visitas_tarde')
        ];
    }

    public function historial(Request $request)
    {
        $hotelId = $this->obtenerHotelId();
        $hotelName = session('current_spa');

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

        // Obtener registros de adultos para el hotel actual con filtro de fechas
        $registrosAdultos = GimnasioRegistroAdulto::where('fk_id_hotel', $hotelId)
            ->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin)
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener registros de menores para el hotel actual con filtro de fechas
        $registrosMenores = GimnasioRegistroMenor::where('fk_id_hotel', $hotelId)
            ->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin)
            ->orderBy('created_at', 'desc')
            ->get();

        // Mapear registros de adultos
        $registrosAdultosMapeados = $registrosAdultos->map(function ($registro) {
            return (object) [
                'id' => $registro->id,
                'tipo' => 'adulto',
                'fecha_registro' => $registro->created_at,
                'origen_registro' => $registro->origen_registro,
                'nombre_principal' => $registro->nombre_huesped,
                'edad' => null,
                'tutor' => null,
                'telefono' => null,
                'anfitrion' => null,
                'registro_original' => $registro
            ];
        });

        // Mapear registros de menores
        $registrosMenoresMapeados = $registrosMenores->map(function ($registro) {
            return (object) [
                'id' => $registro->id,
                'tipo' => 'menor',
                'fecha_registro' => $registro->created_at,
                'origen_registro' => $registro->origen_registro,
                'nombre_principal' => $registro->nombre_menor,
                'edad' => $registro->edad,
                'tutor' => $registro->nombre_tutor,
                'telefono' => $registro->telefono_tutor,
                'anfitrion' => $registro->nombre_anfitrion,
                'registro_original' => $registro
            ];
        });

        // Combinar y ordenar todos los registros
        $todosLosRegistros = $registrosAdultosMapeados->concat($registrosMenoresMapeados)
            ->sortByDesc('fecha_registro');

        return view('gimnasio.historial_entradas', compact('todosLosRegistros', 'fechaInicio', 'fechaFin'));
    }

    public function token_invalido()
    {
        return view('gimnasio.token_invalido');
    }
}

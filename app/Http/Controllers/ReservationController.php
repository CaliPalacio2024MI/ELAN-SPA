<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    Reservation,
    GrupoReserva,
    Client,
    Experience,
    Anfitrion,
    Cabina,
    Spa,
    BlockedSlot,
    AnfitrionOperativo
};

class ReservationController extends Controller
{
    // Vista principal: sábana de reservaciones según spa y fecha
    public function index(Request $request)
    {
        $spaNombre = session('current_spa');

        if (!$spaNombre) {
            return back()->withErrors(['spa_id' => 'No se encontró un spa asignado.']);
        }

        $spa = Spa::where('nombre', $spaNombre)->first();

        if (!$spa) {
            return back()->withErrors(['spa_id' => 'No se encontró el spa en la base de datos.']);
        }

        $fechaSeleccionada = $request->input('fecha', now()->toDateString());

        $anfitriones = $this->getAnfitrionesActivos($spa->id);
        $clients = Client::all();
        $experiences = Experience::where('spa_id', $spa->id)->where('activo', true)->get()->map(function ($experience) {
            $experience->nombre_con_info = "{$experience->nombre} ({$experience->duracion} min - $" . number_format($experience->precio, 2) . ")";
            return $experience;
        });
        $cabinas = Cabina::where('spa_id', $spa->id)->where('activo', true)->get();

        // Normalizar horarios de anfitriones para el día solicitado
        $diaSemana = strtolower(\Carbon\Carbon::parse($fechaSeleccionada)->locale('es')->isoFormat('dddd'));
        $diaSemana = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $diaSemana);

        $horariosAnfitriones = [];
        foreach ($anfitriones as $anfitrion) {
            $horariosRaw = $anfitrion->horario->horarios ?? [];
            $normalizados = [];
            foreach ($horariosRaw as $dia => $horas) {
                $diaSinTilde = strtolower(strtr($dia, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']));
                $normalizados[$diaSinTilde] = $horas;
            }
            $horariosAnfitriones[$anfitrion->id] = $normalizados;
        }

        $anfitrionesDisponibles = $anfitriones->filter(function ($anfitrion) use ($horariosAnfitriones, $diaSemana) {
            $horario = $horariosAnfitriones[$anfitrion->id] ?? [];
            return !empty($horario[$diaSemana] ?? []);
        });

        $horariosFiltrados = $anfitrionesDisponibles->pluck('horario', 'id');

        $reservaciones = Reservation::where('spa_id', $spa->id)
            ->whereIn('anfitrion_id', $anfitriones->pluck('id'))
            ->whereDate('fecha', $fechaSeleccionada)
            ->where('estado', 'activa')
            ->get();

        $bloqueos = BlockedSlot::where('spa_id', $spa->id)
            ->where('fecha', $fechaSeleccionada)
            ->get();

        $cabinasOcupadas = Reservation::where('spa_id', $spa->id)
            ->whereDate('fecha', $fechaSeleccionada)
            ->where('estado', 'activa')
            ->pluck('cabina_id')
            ->toArray();

        $clases_actividad = AnfitrionOperativo::whereHas('anfitrion', function ($q) use ($spa) {
            $q->where('spa_id', $spa->id)
              ->where('rol', 'anfitrion')
              ->where('activo', true);
        })
        ->whereIn('departamento', ['spa', 'salon de belleza'])
        ->pluck('clases_actividad')
        ->flatten()
        ->unique()
        ->values();

        return view('reservations.index', compact(
            'anfitrionesDisponibles',
            'horariosFiltrados',
            'horariosAnfitriones',
            'fechaSeleccionada',
            'clients',
            'experiences',
            'reservaciones',
            'bloqueos',
            'cabinas',
            'cabinasOcupadas',
            'clases_actividad'
        ));
    }

    // Crear una reservación individual
    public function store(Request $request)
    {
        Log::info("Solicitud para crear reservación", $request->all());

        $spa = Spa::where('nombre', session('current_spa'))->first();
        if (!$spa) {
            return $this->jsonOrRedirectError($request, 'No se pudo determinar el spa actual.');
        }

        try {
            $validated = $request->validate([
                'cliente_existente_id' => 'nullable|exists:clients,id',
                'correo_cliente' => 'required|email',
                'nombre_cliente' => 'required_without:cliente_existente_id|string|max:255',
                'apellido_paterno_cliente' => 'required_without:cliente_existente_id|string|max:255',
                'apellido_materno_cliente' => 'nullable|string|max:255',
                'telefono_cliente' => 'required_without:cliente_existente_id|string|max:20',
                'tipo_visita_cliente' => 'required_without:cliente_existente_id|string',
                'experiencia_id' => 'required|exists:experiences,id',
                'anfitrion_id' => 'required|exists:anfitriones,id',
                'fecha' => 'required|date',
                'hora' => 'required|date_format:H:i',
                'cabina_id' => 'required|exists:cabinas,id',
                'observaciones' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonOrRedirectValidationError($request, $e);
        }

        // Crear cliente si no existe, o usar existente
        $validated['cliente_id'] = $validated['cliente_existente_id'] ?? Client::create([
            'nombre' => $validated['nombre_cliente'],
            'apellido_paterno' => $validated['apellido_paterno_cliente'],
            'apellido_materno' => $validated['apellido_materno_cliente'] ?? null,
            'correo' => $validated['correo_cliente'],
            'telefono' => $validated['telefono_cliente'],
            'tipo_visita' => $validated['tipo_visita_cliente'],
        ])->id;

        $experiencia = Experience::where('id', $validated['experiencia_id'])->where('activo', true)->firstOrFail();

        $horaInicio = $validated['hora'];
        $duracionMin = $experiencia->duracion;
        $breakTime = config('finance.reservations.therapist_break_time', 10);
        $horaFin = date('H:i', strtotime("$horaInicio +{$duracionMin} minutes"));
        $horaFinDescanso = date('H:i', strtotime("$horaFin +{$breakTime} minutes"));

        if ($horaFinDescanso > '21:00') {
            return $this->jsonOrRedirectError($request, 'No hay tiempo suficiente para completar esta experiencia antes del cierre.');
        }

        $anfitrion = Anfitrion::where('id', $validated['anfitrion_id'])
            ->where('spa_id', $spa->id)
            ->where('rol', 'anfitrion')
            ->where('activo', true)
            ->whereHas('operativo', function ($q) {
                $q->whereIn('departamento', ['spa', 'salon de belleza']);
            })
            ->firstOrFail();

        if ($this->hayConflictoCliente($validated, $horaInicio, $horaFin, $spa->id)) {
            return $this->jsonOrRedirectError($request, 'El cliente ya tiene una reservación en este horario.');
        }

        if ($this->hayConflictoAnfitrion($validated, $horaInicio, $horaFinDescanso, $spa->id)) {
            return $this->jsonOrRedirectError($request, 'El anfitrión ya tiene una reservación o descanso en este horario.');
        }

        if (!$this->cabinaPerteneceAlSpa($validated['cabina_id'], $spa->id)) {
            return $this->jsonOrRedirectError($request, 'La cabina seleccionada no pertenece al spa actual.');
        }

        if ($this->hayConflictoCabina($validated, $horaInicio, $horaFin)) {
            return $this->jsonOrRedirectError($request, 'La cabina ya está ocupada en este horario.');
        }

        $reservation = Reservation::create([
            'cliente_id' => $validated['cliente_id'],
            'experiencia_id' => $validated['experiencia_id'],
            'anfitrion_id' => $validated['anfitrion_id'],
            'cabina_id' => $validated['cabina_id'],
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'observaciones' => $validated['observaciones'] ?? null,
            'acompanante' => false,
            'spa_id' => $spa->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Reservación creada correctamente.']);
        }

        return redirect()->route('reservations.index')->with('success', 'Reservación creada correctamente.');
    }

    // Obtener detalles para mostrar en JSON (por ejemplo, para edición)
    public function show($id)
    {
        $reservation = Reservation::with(['cliente', 'experiencia', 'anfitrion', 'cabina'])->find($id);

        if (!$reservation) {
            return response()->json(['error' => 'Reservación no encontrada'], 404);
        }

        $cliente = $reservation->cliente;
        $nombreCliente = trim("{$cliente->nombre} {$cliente->apellido_paterno} {$cliente->apellido_materno}");

        return response()->json([
            'cliente' => $nombreCliente,
            'anfitrion' => $reservation->anfitrion->nombre_usuario ?? 'N/D',
            'experiencia' => $reservation->experiencia->nombre ?? 'N/D',
            'fecha' => $reservation->fecha,
            'hora' => substr($reservation->hora, 0, 5),
            'cabina' => $reservation->cabina->nombre ?? 'No asignada',
            'observaciones' => $reservation->observaciones ?? '',
        ]);
    }

    // Datos para editar reservación (rellenar formulario)
    public function edit($id)
    {
        $reservation = Reservation::with(['cliente', 'experiencia', 'anfitrion.horario', 'cabina'])->find($id);

        if (!$reservation) {
            return response()->json(['error' => 'Reservación no encontrada'], 404);
        }

        $cliente = $reservation->cliente;

        $anfitrionesDisponibles = Anfitrion::where('spa_id', $reservation->spa_id)->where('activo', true)->get();
        
        $horariosDisponibles = [];
        if ($reservation->anfitrion) {
            $anfitrion = $reservation->anfitrion;
            $fecha = $reservation->fecha;
            
            // 1. Obtener horario base
            $horariosBase = [];
            if ($anfitrion->horario) {
                $diaSemana = strtolower(\Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd'));
                $diaSemana = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $diaSemana);
                $horariosRaw = $anfitrion->horario->horarios ?? [];
                $normalizados = [];
                foreach ($horariosRaw as $dia => $horas) {
                    $diaSinTilde = strtolower(strtr($dia, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']));
                    $normalizados[$diaSinTilde] = is_array($horas) ? $horas : [];
                }
                if (isset($normalizados[$diaSemana]) && is_array($normalizados[$diaSemana])) {
                    $horariosBase = array_map(fn($h) => \Carbon\Carbon::parse(trim($h))->format('H:i'), $normalizados[$diaSemana]);
                    sort($horariosBase);
                }
            }

            if (!empty($horariosBase)) {
                // 2. Obtener intervalos ocupados
                $busyIntervals = $this->getBusyIntervalsForHost($anfitrion->id, $fecha, $id);
                $breakTime = config('finance.reservations.therapist_break_time', 10);

                // 3. Filtrar horarios
                $experienceDuration = $reservation->experiencia->duracion ?? 0;
                foreach ($horariosBase as $hora) {
                    $slotStart = \Carbon\Carbon::parse($fecha . ' ' . $hora);
                    $isAvailable = true;
                    
                    if ($experienceDuration > 0) {
                        $slotEnd = $slotStart->copy()->addMinutes($experienceDuration + $breakTime);
                        foreach ($busyIntervals as $busy) {
                            if ($slotStart < $busy['end'] && $slotEnd > $busy['start']) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    } else {
                        foreach ($busyIntervals as $busy) {
                            if ($slotStart >= $busy['start'] && $slotStart < $busy['end']) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    }

                    if ($isAvailable) {
                        $horariosDisponibles[] = $hora;
                    }
                }
            }
        }

        return response()->json([
            'id' => $reservation->id,
            'fecha' => $reservation->fecha,
            'hora' => substr($reservation->hora, 0, 5),
            'anfitrion_id' => $reservation->anfitrion_id,
            'experiencia_id' => $reservation->experiencia_id,
            'duracion' => $reservation->experiencia->duracion ?? null,
            'cabina_id' => $reservation->cabina_id,
            'observaciones' => $reservation->observaciones,

            // Datos del cliente para formulario
            'cliente_existente_id' => $cliente->id ?? null,
            'correo_cliente' => $cliente->correo ?? '',
            'nombre_cliente' => $cliente->nombre ?? '',
            'apellido_paterno_cliente' => $cliente->apellido_paterno ?? '',
            'apellido_materno_cliente' => $cliente->apellido_materno ?? '',
            'telefono_cliente' => $cliente->telefono ?? '',
            'tipo_visita_cliente' => $cliente->tipo_visita ?? '',

            'anfitriones' => $anfitrionesDisponibles->map(function($anfitrion) {
                return [
                    'id' => $anfitrion->id,
                    'nombre' => $anfitrion->nombre_usuario
                ];
            })->values(),
            'horarios_disponibles' => $horariosDisponibles,
            'grupo' => [], // Forzar reseteo de UI en frontend
        ]);
    }


    // Actualizar reservación
    public function update(Request $request, $id)
    {
        // Si la actualización proviene de un drag-and-drop, usar el manejador específico.
        if ($request->has('from_drag')) {
            return $this->handleDragAndDropUpdate($request, $id);
        }

        Log::info("Solicitud para actualizar reservación ID $id", $request->all());

        $reservation = Reservation::findOrFail($id);

        if ($reservation->check_out) {
            Log::warning("Intento de modificar reservación con check-out ID: $id");
            return response()->json(['error' => 'No se puede modificar una reservación con check-out realizado.'], 422);
        }

        $spaId = $reservation->spa_id;

        try {
            $validated = $request->validate([
                'cliente_existente_id' => 'required|exists:clients,id',
                'correo_cliente' => 'required|email',
                'nombre_cliente' => 'required|string|max:255',
                'apellido_paterno_cliente' => 'required|string|max:255',
                'apellido_materno_cliente' => 'nullable|string|max:255',
                'telefono_cliente' => 'required|string|max:20',
                'tipo_visita_cliente' => 'required|string',

                'experiencia_id' => 'required|exists:experiences,id',
                'anfitrion_id' => 'required|exists:anfitriones,id',
                'fecha' => 'required|date',
                'hora' => 'required|date_format:H:i',
                'cabina_id' => 'required|exists:cabinas,id',
                'observaciones' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Errores de validación',
            ], 422);
        }

        // Actualizar datos del cliente
        $cliente = Client::find($validated['cliente_existente_id']);
        if ($cliente) {
            $cliente->update([
                'nombre' => $validated['nombre_cliente'],
                'apellido_paterno' => $validated['apellido_paterno_cliente'],
                'apellido_materno' => $validated['apellido_materno_cliente'],
                'correo' => $validated['correo_cliente'],
                'telefono' => $validated['telefono_cliente'],
                'tipo_visita' => $validated['tipo_visita_cliente'],
            ]);
        }

        $validated['cliente_id'] = $cliente->id;

        // Calcular horas para validaciones
        $experiencia = Experience::find($validated['experiencia_id']);
        $duracionMin = $experiencia->duracion ?? 0;
        
        $newAnfitrion = Anfitrion::with('operativo')->find($validated['anfitrion_id']);
        $breakTime = 0;
        if ($newAnfitrion && $newAnfitrion->operativo && strtolower($newAnfitrion->operativo->departamento) === 'spa') {
            $breakTime = config('finance.reservations.therapist_break_time', 10);
        }

        $horaInicio = $validated['hora'];
        $horaFin = date('H:i', strtotime("$horaInicio +{$duracionMin} minutes"));
        $horaFinDescanso = date('H:i', strtotime("$horaFin +{$breakTime} minutes"));

        // Validar conflictos de horarios para cliente
        $clienteOcupado = Reservation::where('cliente_id', $validated['cliente_id'])
            ->where('fecha', $validated['fecha'])
            ->where('id', '!=', $reservation->id)
            ->where('estado', 'activa')
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora', [$horaInicio, $horaFin])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaInicio])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaFin]);
            })->exists();

        if ($clienteOcupado) {
            return response()->json(['error' => 'El cliente ya tiene otra reservación en este horario.'], 422);
        }

        // Validar que la cabina pertenezca al spa actual
        $cabinaId = $validated['cabina_id'];
        $cabinaValida = Cabina::where('id', $cabinaId)->where('spa_id', $spaId)->exists();

        if (!$cabinaValida) {
            return response()->json(['error' => 'La cabina seleccionada no pertenece al spa actual.'], 422);
        }

        // Validar conflictos de horarios para cabina
        $cabinaOcupada = Reservation::where('cabina_id', $cabinaId)
            ->where('fecha', $validated['fecha'])
            ->where('id', '!=', $reservation->id)
            ->where('estado', 'activa')
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora', [$horaInicio, $horaFin])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaInicio])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaFin]);
            })->exists();

        if ($cabinaOcupada) {
            return response()->json(['error' => 'La cabina ya está ocupada en este horario.'], 422);
        }

        // Validar conflictos de horarios para anfitrión (incluye descanso)
        if ($this->hayConflictoAnfitrion($validated, $horaInicio, $horaFinDescanso, $spaId, $id)) {
            return response()->json(['error' => 'El anfitrión ya tiene otra reservación en este horario.'], 422);
        }

        // Actualizar reservación
        $reservation->update([
            'cliente_id' => $validated['cliente_id'],
            'experiencia_id' => $validated['experiencia_id'],
            'anfitrion_id' => $validated['anfitrion_id'],
            'cabina_id' => $validated['cabina_id'],
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'observaciones' => $validated['observaciones'] ?? null,
            'acompanante' => false,
        ]);

        Log::info("Reservación ID $id actualizada correctamente.");

        return response()->json(['success' => true, 'message' => 'Reservación actualizada correctamente.']);
    }

    /**
     * Maneja la actualización de una reservación a través de drag-and-drop.
     */
    private function handleDragAndDropUpdate(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        // VALIDACIÓN: El anfitrión debe tener la especialidad (clase) requerida por la experiencia.
        $newAnfitrion = Anfitrion::with('operativo')->find($request->input('anfitrion_id'));
        $experience = $reservation->experiencia;
        
        // Función para normalizar strings (minúsculas, sin tildes, sin espacios extra)
        $normalize = function ($str) {
            if (!$str) return '';
            $str = mb_strtolower($str, 'UTF-8');
            $str = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
                ['a', 'e', 'i', 'o', 'u', 'n'],
                $str
            );
            return trim($str);
        };

        $requiredName = $normalize($experience->nombre);
        $requiredClass = $normalize($experience->clase);
        
        $anfitrionClasses = $newAnfitrion->operativo->clases_actividad ?? [];
        $normalizedAnfitrionClasses = array_map($normalize, $anfitrionClasses);

        $isQualified = in_array($requiredClass, $normalizedAnfitrionClasses) || in_array($requiredName, $normalizedAnfitrionClasses);

        // --- DEBUGGING ---
        Log::debug('--- Calificación de Anfitrión D&D (Lógica flexible) ---');
        Log::debug("Reservación ID: {$id}");
        Log::debug("Anfitrión a verificar ID: {$newAnfitrion->id}");
        Log::debug("Clase requerida (normalizada): '{$requiredClass}'");
        Log::debug("Nombre requerido (normalizado): '{$requiredName}'");
        Log::debug("Clases del anfitrión (normalizadas): " . implode(', ', $normalizedAnfitrionClasses));
        Log::debug("Resultado de calificación: " . ($isQualified ? 'CALIFICADO' : 'NO CALIFICADO'));
        // --- FIN DEBUGGING ---

        if (!$isQualified) {
            Log::warning("VALIDACIÓN FALLIDA: Anfitrión no posee la clase '{$requiredClass}' ni el nombre '{$requiredName}'.");
            return response()->json([
                'error' => 'El anfitrión no está calificado para realizar esta experiencia.'
            ], 422);
        }

        // No se pueden mover reservaciones con check-out.
        if ($reservation->check_out) {
            return response()->json(['error' => 'No se puede mover una reservación con check-out realizado.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'anfitrion_id' => 'required|exists:anfitriones,id',
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Datos inválidos.'], 422);
        }

        $validated = $validator->validated();
        $spaId = $reservation->spa_id;

        // Datos necesarios para validaciones de conflicto
        $dataForConflictCheck = [
            'anfitrion_id' => $validated['anfitrion_id'],
            'cabina_id' => $reservation->cabina_id, // La cabina no cambia
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'id' => $reservation->id, // Para excluirse a sí misma en la validación
        ];

        // Calcular horas para validaciones
        $duracionMin = $reservation->experiencia->duracion;
        $breakTime = 0;
        if ($newAnfitrion && $newAnfitrion->operativo && strtolower($newAnfitrion->operativo->departamento) === 'spa') {
            $breakTime = config('finance.reservations.therapist_break_time', 10);
        }

        $horaInicio = $validated['hora'];
        $horaFin = date('H:i', strtotime("$horaInicio +{$duracionMin} minutes"));
        $horaFinDescanso = date('H:i', strtotime("$horaFin +{$breakTime} minutes"));

        // Reutilizar funciones de validación de conflictos, excluyendo la reserva actual
        if ($this->hayConflictoAnfitrion($dataForConflictCheck, $horaInicio, $horaFinDescanso, $spaId, $reservation->id)) {
            return response()->json(['error' => 'El anfitrión ya tiene una reservación o descanso en este nuevo horario.'], 422);
        }

        if ($this->hayConflictoCabina($dataForConflictCheck, $horaInicio, $horaFin, $reservation->id)) {
            return response()->json(['error' => 'La cabina está ocupada en este nuevo horario.'], 422);
        }

        // Actualizar la reservación con los nuevos datos
        $reservation->update([
            'anfitrion_id' => $validated['anfitrion_id'],
            'hora' => $validated['hora'],
            'fecha' => $validated['fecha'],
        ]);

        Log::info("Reservación ID $id movida a las {$validated['hora']} con anfitrión {$validated['anfitrion_id']}.");

        // Cargar relaciones para devolver datos completos
        $reservation->load(['cliente', 'experiencia', 'anfitrion', 'cabina']);

        $cliente = $reservation->cliente;
        $nombreCliente = trim("{$cliente->nombre} {$cliente->apellido_paterno} {$cliente->apellido_materno}");

        return response()->json([
            'success' => true,
            'message' => 'Reservación movida correctamente.',
            'reservation' => [
                'id' => $reservation->id,
                'cliente_nombre' => $nombreCliente,
                'experiencia_nombre' => $reservation->experiencia->nombre ?? 'N/D',
                'anfitrion_nombre' => $reservation->anfitrion->nombre_usuario ?? 'N/D',
                'hora' => substr($reservation->hora, 0, 5),
                'duracion' => $reservation->experiencia->duracion,
                'anfitrion_id' => $reservation->anfitrion_id,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'clase_experiencia' => $reservation->experiencia->clase,
            ]
        ]);
    }

    // Cancelar (no eliminar) reservación
    public function destroy($id)
    {
        Log::info("Solicitud para eliminar reservación ID: $id");

        $reservation = Reservation::find($id);

        if (!$reservation) {
            Log::warning("Reservación no encontrada ID: $id");
            return response()->json(['error' => 'Reservación no encontrada.'], 404);
        }

        if ($reservation->check_out) {
            Log::warning("Intento de cancelar reservación con check-out ID: $id");
            return response()->json(['error' => 'No se puede cancelar una reservación con check-out realizado.'], 422);
        }

        $spa = Spa::where('nombre', session('current_spa'))->first();

        if (!$spa || $reservation->spa_id !== $spa->id) {
            Log::warning("Acceso denegado. Reservación no pertenece al spa actual.");
            return response()->json(['error' => 'No tienes permiso para eliminar esta reservación.'], 403);
        }

        $reservation->estado = 'cancelada';
        $reservation->es_principal = false; // Desmarcar principal
        $reservation->save();

        // Si es parte de un grupo, intentar reasignar principal
        if ($reservation->grupo_reserva_id) {
            $grupoReservasActivas = Reservation::where('grupo_reserva_id', $reservation->grupo_reserva_id)
                ->where('estado', 'activa')
                ->orderBy('fecha')
                ->orderBy('hora')
                ->get();

            if ($grupoReservasActivas->isNotEmpty()) {
                // Verificar si ya existe un principal activo
                $principalActual = $grupoReservasActivas->firstWhere('es_principal', true);
                if (!$principalActual) {
                    // Asignar como principal la primera reservación activa
                    $nuevaPrincipal = $grupoReservasActivas->first();
                    $nuevaPrincipal->es_principal = true;
                    $nuevaPrincipal->save();
                    Log::info("Reasignado principal en grupo {$reservation->grupo_reserva_id} a reservación ID {$nuevaPrincipal->id}");
                }
            }
        }

        Log::info("Reservación eliminada correctamente.");

        return response()->json(['success' => true, 'message' => 'Reservación eliminada correctamente.']);
    }

    // Crear reservas en grupo (batch)
    public function storeGrupo(Request $request)
    {
        $reservas = $request->input('grupo');
        $spa = Spa::where('nombre', session('current_spa'))->first();

        if (!is_array($reservas) || empty($reservas) || !$spa) {
            return response()->json(['error' => 'Datos inválidos o spa no encontrado.'], 422);
        }

        $errores = [];
        $reservasValidas = [];

        foreach ($reservas as $i => $r) {
            $index = $r['index'] ?? ($i + 1);

            $validador = Validator::make($r, [
                'cliente_existente_id' => 'nullable|exists:clients,id',
                'correo_cliente' => 'required|email',
                'nombre_cliente' => 'required_without:cliente_existente_id|string|max:255',
                'apellido_paterno_cliente' => 'required_without:cliente_existente_id|string|max:255',
                'apellido_materno_cliente' => 'nullable|string|max:255',
                'telefono_cliente' => 'required_without:cliente_existente_id|string|max:20',
                'tipo_visita_cliente' => 'required_without:cliente_existente_id|string',
                'experiencia_id' => 'required|exists:experiences,id',
                'anfitrion_id' => 'required|exists:anfitriones,id',
                'fecha' => 'required|date',
                'hora' => 'required|date_format:H:i',
                'cabina_id' => 'required|exists:cabinas,id',
                'observaciones' => 'nullable|string',
            ]);

            if ($validador->fails()) {
                $errores["Reserva #$index"] = $validador->errors()->all();
                continue;
            }

            $data = $validador->validated();
            $data['index'] = $index;

            // Verificar experiencia y calcular horarios
            $exp = Experience::find($data['experiencia_id']);
            if (!$exp) {
                $errores["Reserva #$index"][] = 'La experiencia no existe.';
                continue;
            }

            $data['duracion'] = $exp->duracion;
            $breakTime = config('finance.reservations.therapist_break_time', 10);
            $data['hora_fin'] = date('H:i', strtotime("{$data['hora']} +{$exp->duracion} minutes"));
            $data['hora_fin_descanso'] = date('H:i', strtotime("{$data['hora_fin']} +{$breakTime} minutes"));
            $data['spa_id'] = $spa->id;

            // Obtener y normalizar horario del anfitrión
            $anfitrion = Anfitrion::with('horario')->find($data['anfitrion_id']);
            $horario = $anfitrion?->horario?->horarios ?? [];
            $horarioNormalizado = [];
            foreach ($horario as $diaClave => $horas) {
                $claveLimpia = strtolower(strtr($diaClave, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']));
                $horarioNormalizado[$claveLimpia] = $horas;
            }
            $horario = $horarioNormalizado;

            // Día en formato sin tildes
            \Carbon\Carbon::setLocale('es');
            $dia = strtolower(\Carbon\Carbon::parse($data['fecha'])->isoFormat('dddd'));
            $dia = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $dia);

            $horaSolicitada = \Carbon\Carbon::parse($data['hora'])->format('H:i');

            // Validar disponibilidad exacta del horario del anfitrión
            $horasDia = array_map('trim', (array) ($horario[$dia] ?? []));
            
            // Normalizar las horas del horario a formato H:i
            $horasDiaNormalizadas = array_map(fn($h) => \Carbon\Carbon::parse($h)->format('H:i'), $horasDia);

            if (!in_array($horaSolicitada, $horasDiaNormalizadas)) {
                $errores["Reserva #$index"][] = "El anfitrión no tiene un horario de inicio disponible a las {$horaSolicitada} el día {$dia}. Horarios disponibles: " . implode(', ', $horasDia);
                continue;
            }

            $reservasValidas[] = $data;
        }

        // Validaciones cruzadas y conflictos
        foreach ($reservasValidas as $r) {
            $inicio = strtotime($r['hora']);
            $finAnfitrion = strtotime($r['hora_fin_descanso']);
            $finCabina = strtotime($r['hora_fin']);

            $bloqueos = BlockedSlot::where([
                ['spa_id', $spa->id],
                ['anfitrion_id', $r['anfitrion_id']],
                ['fecha', $r['fecha']]
            ])->get();

            foreach ($bloqueos as $bloqueo) {
                $bInicio = strtotime($bloqueo->hora);
                $bFin = strtotime("{$bloqueo->hora} +{$bloqueo->duracion} minutes");

                if ($inicio < $bFin && $bInicio < $finAnfitrion) {
                    $errores["Reserva #{$r['index']}"][] = 'El anfitrión tiene un bloqueo en ese horario.';
                }
            }

            if ($this->hayConflictoCabina($r, $r['hora'], $r['hora_fin'])) {
                $errores["Reserva #{$r['index']}"][] = 'La cabina ya está ocupada.';
            }

            if ($this->hayConflictoAnfitrion($r, $r['hora'], $r['hora_fin_descanso'], $spa->id)) {
                $errores["Reserva #{$r['index']}"][] = 'El anfitrión ya está ocupado.';
            }

            // Conflictos internos entre reservas grupales
            foreach ($reservasValidas as $o) {
                if ($r['index'] === $o['index'] || $r['fecha'] !== $o['fecha']) continue;

                $oInicio = strtotime($o['hora']);
                $oFinAnfitrion = strtotime($o['hora_fin_descanso']);
                $oFinCabina = strtotime($o['hora_fin']);

                if ($r['cabina_id'] === $o['cabina_id'] && $inicio < $oFinCabina && $oInicio < $finCabina) {
                    $errores["Reserva #{$r['index']}"][] = "Conflicto interno de cabina con reserva #{$o['index']}.";
                }

                if ($r['anfitrion_id'] === $o['anfitrion_id'] && $inicio < $oFinAnfitrion && $oInicio < $finAnfitrion) {
                    $errores["Reserva #{$r['index']}"][] = "Conflicto interno de anfitrión con reserva #{$o['index']}.";
                }
            }
        }

        if ($errores) {
            return response()->json([
                'message' => 'Errores de validación en el grupo',
                'errors' => $errores
            ], 422);
        }

        // --- Crear grupo y reservas ---
        $isGroup = count($reservasValidas) > 1;
        $grupoId = null;

        if ($isGroup) {
            // Para un grupo, el cliente principal es el de la primera reserva.
            $primerClienteId = $reservasValidas[0]['cliente_existente_id'] ?? Client::create([
                'nombre' => $reservasValidas[0]['nombre_cliente'],
                'apellido_paterno' => $reservasValidas[0]['apellido_paterno_cliente'],
                'apellido_materno' => $reservasValidas[0]['apellido_materno_cliente'] ?? null,
                'correo' => $reservasValidas[0]['correo_cliente'],
                'telefono' => $reservasValidas[0]['telefono_cliente'],
                'tipo_visita' => $reservasValidas[0]['tipo_visita_cliente'],
            ])->id;

            $grupo = GrupoReserva::create([
                'cliente_id' => $primerClienteId,
            ]);
            $grupoId = $grupo->id;
        }

        foreach ($reservasValidas as $i => $data) {
            // Crear o encontrar el cliente para esta reserva específica.
            $clienteId = $data['cliente_existente_id'] ?? Client::create([
                'nombre' => $data['nombre_cliente'],
                'apellido_paterno' => $data['apellido_paterno_cliente'],
                'apellido_materno' => $data['apellido_materno_cliente'] ?? null,
                'correo' => $data['correo_cliente'],
                'telefono' => $data['telefono_cliente'],
                'tipo_visita' => $data['tipo_visita_cliente'],
            ])->id;

            Reservation::create([
                'spa_id' => $spa->id,
                'cliente_id' => $clienteId,
                'experiencia_id' => $data['experiencia_id'],
                'anfitrion_id' => $data['anfitrion_id'],
                'cabina_id' => $data['cabina_id'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'observaciones' => $data['observaciones'] ?? null,
                'grupo_reserva_id' => $grupoId, // Será null si no es un grupo
                'es_principal' => $isGroup && $i === 0,
            ]);
        }

        $message = count($reservasValidas) === 1
            ? 'Reservación creada correctamente.'
            : 'Reservaciones grupales creadas correctamente.';

        return response()->json(['success' => true, 'message' => $message]);
    }

    // Buscar cliente por correo (Ajax)
    public function buscarCliente(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $cliente = Client::where('correo', $request->correo)->first();

        if (!$cliente) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'success' => true,
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'apellido_paterno' => $cliente->apellido_paterno,
                'apellido_materno' => $cliente->apellido_materno,
                'telefono' => $cliente->telefono,
                'tipo_visita' => $cliente->tipo_visita,
            ],
        ]);
    }

    // FUNCIONES PRIVADAS DE VALIDACION Y UTILIDAD

    protected function getAnfitrionesActivos($spaId)
    {
        return Anfitrion::where('spa_id', $spaId)
            ->where('rol', 'anfitrion')
            ->where('activo', true)
            ->whereHas('operativo', function ($q) {
                 $q->whereIn('departamento', ['spa', 'salon de belleza']);
            })
            ->with('operativo')
            ->get();
    }

    private function jsonOrRedirectError(Request $request, $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }
        return back()->withErrors(['error' => $message]);
    }

    private function jsonOrRedirectValidationError(Request $request, $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'errors' => $exception->errors(),
                'message' => 'Errores de validación'
            ], 422);
        }
        throw $exception;
    }

    private function hayConflictoCliente($data, $horaInicio, $horaFin, $spaId)
    {
        return Reservation::where('cliente_id', $data['cliente_id'])
            ->where('fecha', $data['fecha'])
            ->where('estado', 'activa')
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora', [$horaInicio, $horaFin])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaInicio])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaFin]);
            })->exists();
    }

    private function getBusyIntervalsForHost($anfitrionId, $fecha, $excludeReservationId = null)
    {
        $query = Reservation::with(['experiencia', 'anfitrion.operativo'])
            ->where('anfitrion_id', $anfitrionId)
            ->where('fecha', $fecha)
            ->where('estado', 'activa');

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }
        $reservations = $query->get();

        $blockedSlots = BlockedSlot::where('anfitrion_id', $anfitrionId)
            ->where('fecha', $fecha)
            ->get();

        $defaultBreakTime = config('finance.reservations.therapist_break_time', 10);
        $busyIntervals = [];

        foreach ($reservations as $res) {
            if (empty($res->hora) || !$res->experiencia) continue;

            $breakTime = 0;
            if ($res->anfitrion && $res->anfitrion->operativo && strtolower($res->anfitrion->operativo->departamento) === 'spa') {
                $breakTime = $defaultBreakTime;
            }

            $start = \Carbon\Carbon::parse($fecha . ' ' . $res->hora);
            $duration = $res->experiencia->duracion;
            $end = $start->copy()->addMinutes($duration + $breakTime);
            $busyIntervals[] = [
                'start' => $start, 
                'end' => $end,
                'type' => 'reservation',
                'id' => $res->id,
            ];
        }

        foreach ($blockedSlots as $block) {
            if (empty($block->hora)) continue;
            $start = \Carbon\Carbon::parse($fecha . ' ' . $block->hora);
            $duration = $block->duracion;
            $end = $start->copy()->addMinutes($duration);
            $busyIntervals[] = [
                'start' => $start, 
                'end' => $end,
                'type' => 'blocked_slot',
                'id' => $block->id,
            ];
        }

        return $busyIntervals;
    }

    private function hayConflictoAnfitrion($data, $horaInicio, $horaFinDescanso, $spaId, $excludeReservationId = null)
    {
        $busyIntervals = $this->getBusyIntervalsForHost($data['anfitrion_id'], $data['fecha'], $excludeReservationId);

        $newSlotStart = \Carbon\Carbon::parse($data['fecha'] . ' ' . $horaInicio);
        $newSlotEnd = \Carbon\Carbon::parse($data['fecha'] . ' ' . $horaFinDescanso);
        
        if ($newSlotEnd <= $newSlotStart) {
            $newSlotEnd->addDay();
        }

        foreach ($busyIntervals as $busy) {
            if ($newSlotStart < $busy['end'] && $newSlotEnd > $busy['start']) {
                // --- NEW LOGGING ---
                Log::warning('--- CONFLICTO DE ANFITRIÓN DETECTADO ---');
                Log::warning("Anfitrión ID: {$data['anfitrion_id']}");
                Log::warning("Horario solicitado: {$newSlotStart->toDateTimeString()} - {$newSlotEnd->toDateTimeString()}");
                Log::warning("Conflicto con intervalo: {$busy['start']->toDateTimeString()} - {$busy['end']->toDateTimeString()}");
                if (isset($busy['type'])) {
                    Log::warning("Tipo de conflicto: {$busy['type']} (ID: {$busy['id']})");
                }
                // --- FIN LOGGING ---
                return true; // Conflict found
            }
        }

        return false; // No conflicts
    }

    private function hayConflictoCabina($data, $horaInicio, $horaFin, $excludeReservationId = null)
    {
        $query = Reservation::where('cabina_id', $data['cabina_id'])
            ->where('fecha', $data['fecha'])
            ->where('estado', 'activa')
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora', [$horaInicio, $horaFin])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaInicio])
                  ->orWhereRaw("? BETWEEN hora AND ADDTIME(hora, SEC_TO_TIME((SELECT duracion FROM experiences WHERE id = experiencia_id) * 60))", [$horaFin]);
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    private function cabinaPerteneceAlSpa($cabinaId, $spaId)
    {
        return Cabina::where('id', $cabinaId)->where('spa_id', $spaId)->exists();
    }
        
    public function historial(Request $request)
    {
        $spa = Spa::where('nombre', session('current_spa'))->first();
        if (!$spa) {
            return back()->withErrors(['spa' => 'No se encontró el spa actual en sesión.']);
        }

        $query = Reservation::with(['cliente', 'experiencia', 'cabina', 'anfitrion'])
            ->where('spa_id', $spa->id);

        // Filtrar por rango de fechas
        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        // Filtro general (Buscador unificado)
        if ($request->filled('busqueda')) {
            $search = trim($request->input('busqueda'));
            $query->where(function($q) use ($search) {
                $q->whereHas('cliente', function ($q2) use ($search) {
                    $q2->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido_paterno', 'like', "%{$search}%")
                      ->orWhere('apellido_materno', 'like', "%{$search}%");
                })
                ->orWhereHas('experiencia', function ($q2) use ($search) {
                    $q2->where('nombre', 'like', "%{$search}%");
                })
                ->orWhereHas('cabina', function ($q2) use ($search) {
                    $q2->where('nombre', 'like', "%{$search}%");
                })
                ->orWhereHas('anfitrion', function ($q2) use ($search) {
                    $q2->where('nombre_usuario', 'like', "%{$search}%");
                });
            });
        }

        // Estado de pago — normalizar y aplicar si el parámetro existe y no está vacío
        $pagado = $request->input('pagado');
        if (!is_null($pagado) && trim($pagado) !== '') {
            $pagado = strtolower(trim($pagado));
            if ($pagado === 'pagado') {
                $query->where('check_out', true);
            } elseif ($pagado === 'pendiente') {
                $query->where('check_out', false);
            }
        }

        $reservaciones = $query->orderByDesc('fecha')->orderBy('hora')->get();

        return view('reservations.historial.historial', compact('reservaciones'));
    }

    public function getHorariosAnfitrion(Anfitrion $anfitrion, $fecha, Request $request)
    {
        // 1. Obtener los horarios base del anfitrión
        $horariosBase = [];
        if ($anfitrion->horario) {
            $diaSemana = strtolower(\Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd'));
            $diaSemana = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $diaSemana);
            $horariosRaw = $anfitrion->horario->horarios ?? [];
            $normalizados = [];
            foreach ($horariosRaw as $dia => $horas) {
                $diaSinTilde = strtolower(strtr($dia, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']));
                $normalizados[$diaSinTilde] = is_array($horas) ? $horas : [];
            }
            if (isset($normalizados[$diaSemana]) && is_array($normalizados[$diaSemana])) {
                $horariosBase = array_map(fn($h) => \Carbon\Carbon::parse(trim($h))->format('H:i'), $normalizados[$diaSemana]);
                sort($horariosBase);
            }
        }

        if (empty($horariosBase)) return response()->json([]);

        // 2. Obtener reservaciones y bloqueos para crear intervalos ocupados
        $excludeReservationId = $request->query('reservation_id');
        $busyIntervals = $this->getBusyIntervalsForHost($anfitrion->id, $fecha, $excludeReservationId);
        $breakTime = config('finance.reservations.therapist_break_time', 10);

        // 3. Filtrar horarios
        $experienceDuration = 0;
        if ($request->has('experience_id')) {
            $experience = Experience::find($request->input('experience_id'));
            if ($experience) $experienceDuration = $experience->duracion;
        }

        $availableSlots = [];
        foreach ($horariosBase as $hora) {
            $slotStart = \Carbon\Carbon::parse($fecha . ' ' . $hora);
            $isAvailable = true;

            if ($experienceDuration > 0) {
                // Con duración, hacer chequeo de solapamiento completo
                $slotEnd = $slotStart->copy()->addMinutes($experienceDuration + $breakTime);
                foreach ($busyIntervals as $busy) {
                    if ($slotStart < $busy['end'] && $slotEnd > $busy['start']) {
                        $isAvailable = false;
                        break;
                    }
                }
            } else {
                // Sin duración, solo chequear si la hora de inicio está ocupada
                foreach ($busyIntervals as $busy) {
                    if ($slotStart >= $busy['start'] && $slotStart < $busy['end']) {
                        $isAvailable = false;
                        break;
                    }
                }
            }

            if ($isAvailable) {
                $availableSlots[] = $hora;
            }
        }
    
        return response()->json($availableSlots);
    }

    public function getCabinasForExperience(Request $request, Experience $experience)
    {
        $spa = Spa::where('nombre', session('current_spa'))->firstOrFail();
        
        $cabinas = Cabina::where('spa_id', $spa->id)
            ->where('activo', true)
            ->whereJsonContains('clases_actividad', $experience->nombre)
            ->get();

        return response()->json($cabinas);
    }

    public function getExperiencesForAnfitrion(Request $request, Anfitrion $anfitrion)
    {
        $spa = Spa::where('nombre', session('current_spa'))->firstOrFail();
        
        $clases = $anfitrion->operativo->clases_actividad ?? [];

        if (empty($clases)) {
            return response()->json([]);
        }

        $experiences = Experience::where('spa_id', $spa->id)
            ->where('activo', true)
            ->where(function ($query) use ($clases) {
                $query->whereIn('clase', $clases)
                      ->orWhereIn('nombre', $clases);
            })
            ->get()
            ->map(function ($experience) {
                $experience->nombre_con_info = "{$experience->nombre} ({$experience->duracion} min - $" . number_format($experience->precio, 2) . ")";
                return $experience;
            });

        return response()->json($experiences);
    }
}

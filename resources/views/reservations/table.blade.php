{{-- Contenedor principal, agrega clase para tabla pequeña si hay 7 o menos anfitriones --}}
<div class="reservations-container {{ count($anfitrionesDisponibles ?? []) <= 7 ? 'tabla-anfitriones-pequena' : '' }}">
    <table class="reservations-table">
        <thead>
            <tr>
                <th>Hora</th>
                {{-- Encabezados por cada anfitrión disponible --}}
                @foreach ($anfitrionesDisponibles as $anfitrion)
                    @php
                        $esSalon = $anfitrion->operativo->departamento === 'salon de belleza';
                    @endphp
                    <th class="{{ $esSalon ? 'encabezado-salon' : '' }}">
                        {{ $anfitrion->nombre_usuario }} {{ $anfitrion->apellido_paterno }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                // Controla celdas que deben saltarse para reservar rowspan
                $saltos = [];
            @endphp
        
            {{-- Itera cada media hora desde las 8:00 hasta las 20:30 --}}
            @for ($hora = 8; $hora <= 20; $hora++) 
                @for ($minuto = 0; $minuto < 60; $minuto += 30) 
                    @php
                        $horaCompleta = sprintf("%02d:%02d", $hora, $minuto);
                    @endphp
                    <tr>
                        {{-- Columna con la hora actual --}}
                        <td>{{ $horaCompleta }}</td>
        
                        {{-- Celdas para cada anfitrión en esta hora --}}
                        @foreach ($anfitrionesDisponibles as $anfitrion)
                            @php
                                // Controla saltos para rowspan de reservaciones o bloqueos
                                if (!isset($saltos[$anfitrion->id])) {
                                    $saltos[$anfitrion->id] = 0;
                                }
                                if ($saltos[$anfitrion->id] > 0) {
                                    $saltos[$anfitrion->id]--;
                                    continue; // Saltar celda ya cubierta por rowspan
                                }
        
                                // Buscar reserva activa en esta hora para anfitrión
                                $reserva = $reservaciones->first(function ($r) use ($horaCompleta, $anfitrion) {
                                    $inicio = substr($r->hora, 0, 5);
                                    $fin = date('H:i', strtotime($inicio . " +{$r->experiencia->duracion} minutes"));
                                    return $inicio <= $horaCompleta && $horaCompleta < $fin && $r->anfitrion_id == $anfitrion->id;
                                });
        
                                // Buscar bloqueo activo en esta hora para anfitrión
                                $bloqueo = $bloqueos->first(function ($b) use ($horaCompleta, $anfitrion) {
                                    $inicio = substr($b->hora, 0, 5);
                                    $fin = date('H:i', strtotime($inicio . " +{$b->duracion} minutes"));
                                    return $inicio <= $horaCompleta && $horaCompleta < $fin && $b->anfitrion_id == $anfitrion->id;
                                });
                            @endphp
        
                            {{-- Si hay reserva que comienza en esta hora --}}
                            @if ($reserva && $horaCompleta == substr($reserva->hora, 0, 5))
                                @php
                                    $rowspan = max(1, ceil($reserva->experiencia->duracion / 30));
                                    $saltos[$anfitrion->id] = $rowspan - 1; // Saltar siguientes celdas para esta reserva
                                @endphp
                                <td 
                                    data-hora="{{ $horaCompleta }}" 
                                    data-anfitrion="{{ $anfitrion->id }}"
                                    data-reserva-id="{{ $reserva->id }}" 
                                    data-check-in="{{ $reserva->check_in ? 1 : 0 }}"
                                    class="reserva-celda occupied"
                                    rowspan="{{ $rowspan }}"
                                    style="background-color: {{ $reserva->experiencia->color ?? '#ccc' }}"
                                >
                                    <div class="reservation-block text-start">
                                        <div>
                                            {{-- Nombre del cliente y experiencia --}}
                                            {{ $reserva->cliente->nombre }} - {{ $reserva->experiencia->nombre }}
                                        </div>
                                        <div class="text-muted small">{{ $reserva->experiencia->duracion }} min</div>
                                        <div class="mt-1">
                                            {{-- Iconos de check-in o check-out --}}
                                            @if ($reserva->check_out)
                                                <i class="fas fa-check-circle text-success" title="Check-out realizado"></i>
                                            @elseif ($reserva->check_in)
                                                <i class="fas fa-walking text-secondary" title="Check-in realizado"></i>
                                            @endif
                                        </div>
                                    </div>
                                </td>
        
                            {{-- Si hay bloqueo que comienza en esta hora --}}
                            @elseif ($bloqueo && $horaCompleta == substr($bloqueo->hora, 0, 5))
                                @php
                                    $rowspan = max(1, ceil($bloqueo->duracion / 30));
                                    $saltos[$anfitrion->id] = $rowspan - 1; // Saltar celdas bloqueadas
                                @endphp
                                <td 
                                    class="reserva-celda bloqueada" 
                                    data-hora="{{ $horaCompleta }}" 
                                    data-anfitrion="{{ $anfitrion->id }}"
                                    rowspan="{{ $rowspan }}"
                                >
                                    Bloqueado
                                </td>

                            {{-- Si no hay reserva ni bloqueo --}}
                          @else
                           @php
                            $horario = $horariosAnfitriones[$anfitrion->id] ?? [];
                            $diaSemana = strtolower(\Carbon\Carbon::parse($fechaSeleccionada)->locale('es')->isoFormat('dddd'));
                            $diaSemana = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $diaSemana);
                            $horarioDelDia = $horario[$diaSemana] ?? [];
                            $disponible = false;
                            if (empty($horarioDelDia)) {
                             echo '<td class="reserva-celda no-disponible" style="background-color: yellow;">SIN HORARIO (' . $diaSemana . ')</td>';
                             continue;
                            }
                           $match = in_array($horaCompleta, $horarioDelDia);
                           if (!$match) {
                            $listaHoras = implode(',', $horarioDelDia);
                            $debug_message = ['bg' => 'red', 'text' => 'No COINCIDE. Buscando: ' . $horaCompleta . ' en: ' . $listaHoras];
                            $is_debug = true;
                           } else {
                            $disponible = true;
                           }
                          @endphp
                          @if ($disponible)
                              <td
                              data-hora="{{ $horaCompleta }}"
                              data-anfitrion="{{ $anfitrion->id }}"
                              class="reserva-celda available">
                              Disponible
                               </td>
                            @else
                             <td class="reserva-celda no-disponible"></td>
                            @endif
                       @endif 
                    @endforeach 
                    </tr>
                @endfor
            @endfor
        </tbody>
    </table>
</div>

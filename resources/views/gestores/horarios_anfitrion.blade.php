<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/gestores/h_anfitrion_styles.css'])
    @endif
    <title>ELAN SPA & WELLNESS EXPERIENCE</title>
</head>
<body>
    <style>
        .modal {
            display: none; position: fixed; z-index: 1000;
            left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.6);
        }
        .modal-content {
            background-color: #fefefe; margin: 10% auto; padding: 20px;
            border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #ddd; padding-bottom: 10px;
        }
        .modal-footer {
            border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px; text-align: right;
        }
        .close-button {
            color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        .close-button:hover, .close-button:focus { color: black; }
        .horario-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px; max-height: 400px; overflow-y: auto;
        }
        .horario-item { display: flex; align-items: center; }
        td.dia-laboral { background-color: #d1e7dd; cursor: pointer; }
        td.dia-laboral:hover { background-color: #b8d9c3; }
        td:not(.dia-laboral):not([style*="background-color"]) { cursor: pointer; }
        td:not(.dia-laboral):not([style*="background-color"]):hover { background-color: #f5f5f5; }
        .horas-resumen {
            font-size: 0.8em; color: #333; display: block; margin-top: 5px;
        }
        .alert-success {
            padding: 15px; background-color: #d4edda; color: #155724;
            border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;
        }
        .btn-primary, .btn-secondary {
            padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;
            background-color: #007bff; color: white;
        }
        .btn-secondary { background-color: #6c757d; }
    </style>

    <!-- Modal para editar horario del día -->
    <div id="modal-horario-dia" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-horario-titulo"></h4>
                <span class="close-button" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Seleccione las horas de trabajo para este día:</p>
                <div id="horario-checkboxes" class="horario-grid">
                    <!-- Los checkboxes de las horas se generarán aquí con JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="cerrarModal()" class="btn-secondary">Cancelar</button>
                <button onclick="guardarHorarioDia()" class="btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <div class="container py-4">
    <h2>Asignar horarios a {{ $anfitrion->nombre_usuario }}</h2>

    @if (session('mensaje_exito'))
        <div class="alert-success">
            {{ session('mensaje_exito') }}
        </div>
    @endif

    <form id="horario-form" method="POST" action="{{ route('anfitriones.horario.store', $anfitrion->id) }}">
        @csrf

        @php
            use Carbon\Carbon;
            Carbon::setLocale('es');

            $mesSolicitado = request('mes');
            try {
                $fechaActual = $mesSolicitado ? Carbon::createFromFormat('Y-m', $mesSolicitado) : Carbon::now();
            } catch (\Exception $e) {
                $fechaActual = Carbon::now();
            }

            $diasEnMes = $fechaActual->daysInMonth;
            $primerDiaMes = $fechaActual->copy()->startOfMonth();
            $diaSemanaInicio = $primerDiaMes->dayOfWeekIso; // 1 (Lunes) - 7 (Domingo)
            $nombreMes = ucfirst($fechaActual->translatedFormat('F Y'));

            $mesAnterior = $fechaActual->copy()->subMonth()->format('Y-m');
            $mesSiguiente = $fechaActual->copy()->addMonth()->format('Y-m');
        @endphp

        <input type="hidden" name="mes_actual" value="{{ $fechaActual->format('Y-m') }}">

        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <a href="{{ route('anfitriones.horario.edit', ['anfitrion' => $anfitrion->id, 'mes' => $mesAnterior]) }}" 
                   style="text-decoration: none; padding: 5px 10px; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; color: #333;">
                    &lt; Anterior
                </a>
                
                <div style="text-align: center;">
                    <h3 style="margin: 0;">Calendario - {{ $nombreMes }}</h3>
                    <input type="month" value="{{ $fechaActual->format('Y-m') }}" 
                           onchange="window.location.href = '{{ route('anfitriones.horario.edit', $anfitrion->id) }}?mes=' + this.value"
                           style="margin-top: 5px;">
                </div>

                <a href="{{ route('anfitriones.horario.edit', ['anfitrion' => $anfitrion->id, 'mes' => $mesSiguiente]) }}" 
                   style="text-decoration: none; padding: 5px 10px; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; color: #333;">
                    Siguiente &gt;
                </a>
            </div>

            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; text-align: center; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @php $celdas = 0; @endphp
                        {{-- Celdas vacías antes del primer día --}}
                        @for ($i = 1; $i < $diaSemanaInicio; $i++)
                            <td style="background-color: #f0f0f0;"></td>
                            @php $celdas++; @endphp
                        @endfor

                        {{-- Días del mes --}}
                        @for ($dia = 1; $dia <= $diasEnMes; $dia++)
                            @if ($celdas % 7 == 0 && $celdas > 0)
                                </tr><tr>
                            @endif                            
                            @php
                                $fecha = $fechaActual->copy()->day($dia)->format('Y-m-d');
                                $horasDelDia = $horario[$fecha] ?? [];
                                $esLaboral = !empty($horasDelDia);
                            @endphp
                            <td class="{{ $esLaboral ? 'dia-laboral' : '' }}" onclick="abrirModal('{{ $fecha }}')">
                                <div style="font-weight: bold;">{{ $dia }}</div>
                                <div class="horas-resumen" id="resumen-{{ $fecha }}">
                                    @if ($esLaboral)
                                        {{ count($horasDelDia) / 2 }}h
                                    @endif
                                </div>
                            </td>
                            @php $celdas++; @endphp
                        @endfor

                        {{-- Celdas vacías al final --}}
                        @while ($celdas % 7 != 0)
                            <td style="background-color: #f0f0f0;"></td>
                            @php $celdas++; @endphp
                        @endwhile
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="{{ route('anfitriones.index') }}">Cancelar</a>
        </div>
    </form>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- SETUP ---
        window.horarios = @json($horario ?? []);
        const modal = document.getElementById('modal-horario-dia');
        const modalTitulo = document.getElementById('modal-horario-titulo');
        const checkboxesContainer = document.getElementById('horario-checkboxes');
        const form = document.getElementById('horario-form');
        let fechaActualEditando = null;

        // --- MODAL FUNCTIONS ---
        window.abrirModal = function(fecha) {
            fechaActualEditando = fecha;
            const fechaObj = new Date(fecha + 'T00:00:00');
            const fechaFormateada = fechaObj.toLocaleDateString('es-ES', { timeZone: 'UTC', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            modalTitulo.textContent = `Horario para ${fechaFormateada}`;

            checkboxesContainer.innerHTML = ''; // Limpiar
            const horasSeleccionadas = window.horarios[fecha] || [];

            for (let h = 8; h <= 20; h++) {
                for (let m of [0, 30]) {
                    const hora = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    
                    const item = document.createElement('div');
                    item.className = 'horario-item';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = `hora-${hora.replace(':', '')}`;
                    checkbox.value = hora;
                    checkbox.checked = horasSeleccionadas.includes(hora);

                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = hora;
                    
                    item.appendChild(checkbox);
                    item.appendChild(label);
                    checkboxesContainer.appendChild(item);
                }
            }
            modal.style.display = 'block';
        }

        window.cerrarModal = function() {
            modal.style.display = 'none';
            fechaActualEditando = null;
        }

        window.guardarHorarioDia = function() {
            if (!fechaActualEditando) return;

            const horasSeleccionadas = [];
            checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                horasSeleccionadas.push(cb.value);
            });

            if (horasSeleccionadas.length > 0) {
                window.horarios[fechaActualEditando] = horasSeleccionadas.sort();
            } else {
                delete window.horarios[fechaActualEditando];
            }

            actualizarCeldaCalendario(fechaActualEditando);
            autoSave();
            cerrarModal();
        }

        // --- UI & AJAX FUNCTIONS ---
        function actualizarCeldaCalendario(fecha) {
            const celda = document.querySelector(`td[onclick="abrirModal('${fecha}')"]`);
            const resumenDiv = document.getElementById(`resumen-${fecha}`);
            if (!celda || !resumenDiv) return;

            const horas = window.horarios[fecha] || [];
            if (horas.length > 0) {
                celda.classList.add('dia-laboral');
                resumenDiv.textContent = `${horas.length / 2}h`; // Asumiendo intervalos de 30 min
            } else {
                celda.classList.remove('dia-laboral');
                resumenDiv.textContent = '';
            }
        }

        // Guarda el formulario via AJAX
        function autoSave() {
            const formData = new FormData();
            formData.append('horarios', JSON.stringify(window.horarios));
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('mes_actual', form.querySelector('input[name="mes_actual"]').value);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Horario guardado automáticamente.');
                } else {
                    console.error('Error al guardar:', data.message || 'Error desconocido.');
                }
            })
            .catch(error => console.error('Error en guardado automático:', error));
        }

        // --- INITIALIZATION ---
        // Clic fuera del modal para cerrar
        window.onclick = function(event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }
    });

</script>

</html>
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
    <div class="container py-4">
    <h2>Asignar horarios a {{ $anfitrion->nombre_usuario }}</h2>

    <form method="POST" action="{{ route('anfitriones.horario.store', $anfitrion->id) }}">
        @csrf

        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Hora</th>
                    @foreach(['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'] as $dia)
                        <th>{{ ucfirst($dia) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (range(8, 20) as $hora)
                    @foreach ([0, 30] as $min)
                        @php
                            $horaLabel = sprintf('%02d:%02d', $hora, $min);
                        @endphp
                        <tr>
                            <td>{{ $horaLabel }}</td>
                            @foreach(['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'] as $dia)
                                <td style="text-align: center;">
                                    <input 
                                        type="checkbox" 
                                        name="horarios[{{ $dia }}][]" 
                                        value="{{ $horaLabel }}"
                                        {{ in_array($horaLabel, $horario[$dia] ?? []) ? 'checked' : '' }}
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            <button type="submit">Guardar Horario</button>
            <a href="{{ route('anfitriones.index') }}">Cancelar</a>
        </div>
    </form>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        const tabla = document.querySelector('table');

        dias.forEach((dia, diaIndex) => {
            const th = tabla.querySelectorAll('thead th')[diaIndex + 1]; 

            const btn = document.createElement('button');
            btn.textContent = '⮟';
            btn.type = 'button';
            btn.style.marginLeft = '5px';
            btn.title = 'Alternar todas las horas de ' + dia;
            btn.addEventListener('click', () => {
                const checkboxes = tabla.querySelectorAll(`input[name^="horarios[${dia}]"]`);
                const allChecked = [...checkboxes].every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
            });

            th.appendChild(btn);
        });

        tabla.querySelectorAll('tbody tr').forEach(row => {
            const td = row.querySelector('td');
            if (!td) return;

            const btn = document.createElement('button');
            btn.textContent = '⮞';
            btn.type = 'button';
            btn.style.marginLeft = '5px';
            btn.title = 'Alternar esta hora en todos los días';
            btn.addEventListener('click', () => {
                const checkboxes = row.querySelectorAll('input[type="checkbox"]');
                const allChecked = [...checkboxes].every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
            });

            td.appendChild(btn);
        });
    });

</script>

</html>
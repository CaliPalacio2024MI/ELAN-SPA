<div class="tarjeta">
    <div class="contenido-tarjeta">
        <div class="info">
            <h6 class="titulo">{{ $title }}</h6>
            <div class="valor">{{ $value }}</div>
            <i class="fas fa-minus icono text-{{ $class }}"></i>

        </div>
        
        <div class="acciones">
            {{-- Botón de descarga si se proporciona una ruta --}}
            @isset($exportRoute)
                <a href="{{ route('reports.export.tipo', [
                    'tipo' => $exportRoute, 
                    'desde' => request('desde', $fechaInicio), 
                    'hasta' => request('hasta', $fechaFin), 
                    'servicio' => request('servicio'), 
                    'lugar' => $spaId
                ]) }}"
                   class="btn btn-sm btn-outline-success tiny-download"
                   data-export-type="{{ $exportRoute }}"
                   title="Descargar {{ $title }} en Excel">
                    <i class="fas fa-download"></i>
                </a>
            @endisset
        </div>
    </div>
</div>

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
                <a href="{{ route('reports.export.tipo', ['tipo' => $exportRoute, 'desde' => request('desde'), 'hasta' => request('hasta')]) }}"
                   class="btn btn-sm btn-outline-success tiny-download"
                   title="Descargar {{ $title }} en Excel">
                    <i class="fas fa-file-excel"></i>
                </a>
            @endisset
        </div>
    </div>
</div>

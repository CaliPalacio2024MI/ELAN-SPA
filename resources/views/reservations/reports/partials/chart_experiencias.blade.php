<div class="grafica">
    <div class="grafica-header">
    <div class="grafica-titulo">Experiencias más reservadas</div>
    <a href="{{ route('reports.export.tipo', ['tipo' => 'experiencias', 'desde' => request('desde'), 'hasta' => request('hasta')]) }}"
       class="btn btn-sm btn-outline-success"
       title="Descargar en Excel">
        <i class="fas fa-file-excel"></i>
    </a>
</div>

    <div class="grafica-barras">
        @php $max = $topExperiencias->max('total') ?: 1; @endphp
        @foreach ($topExperiencias as $item)
            <div class="barra-item">
                <div class="barra-label">
                    <span>{{ $item->experiencia->nombre ?? 'Sin nombre' }}</span>

                    <span>{{ $item->total }}</span>
                </div>
                <div class="barra-base">
                    <div class="barra-fill" style="width: {{ ($item->total / $max) * 100 }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

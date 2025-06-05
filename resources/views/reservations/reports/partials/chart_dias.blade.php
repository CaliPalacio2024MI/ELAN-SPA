<div class="grafica">
    <div class="grafica-titulo">Días con más reservaciones</div>
    <div class="grafica-barras">
        @foreach ($diasFrecuentes as $item)
            <div class="barra-item">
                <div class="barra-label">
                    <span>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</span>
                    <span>{{ $item->total }}</span>
                </div>
                <div class="barra-base">
                    @php $max = $diasFrecuentes->max('total') ?: 1; @endphp
                    <div class="barra-fill bg-secundario" style="width: {{ ($item->total / $max) * 100 }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

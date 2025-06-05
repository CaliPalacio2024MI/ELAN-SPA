 <div class="grafica" hidden>
    <div class="grafica-titulo">Ganancias por día (última semana)</div>
    <div class="grafica-barras">
        @php $max = $gananciasPorDia->max('total') ?: 1; @endphp
        @foreach ($gananciasPorDia as $item)
            <div class="barra-item">
                <div class="barra-label">
                    <span>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</span>
                    <span>${{ number_format($item->total, 2) }}</span>
                </div>
                <div class="barra-base">
                    <div class="barra-fill bg-verde" style="width: {{ ($item->total / $max) * 100 }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div> 

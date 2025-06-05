{{-- Ejemplo de uso
Desde el controlador debes de definir los valores y las configuraciones:
$articulos = $compra->map(function ($item) {
    return [
        'value' => $item->numero_auxiliar. ' ' . $item->nombre_articulo,
        'key' => $item->numero_auxiliar
    ];
});
$settings = [
    'searchEngine' => 'loose', // 'loose' o 'strict'
    'showAllOnFocus' => true,
    'ignoreAccents' => true
];

En Blade:
<x-auto-complete id="numero_auxiliar" class="form-control" placeholder="Ingrese artículo..." :values="$articulos" :settings="$settings" /> 
--}}

@props([
    'id' => 'autocomplete',
    'class' => '',
    'placeholder' => 'Escriba aquí...',
    'values' => [],
    'settings' => [],
])

<div class="autocomplete">
    <input type="text" id="{{ $id }}" class="{{ $class }}" placeholder="{{ $placeholder }}" required
        data-key="" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="none">
</div>

@once
    @vite('resources/js/autocomplete.js')
@endonce

<script>
    (function() {
        const idInput = document.getElementById(@js($id));
        const values = @js($values);
        const settings = @js($settings);

        function checkAndInitialize() {
            if (window.MundoImperial && typeof MundoImperial.autocomplete === 'function') {
                MundoImperial.autocomplete(idInput, values, settings);
            } else {
                setTimeout(checkAndInitialize, 100);
            }
        }

        document.addEventListener('DOMContentLoaded', checkAndInitialize);
    })();
</script>

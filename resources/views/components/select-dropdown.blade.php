@props([
    'id' => 'select-dropdown',
    'name' => null,
    'class' => '',
    'placeholder' => 'Seleccione una opción...',
    'values' => [],
    'settings' => [],
    'default' => null,
])

<input type="text" id="{{ $id }}" class="{{ $class }}" placeholder="{{ $placeholder }}" name={{ $name ?? $id }} required
        data-key="" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="none">

@once
    @vite('resources/js/select-dropdown.js')
@endonce

<script>
    (function() {
        const idInput = document.getElementById(@js($id));
        const values = @js($values);
        const settings = @js($settings);
        const defaultValue = @js($default);

        function checkAndInitialize() {
            if (window.MundoImperial && typeof MundoImperial.selectDropdown === 'function') {
                MundoImperial.selectDropdown(idInput, values, settings);

                if (defaultValue) {
                    idInput.setValue(defaultValue, true);
                }
            } else {
                setTimeout(checkAndInitialize, 100);
            }
        }

        document.addEventListener('DOMContentLoaded', checkAndInitialize);
    })();
</script>

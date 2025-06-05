document.addEventListener("DOMContentLoaded", function () {
    function autocomplete(inp, arr, options = {}) {
        // Opciones por defecto, agregando ignoreAccents (false por defecto)
        const settings = {
            searchEngine: "loose",           // Buscar en cualquier parte del texto
            showAllOnFocus: true,  // Mostrar todas las opciones al hacer clic
            labelField: "value",   // Campo a mostrar en la UI
            valueField: "key",     // Campo a guardar en data-key
            ignoreAccents: false,  // Si true, se ignoran acentos en la búsqueda
            ...options             // Sobrescribir con opciones personalizadas
        };

        // Función para eliminar acentos usando normalización Unicode
        function removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        // Determinar el tipo de datos y crear funciones de acceso,
        // convirtiendo los valores a string para evitar problemas con números.
        const isObjectArray = arr.length > 0 && typeof arr[0] === 'object';
        const getItemLabel = item => String(isObjectArray ? item[settings.labelField] : item);
        const getItemValue = item => String(isObjectArray ? item[settings.valueField] : item);

        // Variables de estado
        let currentFocus = -1;

        // Asegurar que el input tenga un ID único
        inp.id = inp.id || `autocomplete_${Math.random().toString(36).substr(2, 9)}`;

        // Función para cerrar la lista de sugerencias
        function closeList() {
            const list = document.getElementById(`${inp.id}-autocomplete-list`);
            if (list) list.parentNode.removeChild(list);
        }

        // Función para mostrar las sugerencias
        function showSuggestions(value = "") {
            closeList();
            currentFocus = -1;

            // Crear contenedor de sugerencias
            const container = document.createElement("DIV");
            container.id = `${inp.id}-autocomplete-list`;
            container.className = "autocomplete-items";
            container.dataset.ownerInput = inp.id;
            inp.parentNode.appendChild(container);

            // Normalizar el valor de búsqueda si se ignoran acentos
            let searchValue = value;
            if (settings.ignoreAccents) {
                searchValue = removeAccents(searchValue);
            }
            const normalizedValue = searchValue.toUpperCase();

            // Filtrar y mostrar resultados con conversión a string
            const matchingItems = arr.filter(item => {
                const itemLabelRaw = getItemLabel(item);
                let labelForSearch = itemLabelRaw;
                if (settings.ignoreAccents) {
                    labelForSearch = removeAccents(labelForSearch);
                }
                labelForSearch = labelForSearch.toUpperCase();

                // Si el input está vacío y se quieren mostrar todas las opciones
                if (value === "" && settings.showAllOnFocus) return true;

                return settings.searchEngine === "loose"
                    ? labelForSearch.includes(normalizedValue)
                    : labelForSearch.startsWith(normalizedValue);
            });

            // Crear los elementos de sugerencia
            matchingItems.forEach(item => {
                const itemLabel = getItemLabel(item);
                const itemValue = getItemValue(item);
                const suggestionElement = document.createElement("DIV");
                let labelForHighlight = itemLabel;
                let normalizedItemLabel = itemLabel;
                if (settings.ignoreAccents) {
                    normalizedItemLabel = removeAccents(labelForHighlight);
                }
                normalizedItemLabel = normalizedItemLabel.toUpperCase();

                if (value !== "") {
                    const index = normalizedItemLabel.indexOf(normalizedValue);
                    if (index !== -1) {
                        suggestionElement.innerHTML =
                            itemLabel.substring(0, index) +
                            "<strong>" + itemLabel.substring(index, index + value.length) + "</strong>" +
                            itemLabel.substring(index + value.length);
                    } else {
                        suggestionElement.innerHTML = itemLabel;
                    }
                } else {
                    suggestionElement.innerHTML = itemLabel;
                }

                // Agregar datos ocultos (convierte a string)
                suggestionElement.innerHTML += `<input type='hidden' data-label='${itemLabel}' data-value='${itemValue}'>`;
                suggestionElement.title = itemLabel;
                
                // Manejar la selección por click
                suggestionElement.addEventListener("click", function (e) {
                    e.stopPropagation(); // Evitar que se propague el click y cierre la lista prematuramente
                    const hiddenInput = this.querySelector("input");
                    inp.value = hiddenInput.dataset.label;
                    inp.dataset.key = hiddenInput.dataset.value;
                    closeList();
                    inp.dispatchEvent(new Event('auto-complete-change', { bubbles: true }));
                });

                container.appendChild(suggestionElement);
            });
        }

        // Función para manejar navegación con teclado
        function handleKeyNavigation(keyCode) {
            const x = document.querySelectorAll(`#${inp.id}-autocomplete-list div`);
            if (!x.length) return;

            // Remover estado activo de todos los elementos
            x.forEach(item => item.classList.remove("autocomplete-active"));

            if (keyCode === 40) { // Flecha ABAJO
                currentFocus = (currentFocus + 1) % x.length;
            } else if (keyCode === 38) { // Flecha ARRIBA
                currentFocus = currentFocus <= 0 ? x.length - 1 : currentFocus - 1;
            } else if (keyCode === 13) { // ENTER
                if (currentFocus > -1) {
                    x[currentFocus].click();
                } else if (x.length > 0) {
                    x[0].click();
                }
                return true; // Indica que se procesó Enter correctamente
            }

            if (x[currentFocus]) x[currentFocus].classList.add("autocomplete-active");
            return false;
        }

        // Función para validar el input
        function validateInput() {
            if (inp.value.trim() !== "") {
                const isValid = arr.some(item => getItemLabel(item) === inp.value);
                if (!isValid) {
                    inp.value = "";
                    inp.dataset.key = "";
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            return inp.value !== "" && inp.dataset.key !== "";
        }

        // === Event Listeners ===

        // Mostrar sugerencias al escribir
        inp.addEventListener("input", function(e) {
            showSuggestions(inp.value);
            // Validar automáticamente mientras escribe
            const isValid = arr.some(item => getItemLabel(item) === this.value.trim());
            if (!isValid) {
                this.dataset.key = ""; // Limpiar key si no es válido
            }
        });

        // Mostrar todas las opciones al hacer clic en el input
        if (settings.showAllOnFocus) {
            inp.addEventListener("focus", e => {
                e.stopPropagation();
                showSuggestions(inp.value);
            });
        }

        // Validar al perder el foco y cerrar la lista después de un breve retraso
        inp.addEventListener("blur", () => {
            setTimeout(() => {
                validateInput();
                closeList();
            }, 200);
        });

        // Navegación con teclado
        inp.addEventListener("keydown", e => {
            if ([13, 38, 40].includes(e.keyCode)) { // 13 - Enter, 38 - Arriba, 40 - Abajo
                if (e.keyCode === 13) e.preventDefault();
                handleKeyNavigation(e.keyCode);
            }
        });

        // Cerrar la lista al hacer clic fuera del input o del contenedor de sugerencias
        document.addEventListener("click", e => {
            const container = document.getElementById(`${inp.id}-autocomplete-list`);
            if (e.target === inp || (container && container.contains(e.target))) {
                return;
            }
            closeList();
        });
    }

    window.MundoImperial = window.MundoImperial || {};
    window.MundoImperial.autocomplete = autocomplete;
});

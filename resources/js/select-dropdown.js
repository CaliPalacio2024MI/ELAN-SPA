document.addEventListener("DOMContentLoaded", function () {
    function selectDropdown(inp, arr, options = {}) {
        // Opciones por defecto
        const settings = {
            labelField: "value",   // Campo a mostrar en la UI
            valueField: "key",     // Campo a guardar en data-key
            ...options             // Sobrescribir con opciones personalizadas
        };

        // Variables de estado
        let currentFocus = -1;
        let isOpen = false;

        // Asegurar que el input tenga un ID único
        inp.id = inp.id || `select_${Math.random().toString(36).substr(2, 9)}`;
        
        // Convertir el input en select-like
        inp.readOnly = true; // Evitar escritura
        inp.classList.add("select-dropdown");
        
        // Añadir un indicador visual de dropdown
        const wrapper = document.createElement("DIV");
        wrapper.className = "select-wrapper";
        inp.parentNode.insertBefore(wrapper, inp);
        wrapper.appendChild(inp);
        
        const arrow = document.createElement("DIV");
        arrow.className = "select-arrow";
        arrow.innerHTML = "▼";
        wrapper.appendChild(arrow);

        // Determinar el tipo de datos y crear funciones de acceso
        const isObjectArray = arr.length > 0 && typeof arr[0] === 'object';
        const getItemLabel = item => String(isObjectArray ? item[settings.labelField] : item);
        const getItemValue = item => String(isObjectArray ? item[settings.valueField] : item);

        // Función para cerrar la lista de opciones
        function closeList() {
            const list = document.getElementById(`${inp.id}-select-list`);
            if (list) list.parentNode.removeChild(list);
            isOpen = false;
            arrow.innerHTML = "▼"; // Flecha hacia abajo cuando cerrado
        }

        // Función para mostrar las opciones
        function showOptions() {
            closeList();
            currentFocus = -1;
            isOpen = true;
            arrow.innerHTML = "▲"; // Flecha hacia arriba cuando abierto

            // Crear contenedor de opciones
            const container = document.createElement("DIV");
            container.id = `${inp.id}-select-list`;
            container.className = "select-items";
            wrapper.appendChild(container);

            // Crear los elementos de opciones - siempre mostrar todas
            arr.forEach(item => {
                const itemLabel = getItemLabel(item);
                const itemValue = getItemValue(item);
                const optionElement = document.createElement("DIV");
                
                optionElement.innerHTML = itemLabel;
                optionElement.title = itemLabel;
                
                // Resaltar la opción actualmente seleccionada
                if (inp.value === itemLabel) {
                    optionElement.classList.add("selected");
                }
                
                // Manejar la selección por click
                optionElement.addEventListener("click", function (e) {
                    e.stopPropagation();
                    inp.value = itemLabel;
                    inp.dataset.key = itemValue;
                    closeList();
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                });

                container.appendChild(optionElement);
            });
        }

        // Función para manejar navegación con teclado
        function handleKeyNavigation(keyCode) {
            const x = document.querySelectorAll(`#${inp.id}-select-list div`);
            if (!x.length) return;

            // Remover estado activo de todos los elementos
            x.forEach(item => item.classList.remove("select-active"));

            if (keyCode === 40) { // Flecha ABAJO
                currentFocus = (currentFocus + 1) % x.length;
            } else if (keyCode === 38) { // Flecha ARRIBA
                currentFocus = currentFocus <= 0 ? x.length - 1 : currentFocus - 1;
            } else if (keyCode === 13 || keyCode === 32) { // ENTER o ESPACIO
                if (currentFocus > -1) {
                    x[currentFocus].click();
                } else if (x.length > 0) {
                    x[0].click();
                }
                return true;
            } else if (keyCode === 27) { // ESC
                closeList();
                return true;
            }

            if (x[currentFocus]) {
                x[currentFocus].classList.add("select-active");
                // Hacer scroll para que la opción sea visible
                x[currentFocus].scrollIntoView({ block: "nearest" });
            }
            return false;
        }

        // === Event Listeners ===

        // Abrir/cerrar dropdown al hacer clic en el input
        inp.addEventListener("click", function(e) {
            e.stopPropagation();
            if (isOpen) {
                closeList();
            } else {
                showOptions();
            }
        });

        // También permitir clic en la flecha para abrir/cerrar
        arrow.addEventListener("click", function(e) {
            e.stopPropagation();
            if (isOpen) {
                closeList();
            } else {
                showOptions();
            }
        });

        // Navegación con teclado
        inp.addEventListener("keydown", e => {
            // Si el dropdown está cerrado, abrirlo con flecha abajo/arriba/espacio
            if (!isOpen && [38, 40, 32].includes(e.keyCode)) {
                e.preventDefault();
                showOptions();
                return;
            }
            
            if ([13, 27, 32, 38, 40].includes(e.keyCode) && isOpen) {
                e.preventDefault();
                handleKeyNavigation(e.keyCode);
            }
        });

        // Cerrar la lista al hacer clic fuera del input o del contenedor de sugerencias
        document.addEventListener("click", e => {
            if (isOpen) closeList();
        });

        // Método para establecer valor programáticamente
        function setValue(value, silent = false) {
            const matchingItem = arr.find(item => getItemValue(item) === value);
            if (matchingItem) {
                inp.value = getItemLabel(matchingItem);
                inp.dataset.key = value;
                if (!silent) {
                    inp.dispatchEvent(new Event('select-change', { bubbles: true }));
                }
                return true;
            }
            return false;
        }

        // Exponer método para establecer valor
        inp.setValue = setValue;
    }

    window.MundoImperial = window.MundoImperial || {};
    window.MundoImperial.selectDropdown = selectDropdown;
});
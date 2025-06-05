export const Dom = {
    // Muestra u oculta un elemento según el valor booleano `show`
    toggleDisplay(element, show = true) {
        if (!element) return;
        element.style.display = show ? "block" : "none";
    },

    // Establece el valor de un input por su id
    setValue(id, value = "") {
        const el = document.getElementById(id);
        if (el) el.value = value;
    },

    // Obtiene la opción seleccionada de un select por su id
    getSelectedOption(selectElementId) {
        const select = document.getElementById(selectElementId);
        if (!select) return null;
        return select.options[select.selectedIndex] || null;
    },

    // Establece el texto interno (textContent) de un elemento por su id
    setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    },

    // Resetea las opciones de un select y las rellena con un array de opciones
    // Cada opción debe tener forma { value: "", label: "" }
    resetSelectOptions(selectId, options = [], placeholder = "Selecciona una opción") {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = "";

        // Opción por defecto deshabilitada y seleccionada
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);

        // Agrega las opciones pasadas
        options.forEach(opt => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.textContent = opt.label;
            select.appendChild(option);
        });
    },

    // Crea un nuevo elemento HTML con atributos y contenido opcional
    createElement(tag, attributes = {}, innerHTML = "") {
        const el = document.createElement(tag);
        for (const attr in attributes) {
            el.setAttribute(attr, attributes[attr]);
        }
        el.innerHTML = innerHTML;
        return el;
    }
};

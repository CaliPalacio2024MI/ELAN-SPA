import { ModalAlerts } from '@/utils/modalAlerts.js';
window.ModalAlerts = ModalAlerts;
import { Alerts } from '@/utils/alerts.js';
window.Alerts = Alerts;

class ExperienciasApp {
    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initEditarExperiencia();
            this.initEliminarExperiencia();
            this.initToggleEstado();
            this.initAlternarClase();
            this.initSidebarHover();
        });
    }

    // Carga datos al formulario de edición y alterna entre input/select para "clase"
    initEditarExperiencia() {
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const clase = button.dataset.clase;
                const select = document.getElementById("edit_clase_select");
                const input = document.getElementById("edit_clase_input");

                const form = document.getElementById('editExperienceForm');
                if (form && id) {
                    form.setAttribute('action', '/experiences/' + id);
                } else {
                    console.error("⚠️ No se pudo establecer el action en el formulario.");
                }

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = button.dataset.nombre;
                document.getElementById('edit_duracion').value = button.dataset.duracion;
                document.getElementById('edit_precio').value = button.dataset.precio;
                document.getElementById('edit_color').value = button.dataset.color || '#000000';
                document.getElementById('edit_descripcion').value = button.dataset.descripcion;
                document.getElementById('edit_activo').value = button.dataset.activo;

                if ([...select.options].some(option => option.value === clase)) {
                    select.value = clase;
                    document.getElementById("edit_toggleClaseSelect").click();
                } else {
                    input.value = clase;
                    document.getElementById("edit_toggleClaseInput").click();
                }
            });
        });
    }

    // Confirmar eliminación con alerta personalizada
    initEliminarExperiencia() {
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const form = button.closest('form');

                Alerts.confirmDelete(() => form.submit());
            });
        });
    }

    // Cambiar estado activo/inactivo con fetch y actualizar UI
    initToggleEstado() {
        document.querySelectorAll('.toggle-estado').forEach(badge => {
            badge.addEventListener('click', () => {
                const id = badge.dataset.id;

                fetch(`/experiences/${id}/toggle-estado`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        badge.textContent = data.activo ? 'Activo' : 'Inactivo';
                        badge.classList.toggle('bg-success', data.activo);
                        badge.classList.toggle('bg-danger', !data.activo);
                    }
                })
                .catch(console.error);
            });
        });
    }

    // Alternar visibilidad y atributos name entre input y select para campo "clase"
    initAlternarClase() {
        const toggleInput = () => {
            document.getElementById("clase-select-container").classList.add("d-none");
            document.getElementById("clase-input-container").classList.remove("d-none");
            document.getElementById("clase_input").setAttribute("name", "clase");
            document.getElementById("clase_select").removeAttribute("name");
        };

        const toggleSelect = () => {
            document.getElementById("clase-input-container").classList.add("d-none");
            document.getElementById("clase-select-container").classList.remove("d-none");
            document.getElementById("clase_select").setAttribute("name", "clase");
            document.getElementById("clase_input").removeAttribute("name");
        };

        document.getElementById("toggleClaseInput")?.addEventListener("click", toggleInput);
        document.getElementById("toggleClaseSelect")?.addEventListener("click", toggleSelect);
        document.getElementById("edit_toggleClaseInput")?.addEventListener("click", () => {
            document.getElementById("edit-clase-select-container").classList.add("d-none");
            document.getElementById("edit-clase-input-container").classList.remove("d-none");
            document.getElementById("edit_clase_input").setAttribute("name", "clase");
            document.getElementById("edit_clase_select").removeAttribute("name");
        });
        document.getElementById("edit_toggleClaseSelect")?.addEventListener("click", () => {
            document.getElementById("edit-clase-input-container").classList.add("d-none");
            document.getElementById("edit-clase-select-container").classList.remove("d-none");
            document.getElementById("edit_clase_select").setAttribute("name", "clase");
            document.getElementById("edit_clase_input").removeAttribute("name");
        });

        // Ajustar atributo name justo antes de enviar formulario
        document.getElementById("experienceForm")?.addEventListener("submit", () => {
            const selectContainer = document.getElementById("clase-select-container");
            const inputContainer = document.getElementById("clase-input-container");
            const select = document.getElementById("clase_select");
            const input = document.getElementById("clase_input");

            if (inputContainer && !inputContainer.classList.contains("d-none")) {
                select.removeAttribute("name");
                input.setAttribute("name", "clase");
            } else {
                input.removeAttribute("name");
                select.setAttribute("name", "clase");
            }
        });
    }

    // Hover en sidebar para toggle clase global en body
    initSidebarHover() {
        const sidebar = document.querySelector('.sidebar');
        sidebar?.addEventListener('mouseenter', () => {
            document.body.classList.add('sidebar-hover');
        });
        sidebar?.addEventListener('mouseleave', () => {
            document.body.classList.remove('sidebar-hover');
        });
    }
}

new ExperienciasApp();

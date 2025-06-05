import { ModalAlerts } from '@/utils/modalAlerts.js';
window.ModalAlerts = ModalAlerts;
import { Alerts } from '@/utils/alerts.js';
window.Alerts = Alerts;

class CabinasApp {
    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initAbrirFormularioCreacion();
            this.initAbrirFormularioEdicion();
            this.initEliminarCabina();
            this.initToggleEstado();
            this.initSidebarHover();
            this.reabrirModalSiErrores();
        });
    }

    // Abrir formulario creación y resetear estado
    initAbrirFormularioCreacion() {
        const createBtn = document.querySelector('button[data-bs-target="#cabinaModal"]');
        if (createBtn) {
            createBtn.addEventListener('click', () => {
                const form = document.querySelector('#cabinaModal form');
                form.reset();
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback, .text-danger').forEach(el => el.innerHTML = '');
            });
        }
    }

    // Abrir formulario edición y cargar datos
    initAbrirFormularioEdicion() {
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const nombre = button.dataset.nombre;
                const clase = button.dataset.clase;
                const activo = button.dataset.activo;
                const clasesActividad = JSON.parse(button.dataset.clasesActividad || '[]');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre_cabina').value = nombre;
                document.getElementById('edit_clase_cabina').value = clase;
                document.getElementById('edit_activo').value = activo;

                // Desmarcar todos los checkboxes de clases
                document.querySelectorAll('#editCabinaModal input[name="clases_actividad[]"]').forEach(cb => cb.checked = false);

                // Marcar checkboxes según clasesActividad
                clasesActividad.forEach(valor => {
                    const checkbox = document.querySelector(`#editCabinaModal input[name="clases_actividad[]"][value="${valor}"]`);
                    if (checkbox) checkbox.checked = true;
                });

                const form = document.getElementById('editCabinaForm');
                form.setAttribute('action', '/cabinas/' + id);
            });
        });
    }

    // Confirmar y eliminar cabina
    initEliminarCabina() {
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const form = button.closest('form');

                Alerts.confirmDelete(() => {
                    form.submit();
                });
            });
        });
    }

    // Toggle estado activo/inactivo vía fetch
    initToggleEstado() {
        document.querySelectorAll('.toggle-estado').forEach(badge => {
            badge.addEventListener('click', () => {
                const cabinaId = badge.dataset.id;

                fetch(`/cabinas/${cabinaId}/toggle-estado`, {
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

    // Ajustar clases para efecto hover sidebar
    initSidebarHover() {
        const sidebar = document.querySelector('.sidebar');
        sidebar?.addEventListener('mouseenter', () => document.body.classList.add('sidebar-hover'));
        sidebar?.addEventListener('mouseleave', () => document.body.classList.remove('sidebar-hover'));
    }

    // Reabrir modal si existen errores de validación
    reabrirModalSiErrores() {
        if (document.querySelector('#cabinaModal .is-invalid')) {
            new bootstrap.Modal(document.getElementById('cabinaModal')).show();
        }
        if (document.querySelector('#editCabinaModal .is-invalid')) {
            new bootstrap.Modal(document.getElementById('editCabinaModal')).show();
        }
    }
}

new CabinasApp();

import { ModalAlerts } from '@/utils/modalAlerts.js';
window.ModalAlerts = ModalAlerts;
import { Alerts } from '@/utils/alerts.js';
window.Alerts = Alerts;

class ClientesApp {
    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initAbrirFormularioCreacion();
            this.initAbrirFormularioEdicion();
            this.initEliminarCliente();
            this.fixModalBackdrop();
        });
    }

    // Abrir formulario creación y resetear
    initAbrirFormularioCreacion() {
        const createBtn = document.querySelector('button[data-bs-target="#clienteModal"]');
        if (createBtn) {
            createBtn.addEventListener('click', () => {
                const form = document.querySelector('#clienteModal form');
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
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = button.dataset.nombre;
                document.getElementById('edit_apellido_paterno').value = button.dataset.apellido_paterno;
                document.getElementById('edit_apellido_materno').value = button.dataset.apellido_materno ?? '';
                document.getElementById('edit_correo').value = button.dataset.correo ?? '';
                document.getElementById('edit_telefono').value = button.dataset.telefono;
                document.getElementById('edit_tipo_visita').value = button.dataset.tipo_visita;

                document.getElementById('editclienteForm').setAttribute('action', '/cliente/' + id);
            });
        });
    }

    // Confirmar y eliminar cliente
    initEliminarCliente() {
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const form = button.closest('form');

                Alerts.confirmDelete(() => form.submit());
            });
        });
    }

    // Corregir problema de backdrop al cerrar modales
    fixModalBackdrop() {
        const clienteModal = document.getElementById('clienteModal');
        const editclienteModal = document.getElementById('editclienteModal');

        [clienteModal, editclienteModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('hidden.bs.modal', () => {
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                });
            }
        });
    }
}

new ClientesApp();

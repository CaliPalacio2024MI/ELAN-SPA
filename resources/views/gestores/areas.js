import { Alerts } from '@/utils/alerts.js';

class AreasApp {
    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initEliminarArea();
            this.initAbrirFormularioEdicion();
            this.initToggleEstado();
            this.initSidebarHover();
        });
    }

    // Abrir formulario edición y cargar datos
    initAbrirFormularioEdicion() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const departamento = this.dataset.departamento;
                const form = document.getElementById('editDepartamentoForm');
                form.action = `/areas/${encodeURIComponent(departamento)}`;
                document.getElementById('edit_nombre_departamento').value = departamento;
            });
        });
    }

    // Confirmar y eliminar área
    initEliminarArea() {
        // Busca todos los botones de submit con la clase btn-danger dentro de un formulario
        document.querySelectorAll('form button.btn-danger[type="submit"]').forEach(button => {
            // Asegurarse de que es un formulario de eliminación
            if (!button.closest('form').querySelector('input[name="_method"][value="DELETE"]')) return;
            button.addEventListener('click', event => {
                // Previene el envío inmediato del formulario
                event.preventDefault();
                const form = button.closest('form');

                // Llama a la alerta de confirmación. Si el usuario confirma, se ejecuta el callback.
                Alerts.confirmDelete(() => {
                    form.submit(); // Envía el formulario
                });
            });
        });
    }

    // Toggle estado activo/inactivo vía fetch
    initToggleEstado() {
        document.querySelectorAll('.toggle-estado').forEach(el => {
            el.addEventListener('click', function () {
                const departamento = this.dataset.departamento;
                const url = `/areas/${encodeURIComponent(departamento)}/toggle`;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Recargar la página para reflejar el cambio de estado
                            window.location.reload();
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
}

new AreasApp();
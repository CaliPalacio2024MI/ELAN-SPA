class AnfitrionesApp {
    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.avisoModala();
            this.configurarModales();
            this.configurarFormularioCreacion();
            this.configurarFormularioEdicion();
            this.initEliminarAnfitrion();
            this.configurarToggleEstado();
            this.configurarCheckboxAccesos();
            this.configurarResetFormularios();
            this.ajustarConSidebar();

            if (window.mensaje_exito) {
                showSimpleAlert(window.mensaje_exito, 'success');
            }
        });
    }

    // Define alerta simple reutilizable
    avisoModala() {
        window.showSimpleAlert = function (message, type = 'success', onConfirm = null, titulo = 'Aviso') {
            const container = document.getElementById('simpleAlertContainer');
            if (!container) return;

            container.innerHTML = '';

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.role = 'alert';

            const header = document.createElement('div');
            header.className = 'alert-header';
            header.innerHTML = `
                <span>${titulo}</span>
                <button class="btn-close-alert" title="Cerrar aviso">×</button>
            `;

            const body = document.createElement('div');
            body.className = 'alert-body';
            body.textContent = message;

            if (typeof onConfirm === 'function') {
                const btn = document.createElement('button');
                btn.className = 'btn-confirm';
                btn.textContent = 'Confirmar';
                btn.onclick = () => {
                    onConfirm();
                    alert.remove();
                };
                body.appendChild(document.createElement('br'));
                body.appendChild(btn);
            }

            header.querySelector('.btn-close-alert').onclick = () => alert.remove();

            alert.appendChild(header);
            alert.appendChild(body);
            container.appendChild(alert);

            if (!onConfirm) {
                setTimeout(() => {
                    alert.classList.add('hide');
                    setTimeout(() => alert.remove(), 300);
                }, 4000);
            }
        };
    }

    // Confirmación antes de eliminar anfitrión
    initEliminarAnfitrion() {
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const form = button.closest('form');

                showSimpleAlert(
                    '¿Estás seguro de que deseas eliminar este anfitrión?',
                    'warning',
                    () => form.submit(),
                    'Confirmar eliminación'
                );
            });
        });
    }

    // Mostrar modales si hay errores de validación
    configurarModales() {
        if (window.errors?.create) {
            const modal = document.getElementById('anfitrionModal');
            if (modal) new bootstrap.Modal(modal).show();
        }

        if (window.errors?.edit) {
            const modal = document.getElementById('editAnfitrionModal');
            if (modal) new bootstrap.Modal(modal).show();
        }
    }

    // Reset y limpiar formulario creación al abrir modal
    configurarFormularioCreacion() {
        const btn = document.querySelector('button[data-bs-target="#anfitrionModal"]');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const form = document.querySelector('#anfitrionModal form');
            form?.reset();
            this.limpiarErrores(form);
            document.getElementById('accesosContainer').style.display = 'none';
        });
    }

    // Cargar datos en formulario edición al abrir
    configurarFormularioEdicion() {
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const accesos = JSON.parse(button.dataset.accesos || "[]");
                const clases = JSON.parse(button.dataset.clases || "[]");
                const clasesSelect = document.getElementById('edit_clases_actividad');

                const form = document.getElementById('editAnfitrionForm');
                form.setAttribute('action', `/anfitriones/${id}`);

                // Rellenar campos
                document.getElementById('edit_RFC').value = button.dataset.rfc;
                document.getElementById('edit_nombre_usuario').value = button.dataset.nombre;
                document.getElementById('edit_apellido_paterno').value = button.dataset.apellidoPaterno;
                document.getElementById('edit_apellido_materno').value = button.dataset.apellidoMaterno;
                document.getElementById('edit_rol').value = button.dataset.rol;
                document.getElementById('edit_departamento').value = button.dataset.departamento;
                document.getElementById('edit_activo').value = button.dataset.activo;

                // Checkbox clases actividad
                document.querySelectorAll('input[name="clases_actividad[]"]').forEach(checkbox => {
                    checkbox.checked = clases.includes(checkbox.value);
                });

                // Select múltiple clases actividad
                if (clasesSelect && Array.isArray(clases)) {
                    [...clasesSelect.options].forEach(opt => {
                        opt.selected = clases.includes(opt.value);
                    });
                }

                // Mostrar y marcar accesos
                const accesosContainer = document.getElementById('edit_accesosContainer');
                const checkboxEdit = document.getElementById('edit_accesosCheckbox');
                if (Array.isArray(accesos)) {
                    checkboxEdit.checked = true;
                    accesosContainer.style.display = 'block';
                    accesosContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = accesos.includes(parseInt(cb.value));
                    });
                }
            });
        });
    }

    // Toggle estado activo/inactivo del anfitrión con fetch
    configurarToggleEstado() {
        document.querySelectorAll('.toggle-estado').forEach(badge => {
            badge.addEventListener('click', () => {
                const id = badge.dataset.id;
                fetch(`/anfitriones/${id}/toggle-estado`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        badge.textContent = data.activo ? 'Activo' : 'Inactivo';
                        badge.classList.toggle('bg-success', data.activo);
                        badge.classList.toggle('bg-danger', !data.activo);
                    }
                })
                .catch(error => console.error('Error al actualizar estado:', error));
            });
        });
    }

    // Mostrar/Ocultar contenedores según checkbox accesos
    configurarCheckboxAccesos() {
        const toggle = (checkboxId, containerId) => {
            const checkbox = document.getElementById(checkboxId);
            const container = document.getElementById(containerId);
            if (checkbox && container) {
                checkbox.addEventListener('change', () => {
                    container.style.display = checkbox.checked ? 'block' : 'none';
                });
            }
        };

        toggle('accesosCheckbox', 'accesosContainer');
        toggle('edit_accesosCheckbox', 'edit_accesosContainer');
    }

    // Reset y limpiar errores al cerrar modal
    configurarResetFormularios() {
        const resetForm = (modalId, formSelector) => {
            const modal = document.getElementById(modalId);
            modal?.addEventListener('hidden.bs.modal', () => {
                const form = modal.querySelector(formSelector);
                form.reset();
                this.limpiarErrores(form);
                const accesosContainer = form.querySelector('[id$="accesosContainer"]');
                if (accesosContainer) accesosContainer.style.display = 'none';
                const checkbox = form.querySelector('[id$="accesosCheckbox"]');
                if (checkbox) checkbox.checked = false;
            });
        };

        resetForm('anfitrionModal', 'form');
        resetForm('editAnfitrionModal', 'form');
    }

    limpiarErrores(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback, .text-danger').forEach(el => el.innerHTML = '');
    }

    // Ajustar clases CSS para sidebar hover
    ajustarConSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const container = document.querySelector('.table-container');

        if (!sidebar || !container) return;

        sidebar.addEventListener('mouseenter', () => {
            container.classList.add('sidebar-hover');
            document.body.classList.add('sidebar-hover');
        });

        sidebar.addEventListener('mouseleave', () => {
            container.classList.remove('sidebar-hover');
            document.body.classList.remove('sidebar-hover');
        });
    }
}

new AnfitrionesApp();

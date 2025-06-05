export const ModalAlerts = {
  // Muestra un modal de alerta personalizado con opciones configurables
  show(message, options = {}) {
    const modalEl = document.getElementById('alertModal');
    const modalInstance = new bootstrap.Modal(modalEl);
    const titleEl = document.getElementById('alertModalLabel');
    const contentEl = document.getElementById('alertModalContent');
    const confirmBtn = document.getElementById('alertModalConfirmBtn');

    // Configuración por defecto y personalizada
    const {
      title = 'Aviso',
      type = 'info', // success, error, warning, info
      autoClose = true, // tiempo en ms o true para auto cerrar, false para no cerrar
      confirmButton = null // { label: "", action: function }
    } = options;

    // Limpia clases previas y asigna clase según tipo de alerta
    modalEl.classList.remove('alert-success', 'alert-error', 'alert-warning', 'alert-info');
    modalEl.classList.add(`alert-${type}`);

    // Asigna título y contenido HTML del modal
    titleEl.textContent = title;
    contentEl.innerHTML = message || 'Operación realizada correctamente.';

    const hasConfirmAction = confirmButton && typeof confirmButton.action === 'function';

    // Determina si se debe mostrar el botón de confirmación
    const shouldShowConfirmBtn =
      type === 'error' ||
      (type === 'warning' && hasConfirmAction) ||
      hasConfirmAction;

    if (shouldShowConfirmBtn) {
      // Muestra botón confirmación con acción y etiqueta
      confirmBtn.classList.remove('d-none');
      confirmBtn.style.display = 'inline-block';
      confirmBtn.textContent = confirmButton?.label || "Confirmar";
      confirmBtn.onclick = () => {
        confirmButton?.action?.();
        modalInstance.hide();
      };
    } else {
      // Oculta botón confirmación y limpia evento onclick
      confirmBtn.classList.add('d-none');
      confirmBtn.style.display = 'none';
      confirmBtn.onclick = null;

      // Si autoClose está habilitado, cierra modal después de delay (default 4000 ms)
      if (autoClose !== false) {
        const delay = typeof autoClose === "number" ? autoClose : 4000;
        setTimeout(() => {
          const instance = bootstrap.Modal.getInstance(modalEl);
          if (instance) instance.hide();
        }, delay);
      }
    }

    // Oculta cualquier otro modal de alerta visible para evitar solapamientos
    document.querySelectorAll('.modal.show.alert-modal').forEach(modal => {
      bootstrap.Modal.getInstance(modal)?.hide();
    });

    // Muestra el modal actual
    modalInstance.show();
  }
};

<div class="modal fade custom-alert-modal alert-success" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title fs-5" id="alertModalLabel">Aviso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="alertModalContent"></div>
      </div>
      <div class="modal-footer">
        <button type="button" id="alertModalConfirmBtn" class="btn custom-confirm-btn d-none">Confirmar</button>
      </div>
    </div>
  </div>
</div>



<script>
  window.AlertModal = {
    show: function (options = {}) {
      const modalEl = document.getElementById('alertModal');
      const modal = new bootstrap.Modal(modalEl);
      const titleEl = document.getElementById('alertModalLabel');
      const contentEl = document.getElementById('alertModalContent');
      const confirmBtn = document.getElementById('alertModalConfirmBtn');

      // Limpiar clases previas
      modalEl.classList.remove('alert-success', 'alert-error', 'alert-warning', 'alert-info');
      const tipo = options.type || 'info';
      modalEl.classList.add('alert-' + tipo);

      // Actualizar contenido
      titleEl.textContent = options.title || 'Aviso';
      contentEl.innerHTML = options.message || '';

      // Mostrar u ocultar el botón Confirmar
      if (typeof options.onConfirm === 'function') {
        confirmBtn.classList.remove('d-none');
        confirmBtn.textContent = options.confirmLabel || 'Confirmar';
        confirmBtn.onclick = () => {
          confirmBtn.disabled = true;
          options.onConfirm();
          modal.hide();
          confirmBtn.disabled = false;
        };
      } else {
        confirmBtn.classList.add('d-none');
        confirmBtn.onclick = null;

        const autoCloseTime = options.autoClose ?? 4000;
        setTimeout(() => {
          const instance = bootstrap.Modal.getInstance(modalEl);
          if (instance) instance.hide();
        }, autoCloseTime);
      }

      modal.show();
    }
  };
</script>

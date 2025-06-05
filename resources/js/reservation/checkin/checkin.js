// resources/js/reservation/checkin.js

// Inicialización al cargar el DOM
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("canvasFirma");
  const ctx = canvas.getContext("2d");
  let isDrawing = false;
  let campoDestino = null;
  const firmaModal = new bootstrap.Modal(document.getElementById("firmaModal"));

  // Limpia el canvas para una nueva firma
  const limpiarCanvas = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.beginPath();
  };

  // Dibuja en el canvas mientras se arrastra el mouse
  const dibujar = (e) => {
    if (!isDrawing) return;
    const rect = canvas.getBoundingClientRect();
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
  };

  // Eventos para iniciar/detener dibujo en canvas
  canvas.addEventListener("mousedown", () => isDrawing = true);
  canvas.addEventListener("mouseup", () => {
    isDrawing = false;
    ctx.beginPath();
  });
  canvas.addEventListener("mouseleave", () => isDrawing = false);
  canvas.addEventListener("mousemove", dibujar);

  // Botones que abren el modal para firmar y asignan el input donde se guarda la imagen
  document.querySelectorAll("[data-firma-target]").forEach(btn => {
    btn.addEventListener("click", () => {
      campoDestino = document.getElementById(btn.dataset.firmaTarget);
      limpiarCanvas();
      firmaModal.show();
    });
  });

  // Botón para limpiar el canvas (borrar firma)
  document.getElementById("btnLimpiarFirma").addEventListener("click", limpiarCanvas);

  // Botón para guardar la firma: convierte el canvas en imagen base64 y la asigna al input oculto
  document.getElementById("btnGuardarFirma").addEventListener("click", () => {
    if (!campoDestino) return;

    const imagen = canvas.toDataURL("image/png");
    campoDestino.value = imagen;
    campoDestino.style.display = "none";

    const img = document.createElement("img");
    img.src = imagen;
    img.classList.add("img-firma");

    const boton = campoDestino.parentElement.querySelector("button[data-firma-target]");
    if (boton) boton.remove();

    campoDestino.parentElement.appendChild(img);
    firmaModal.hide();
  });
});

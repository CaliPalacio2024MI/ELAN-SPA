// resources/js/reservation/modules/formHandler.js

import { TableLoader } from './tableLoader.js';
import { ModalHandler } from './modalHandler.js';
import { Alerts } from '@/utils/alerts.js';
import { ModalAlerts } from '@/utils/modalAlerts.js';

export const ReservationFormHandler = {
    // Normaliza cadenas: quita tildes, trim y pasa a minúsculas
    normalizeString(s) {
        return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    },
    // Comprueba si una cabina (con sus 'clases_actividad' que contienen nombres de experiencias)
    // es compatible con una clase requerida (nombre general de la experiencia)
    cabinaSoportaClase(cabina, claseRequerida) {
        if (!cabina || !Array.isArray(cabina.clases_actividad)) return false;
        const experiencias = window.ReservasConfig?.experiencias || [];
        const claseNorm = ReservationFormHandler.normalizeString(claseRequerida);

        return cabina.clases_actividad.some(tipoNombre => {
            const tipoNorm = ReservationFormHandler.normalizeString(tipoNombre);
            // Buscar experiencia por nombre (tipo) y comparar su clase
            const exp = experiencias.find(e => ReservationFormHandler.normalizeString(e.nombre) === tipoNorm);
            if (exp && exp.clase) {
                return ReservationFormHandler.normalizeString(exp.clase) === claseNorm;
            }
            // Si no existe la experiencia por nombre, también comparar directamente con la clase
            return tipoNorm === claseNorm;
        });
    },
    // Inicializa eventos y configuraciones del formulario
    init() {
        const form = document.getElementById("reservationForm");
        if (!form) return;

        form.addEventListener("submit", this.handleSubmit);
        this.bindInputs();
        this.setupBuscarCliente();
        this.setupGrupoReservas();
        this.setupFiltradoPorExperienciaYFecha();
        this.setupActualizarHoras();
    },

    // Maneja el envío del formulario (crear o editar reservación)
    handleSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const reservaId = document.getElementById("reserva_id").value;
        const grupo = [];
        const errores = [];

        // Recopila datos de la reservación principal
        const dataPrincipal = ReservationFormHandler.collectReservaData(form);
        dataPrincipal.index = 1;

        if (!ReservationFormHandler.validarPrincipal(dataPrincipal, errores)) {
            Alerts.validationErrors(errores);
            return;
        }

        grupo.push(dataPrincipal);
        // Recopila datos de acompañantes (si los hay)
        ReservationFormHandler.collectAcompanantes(grupo, errores);

        if (errores.length > 0) {
            Alerts.validationErrors(errores);
            return;
        }

        // Envía los datos según sea creación o edición
        reservaId ? ReservationFormHandler.submitEdicion(dataPrincipal, reservaId) : ReservationFormHandler.submitGrupo(grupo);
    },

    // Recopila datos del formulario principal
    collectReservaData(form) {
        const isEditing = !!document.getElementById("reserva_id").value;
        const anfitrionId = isEditing
            ? form.querySelector("#anfitrion_id_select")?.value
            : document.getElementById("selected_anfitrion")?.value;

        return {
            cliente_existente_id: form.querySelector("[name='cliente_existente_id']")?.value.trim(),
            correo_cliente: form.querySelector("[name='correo_cliente']")?.value.trim(),
            nombre_cliente: form.querySelector("[name='nombre_cliente']")?.value.trim(),
            apellido_paterno_cliente: form.querySelector("[name='apellido_paterno_cliente']")?.value.trim(),
            apellido_materno_cliente: form.querySelector("[name='apellido_materno_cliente']")?.value.trim(),
            telefono_cliente: form.querySelector("[name='telefono_cliente']")?.value.trim(),
            tipo_visita_cliente: form.querySelector("[name='tipo_visita_cliente']")?.value.trim(),
            experiencia_id: form.querySelector("[name='experiencia_id']")?.value,
            cabina_id: form.querySelector("[name='cabina_id']")?.value,
            anfitrion_id: anfitrionId,
            fecha: document.getElementById("fecha_reserva")?.value,
            hora: document.getElementById("hora")?.value,
            observaciones: form.querySelector("[name='observaciones']")?.value || ''
        };
    },

    // Valida los campos esenciales de la reservación principal
    validarPrincipal(data, errores) {
        if (!data.correo_cliente) errores.push(`Reservación principal: Falta correo.`);
        if (!data.cliente_existente_id && (!data.nombre_cliente || !data.apellido_paterno_cliente || !data.telefono_cliente || !data.tipo_visita_cliente)) {
            errores.push(`Reservación principal: Faltan datos del cliente.`);
        }
        if (!data.experiencia_id) errores.push(`Reservación principal: Falta experiencia.`);
        if (!data.cabina_id) errores.push(`Reservación principal: Falta cabina.`);
        if (!data.anfitrion_id) errores.push(`Reservación principal: Falta anfitrión.`);
        if (!data.fecha || !data.hora) errores.push(`Reservación principal: Falta fecha u hora.`);
        return errores.length === 0;
    },

    // Recopila datos de acompañantes y valida
    collectAcompanantes(grupo, errores) {
        document.querySelectorAll(".reserva-extra").forEach((wrapper, i) => {
            const data = {
                cliente_existente_id: wrapper.querySelector("[name*='cliente_existente_id']")?.value.trim() || null,
                correo_cliente: wrapper.querySelector("[name*='correo_cliente']")?.value.trim(),
                nombre_cliente: wrapper.querySelector("[name*='nombre_cliente']")?.value.trim(),
                apellido_paterno_cliente: wrapper.querySelector("[name*='apellido_paterno_cliente']")?.value.trim(),
                apellido_materno_cliente: wrapper.querySelector("[name*='apellido_materno_cliente']")?.value.trim(),
                telefono_cliente: wrapper.querySelector("[name*='telefono_cliente']")?.value.trim(),
                tipo_visita_cliente: wrapper.querySelector("[name*='tipo_visita_cliente']")?.value.trim(),
                experiencia_id: wrapper.querySelector("[name*='experiencia_id']")?.value,
                cabina_id: wrapper.querySelector("[name*='cabina_id']")?.value,
                anfitrion_id: wrapper.querySelector("[name*='anfitrion_id']")?.value,
                fecha: wrapper.querySelector("[name*='fecha']")?.value,
                hora: wrapper.querySelector("[name*='hora']")?.value,
                observaciones: wrapper.querySelector("[name*='observaciones']")?.value || '',
                index: i + 2
            };

            if (!data.correo_cliente) errores.push(`Acompañante #${i + 1}: Falta correo.`);
            if (!data.nombre_cliente || !data.apellido_paterno_cliente || !data.telefono_cliente) {
                errores.push(`Acompañante #${i + 1}: Faltan datos del cliente.`);
            }
            if (!data.experiencia_id || !data.cabina_id || !data.anfitrion_id || !data.fecha || !data.hora) {
                errores.push(`Acompañante #${i + 1}: Faltan datos de reservación.`);
            }

            grupo.push(data);
        });
    },

    // Envía la edición de una reservación
    async submitEdicion(data, id) {
        try {
            const res = await fetch(`/reservations/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify(data)
            });

            const isJson = res.headers.get("content-type")?.includes("application/json");
            if (!res.ok) throw isJson ? await res.json() : { message: "Error inesperado" };

            const result = await res.json();
            Alerts.success(result.message);
            
            // Cierra el modal de Bootstrap
            const modalEl = document.getElementById('reservationModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }

            document.activeElement.blur();
            TableLoader.reload();

        } catch (err) {
            const mensajes = err?.errors
                ? Object.values(err.errors).flat().map(msg => `<p>${msg}</p>`).join("")
                : `<p>${err.message || "Error inesperado"}</p>`;
            Alerts.error(mensajes);
        }
    },

    // Envía un grupo de reservaciones (principal + acompañantes)
    async submitGrupo(grupo) {
        try {
            const res = await fetch("/reservations/grupo", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ grupo })
            });

            const isJson = res.headers.get("content-type")?.includes("application/json");
            if (!res.ok) throw isJson ? await res.json() : { message: "Error inesperado" };

            const result = await res.json();
            Alerts.success(result.message);
            ModalHandler.hideMain();
            document.activeElement.blur();
            TableLoader.reload();

        } catch (err) {
            Alerts.error(
                err.errors
                    ? Object.entries(err.errors).map(([reserva, errores]) => `<strong>${reserva}</strong><br>${errores.join("<br>")}`).join("<hr>")
                    : `<p>${err.message || err.error || "Algo salió mal"}</p>`
            );
        }
    },

    // Enlaza inputs para mostrar/ocultar campos y actualizar duración automáticamente
    bindInputs() {
        const checkbox = document.getElementById("acompanante_checkbox");
        const fields = document.getElementById("acompanante_fields");
        if (checkbox && fields) {
            checkbox.addEventListener("change", () => {
                fields.style.display = checkbox.checked ? "block" : "none";
            });
        }

        const experienciaSelect = document.getElementById("experiencia_id");
        const duracionInput = document.getElementById("duracion");

        if (experienciaSelect && duracionInput) {
            experienciaSelect.addEventListener("change", () => {
                const selectedOption = experienciaSelect.options[experienciaSelect.selectedIndex];
                const duracion = selectedOption.getAttribute("data-duracion") || 0;
                duracionInput.value = duracion;
            });
        }
    },

    // Configura búsqueda de cliente por correo en formulario principal
    setupBuscarCliente() {
        const buscarBtn = document.getElementById("buscarClienteBtn");
        if (!buscarBtn) return;

        buscarBtn.addEventListener("click", async () => {
            const correoInput = document.getElementById("correo_cliente");
            const correo = correoInput.value.trim();

            if (!correo) {
                ModalAlerts.show("Por favor ingresa un correo para buscar.", { type: "warning", title: "Atención", autoClose: 4000 });
                return;
            }

            try {
                const response = await fetch('/buscar-cliente', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ correo })
                });

                const data = await response.json();

                const datosDiv = document.getElementById("datosCliente");
                datosDiv.style.display = "block";

                if (response.ok && data.success) {
                    document.getElementById("cliente_existente_id").value = data.cliente.id;
                    document.getElementById("nombre_cliente").value = data.cliente.nombre || '';
                    document.getElementById("apellido_paterno_cliente").value = data.cliente.apellido_paterno || '';
                    document.getElementById("apellido_materno_cliente").value = data.cliente.apellido_materno || '';
                    document.getElementById("telefono_cliente").value = data.cliente.telefono || '';
                    document.getElementById("tipo_visita_cliente").value = data.cliente.tipo_visita || '';
                    ModalAlerts.show("Los datos se han cargado.", { type: "success", title: "Cliente encontrado", autoClose: 4000 });
                } else {
                    document.getElementById("nombre_cliente").value = '';
                    document.getElementById("apellido_paterno_cliente").value = '';
                    document.getElementById("apellido_materno_cliente").value = '';
                    document.getElementById("telefono_cliente").value = '';
                    document.getElementById("tipo_visita_cliente").value = '';
                    ModalAlerts.show("Por favor ingresa los datos.", { type: "info", title: "Cliente no encontrado", autoClose: 4000 });
                }

            } catch (error) {
                console.error(error);
                ModalAlerts.show("Hubo un problema al buscar el cliente.", { type: "error", title: "Error" });
            }
        });
    },

    // Configura generación dinámica de formularios para acompañantes
    setupGrupoReservas() {
        const generarBtn = document.getElementById("generarReservasBtn");
        const cantidadInput = document.getElementById("cantidadReservas");
        const container = document.getElementById("grupoReservasContainer");
        const template = document.getElementById("reservaExtraTemplate");

        if (!generarBtn || !cantidadInput || !container || !template) return;

        generarBtn.addEventListener("click", () => {
            const cantidad = parseInt(cantidadInput.value);
            if (isNaN(cantidad) || cantidad < 0 || cantidad > 10) {
                ModalAlerts.show("Ingresa una cantidad válida entre 0 y 10.", { type: "warning", autoClose: 4000 });
                return;
            }

            container.innerHTML = ""; // Limpia contenedor antes de agregar

            for (let i = 0; i < cantidad; i++) {
                const clone = template.content.cloneNode(true);
                const html = clone.querySelector(".reserva-extra").innerHTML.replace(/__INDEX__/g, i);
                const wrapper = document.createElement("div");
                wrapper.className = "reserva-extra bg-cliente p-3 my-3 rounded border border-secondary position-relative";
                wrapper.innerHTML = html;

                container.appendChild(wrapper);
                ReservationFormHandler.populateSelects(wrapper);

                // Agrega listeners para actualizar cabinas, anfitriones y horas disponibles dinámicamente
                ['experiencia_id', 'fecha'].forEach(campo => {
                    wrapper.querySelector(`[name*='${campo}']`)?.addEventListener('change', () => {
                        ReservationFormHandler.filtrarCabinasYAnfitriones(wrapper);
                        ReservationFormHandler.actualizarHorasDisponibles(wrapper);
                    });
                });

                ['cabina_id', 'anfitrion_id'].forEach(campo => {
                    wrapper.querySelector(`[name*='${campo}']`)?.addEventListener('change', () => {
                        ReservationFormHandler.actualizarHorasDisponibles(wrapper);
                    });
                });

                ['fecha', 'experiencia_id', 'anfitrion_id', 'cabina_id'].forEach(campo => {
                    wrapper.querySelector(`[name*='${campo}']`)?.addEventListener('change', () => {
                        ReservationFormHandler.actualizarHorasDisponibles(wrapper);
                    });
                });

                // Botón para remover formulario de acompañante
                const removeBtn = wrapper.querySelector(".remove-reserva-btn");
                removeBtn?.addEventListener("click", () => wrapper.remove());
            }
        });

        // Evento delegación para buscar clientes en formularios de acompañantes
        document.addEventListener("click", async function (e) {
            const btn = e.target.closest(".buscar-cliente-extra");
            if (!btn) return;

            const wrapper = btn.closest(".reserva-extra");
            if (!wrapper) {
                return;
            }

            const inputCorreo = wrapper.querySelector(".correo-cliente");
            const correo = inputCorreo?.value.trim();

            if (!correo) {
                ModalAlerts.show("Ingresa un correo válido.", { type: "warning", title: "Atención" });
                return;
            }

            try {
                const response = await fetch('/buscar-cliente', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ correo })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    wrapper.querySelector(".cliente-id-existente").value = data.cliente.id;
                    wrapper.querySelector("[name*='nombre_cliente']").value = data.cliente.nombre || '';
                    wrapper.querySelector("[name*='apellido_paterno_cliente']").value = data.cliente.apellido_paterno || '';
                    wrapper.querySelector("[name*='apellido_materno_cliente']").value = data.cliente.apellido_materno || '';
                    wrapper.querySelector("[name*='telefono_cliente']").value = data.cliente.telefono || '';
                    wrapper.querySelector("[name*='tipo_visita_cliente']").value = data.cliente.tipo_visita || '';
                    ModalAlerts.show("Cliente encontrado y cargado.", { type: "success", title: "Cliente encontrado" });
                } else {
                    ModalAlerts.show("Cliente no encontrado. Ingresa los datos manualmente.", { type: "info", title: "No encontrado" });
                }

            } catch (error) {
                console.error(error);
                ModalAlerts.show("Ocurrió un error al buscar el cliente.", { type: "error", title: "Error" });
            }
        });
    },

    // Rellena formulario para editar reservación con datos existentes
    async rellenarFormularioEdicion(data) {
        document.getElementById("modalTitle").textContent = "Editar Reservación";
        document.getElementById("saveButton").textContent = "Actualizar Reservación";

        document.getElementById("reserva_id").value = data.id;
        document.getElementById("fecha_reserva").value = data.fecha;
        document.getElementById("fecha_reserva").setAttribute('data-original-date', data.fecha);
        
        window.originalHoraReserva = data.hora; // Guardar hora original

        document.getElementById("duracion").value = data.duracion;
        
        const experienciaSelect = document.getElementById("experiencia_id");
        const cabinaSelect = document.getElementById("cabina_id");
        experienciaSelect.value = data.experiencia_id;
        cabinaSelect.value = data.cabina_id;

        document.getElementById("cliente_existente_id").value = data.cliente_existente_id || "";
        document.getElementById("correo_cliente").value = data.correo_cliente || "";
        document.getElementById("nombre_cliente").value = data.nombre_cliente || "";
        document.getElementById("apellido_paterno_cliente").value = data.apellido_paterno_cliente || "";
        document.getElementById("apellido_materno_cliente").value = data.apellido_materno_cliente || "";
        document.getElementById("telefono_cliente").value = data.telefono_cliente || "";
        document.getElementById("tipo_visita_cliente").value = data.tipo_visita_cliente || "";

        const datosDiv = document.getElementById("datosCliente");
        datosDiv.style.display = "block";
        document.getElementById("observaciones").value = data.observaciones || "";

        const anfitrionWrapper = document.getElementById("anfitrionWrapper");
        const anfitrionSelect = document.getElementById("anfitrion_id_select");
        anfitrionWrapper.style.display = "block";
        anfitrionSelect.innerHTML = "";

        data.anfitriones.forEach(anfitrion => {
            const option = document.createElement("option");
            option.value = anfitrion.id;
            option.textContent = anfitrion.nombre;
            anfitrionSelect.appendChild(option);
        });

        anfitrionSelect.value = data.anfitrion_id;
        document.getElementById("selected_anfitrion").disabled = true;

        const horaSelect = document.getElementById("hora");
        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>'; // Clear existing options

        if (data.horarios_disponibles && data.horarios_disponibles.length > 0) {
            data.horarios_disponibles.forEach(hora => {
                const option = document.createElement('option');
                option.value = hora;
                option.textContent = hora;
                horaSelect.appendChild(option);
            });
        }
        horaSelect.value = data.hora;
        
        const modalEl = document.getElementById("reservationModal");
        const modal = new bootstrap.Modal(modalEl, { keyboard: false });
        
        const addReservaBtn = document.getElementById("addReservaBtn");
        if(addReservaBtn) {
            addReservaBtn.classList.add("d-none");
        }
        
        modal.show();
    },

    // Limpia y resetea todos los campos y estados del formulario
    limpiarFormulario() {
        const form = document.getElementById("reservationForm");
        if (form) form.reset();

        window.originalHoraReserva = null;

        document.getElementById("experiencia_id").disabled = false;
        document.getElementById("cabina_id").disabled = false;
        document.getElementById("selected_anfitrion").disabled = false;

        document.getElementById("anfitrionWrapper").style.display = "none";

        const campos = [
            "cliente_existente_id",
            "reserva_id",
            "fecha_reserva",
            "hora",
            "duracion",
            "selected_anfitrion",
            "correo_cliente",
            "nombre_cliente",
            "apellido_paterno_cliente",
            "apellido_materno_cliente",
            "telefono_cliente",
            "tipo_visita_cliente",
            "observaciones"
        ];

        campos.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });

        const datosCliente = document.getElementById("datosCliente");
        if (datosCliente) datosCliente.style.display = "none";

        const grupoReservas = document.getElementById("grupoReservasContainer");
        if (grupoReservas) grupoReservas.innerHTML = "";

        const cantidadInput = document.getElementById("cantidadReservas");
        if (cantidadInput) cantidadInput.value = "";

        const saveBtn = document.getElementById("saveButton");
        if (saveBtn) saveBtn.textContent = "Guardar Reservación";

        const modalTitle = document.getElementById("modalTitle");
        if (modalTitle) modalTitle.textContent = "Nueva Reservación";
    },

    // Llena selects de experiencia, cabina y anfitrión para formularios dinámicos
    populateSelects(wrapper) {
        const experienciaSelect = wrapper.querySelector("select[name='experiencia_id']");
        if (experienciaSelect) {
            experienciaSelect.innerHTML = '<option disabled selected>Selecciona experiencia</option>';
            (window.ReservasConfig?.experiencias || []).forEach(exp => {
                const opt = document.createElement("option");
                opt.value = exp.id;
                opt.textContent = exp.nombre;
                opt.setAttribute("data-duracion", exp.duracion);
                experienciaSelect.appendChild(opt);
            });

            const duracionInput = wrapper.querySelector("input[name='duracion']");
            if (duracionInput) {
                experienciaSelect.addEventListener("change", () => {
                    const duracion = experienciaSelect.options[experienciaSelect.selectedIndex]?.getAttribute("data-duracion") || 0;
                    duracionInput.value = duracion;
                });
            }
        }

        const cabinaSelect = wrapper.querySelector("select[name='cabina_id']");
        if (cabinaSelect) {
            cabinaSelect.innerHTML = '<option disabled selected>Selecciona cabina</option>';
            (window.ReservasConfig?.cabinas || []).forEach(cabina => {
                const opt = document.createElement("option");
                opt.value = cabina.id;
                opt.textContent = cabina.nombre;
                cabinaSelect.appendChild(opt);
            });
        }

        const anfitrionSelect = wrapper.querySelector("select[name='anfitrion_id']");
        if (anfitrionSelect) {
            anfitrionSelect.innerHTML = '<option disabled selected>Selecciona anfitrión</option>';
            (window.ReservasConfig?.anfitriones || []).forEach(a => {
                const opt = document.createElement("option");
                opt.value = a.id;
                opt.textContent = a.nombre_usuario;
                anfitrionSelect.appendChild(opt);
            });
            const anfitrionId = anfitrionSelect.value;
            this.filtrarExperienciasPorAnfitrion(anfitrionId, formulario);
        }
    },

    // Configura filtrado de cabinas y anfitriones según experiencia y fecha seleccionadas
    setupFiltradoPorExperienciaYFecha() {
        const experienciaSelect = document.getElementById("experiencia_id");
        const fechaInput = document.getElementById("fecha_reserva");
        const cabinaSelect = document.getElementById("cabina_id");

        const resetSelect = (select, mensaje) => {
            if (select) {
                select.innerHTML = `<option value="">${mensaje}</option>`;
            }
        };

        const filtrar = () => {
            const experienciaId = experienciaSelect.value;
            const fecha = fechaInput.value;

            if (!experienciaId || !fecha) return;

            const experiencias = window.ReservasConfig.experiencias || [];
            const cabinas = window.ReservasConfig.cabinas || [];
            
            const experiencia = experiencias.find(e => e.id == experienciaId);
            if (!experiencia) return;

            const claseRequerida = ReservationFormHandler.normalizeString(experiencia.clase || "");

            // Filtra cabinas compatibles
            const cabinasCompatibles = cabinas.filter(c => ReservationFormHandler.cabinaSoportaClase(c, claseRequerida));

            // Guarda la cabina seleccionada antes de limpiar
            const cabinaSeleccionadaAnteriormente = cabinaSelect.value;

            // Actualiza select de cabina
            resetSelect(cabinaSelect, "Selecciona cabina");
            let cabinaAnteriorAunCompatible = false;

            cabinasCompatibles.forEach(c => {
                const option = document.createElement("option");
                option.value = c.id;
                option.textContent = c.nombre;
                cabinaSelect.appendChild(option);
                if (String(c.id) === String(cabinaSeleccionadaAnteriormente)) {
                    cabinaAnteriorAunCompatible = true;
                }
            });

            // Si la cabina anterior sigue siendo compatible, la re-selecciona
            if (cabinaAnteriorAunCompatible) {
                cabinaSelect.value = cabinaSeleccionadaAnteriormente;
            }
        };

        experienciaSelect?.addEventListener("change", filtrar);
        fechaInput?.addEventListener("change", filtrar);
    },

    // Actualiza las horas disponibles según filtros y bloqueos/reservas existentes
    async actualizarHorasDisponibles(wrapper) {
        const experienciaId = wrapper.querySelector("[name*='experiencia_id']")?.value;
        const fecha = wrapper.querySelector("[name*='fecha']")?.value;
        const anfitrionId = wrapper.querySelector("[name*='anfitrion_id']")?.value;
        const horaSelect = wrapper.querySelector("select[name*='hora']");

        if (!fecha || !anfitrionId || !experienciaId) {
            horaSelect.innerHTML = `<option value="">Selecciona hora</option>`;
            return;
        }

        try {
            const params = new URLSearchParams({ experience_id: experienciaId });
            const url = `/anfitriones/${anfitrionId}/horarios/${fecha}?${params.toString()}`;
            
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('No se pudieron obtener los horarios.');
            }
            const horarios = await response.json();

            horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
            if (horarios && horarios.length > 0) {
                horarios.forEach(hora => {
                    const option = document.createElement('option');
                    option.value = hora;
                    option.textContent = hora;
                    horaSelect.appendChild(option);
                });
            } else {
                 horaSelect.innerHTML = '<option value="">No hay horas disponibles</option>';
            }
        } catch (error) {
            console.error('Error al obtener horarios para el grupo:', error);
            horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
        }
    },

    // Filtra experiencias permitidas por anfitrión
    filtrarExperienciasPorAnfitrion(anfitrionId, contenedor) {
        const anfitrion = window.ReservasConfig.anfitriones.find(a => a.id == anfitrionId);
        const selectExperiencia = contenedor?.querySelector('[name="experiencia_id"]');

        if (!anfitrion || !selectExperiencia) return;

        const clasesPermitidas = Array.isArray(anfitrion.operativo?.clases_actividad)
            ? anfitrion.operativo.clases_actividad.map(c => ReservationFormHandler.normalizeString(c))
            : [];

        // Limpiar select
        selectExperiencia.innerHTML = '<option value="">Selecciona una experiencia</option>';

        // Filtrar y agregar opciones
        window.ReservasConfig.experiencias.forEach(exp => {
            if (clasesPermitidas.length === 0 || clasesPermitidas.includes(ReservationFormHandler.normalizeString(exp.clase))) {
                const option = document.createElement('option');
                option.value = exp.id;
                option.textContent = exp.nombre;
                selectExperiencia.appendChild(option);
            }
        });
    },

    // Obtiene día de la semana en texto desde fecha YYYY-MM-DD
    diaSemana(fechaStr) {
        if (!fechaStr || isNaN(new Date(fechaStr).getTime())) {
            console.warn("⚠️ Fecha inválida recibida:", fechaStr);
            return "";
        }

        const [year, month, day] = fechaStr.split('-').map(Number);
        const fecha = new Date(year, month - 1, day);

        const dias = [
            "domingo",
            "lunes",
            "martes",
            "miercoles",
            "jueves",
            "viernes",
            "sabado"
        ];

        return dias[fecha.getDay()];
    },

    // Convierte hora HH:mm a minutos totales
    toMinutes(horaStr) {
        if (!horaStr || typeof horaStr !== "string" || !horaStr.includes(":")) return -1;
        const [h, m] = horaStr.split(":").map(x => parseInt(x) || 0);
        return h * 60 + m;
    },

    // Obtiene horarios ocupados por reservas y bloqueos para fecha, anfitrión y cabina
    obtenerHorariosOcupados(fecha, anfitrionId, cabinaId) {
        const reservas = window.ReservasConfig?.reservaciones || [];
        const bloqueos = window.ReservasConfig?.bloqueos || [];
        const experiencias = window.ReservasConfig?.experiencias || [];

        const ocupados = {
            anfitrion: [],
            cabina: []
        };

        reservas.forEach(r => {
            const fechaReserva = r.fecha?.split('T')[0];
            if (fechaReserva !== fecha) return;

            const duracionExp = parseInt((experiencias.find(e => e.id == r.experiencia_id)?.duracion) || 50);
            const inicio = ReservationFormHandler.toMinutes(r.hora || "00:00");

            // Ocupación del anfitrión (con descanso)
            if (String(r.anfitrion_id) === String(anfitrionId)) {
                const finAnfitrion = inicio + duracionExp + 10;
                ocupados.anfitrion.push([inicio, finAnfitrion]);
            }

            // Ocupación de la cabina (sin descanso)
            if (String(r.cabina_id) === String(cabinaId)) {
                const finCabina = inicio + duracionExp;
                ocupados.cabina.push([inicio, finCabina]);
            }
        });

        bloqueos.forEach(b => {
            const fechaBloqueo = b.fecha?.split('T')[0];
            if (fechaBloqueo !== fecha || String(b.anfitrion_id) !== String(anfitrionId)) return;

            const inicio = ReservationFormHandler.toMinutes(b.hora || "00:00");
            const duracion = parseInt(b.duracion || 30);
            const fin = inicio + duracion;

            ocupados.anfitrion.push([inicio, fin]); // Los bloqueos solo afectan al anfitrión
        });

        return ocupados;
    },

    // Obtiene horarios ocupados por otras reservaciones que se están creando en el mismo formulario
    obtenerHorariosOcupadosDelFormulario(wrapperExcluido, fecha, anfitrionId, cabinaId) {
        const ocupados = {
            anfitrion: [],
            cabina: []
        };
        const experiencias = window.ReservasConfig?.experiencias || [];

        const procesarWrapper = (wrapper) => {
            if (wrapper === wrapperExcluido) return;

            const isPrincipal = wrapper.id === 'reservationForm';
            const formFecha = isPrincipal ? wrapper.querySelector("#fecha_reserva")?.value : wrapper.querySelector("[name*='fecha']")?.value;

            if (formFecha !== fecha) return;

            const formHora = isPrincipal ? wrapper.querySelector("#hora")?.value : wrapper.querySelector("[name*='hora']")?.value;
            const formAnfitrionId = isPrincipal ? document.getElementById("selected_anfitrion")?.value : wrapper.querySelector("[name*='anfitrion_id']")?.value;
            const formCabinaId = isPrincipal ? wrapper.querySelector("[name*='cabina_id']")?.value : wrapper.querySelector("[name*='cabina_id']")?.value;
            const formExperienciaId = isPrincipal ? wrapper.querySelector("[name*='experiencia_id']")?.value : wrapper.querySelector("[name*='experiencia_id']")?.value;

            if (!formHora || !formExperienciaId) return;

            const exp = experiencias.find(e => e.id == formExperienciaId);
            if (!exp) return;

            const duracionExp = parseInt(exp.duracion || 0);
            const inicio = this.toMinutes(formHora);

            if (String(formAnfitrionId) === String(anfitrionId)) {
                ocupados.anfitrion.push([inicio, inicio + duracionExp + 10]); // con descanso
            }

            if (String(formCabinaId) === String(cabinaId)) {
                ocupados.cabina.push([inicio, inicio + duracionExp]); // sin descanso
            }
        };

        procesarWrapper(document.getElementById('reservationForm'));
        document.querySelectorAll('.reserva-extra').forEach(procesarWrapper);

        return ocupados;
    },

    // Filtra selects de cabinas y anfitriones compatibles con la experiencia y fecha
    filtrarCabinasYAnfitriones(wrapper) {
        const experienciaId = wrapper.querySelector("[name*='experiencia_id']")?.value;
        const fecha = wrapper.querySelector("[name*='fecha']")?.value;
        const selectCabina = wrapper.querySelector("select[name*='cabina_id']");
        const selectAnfitrion = wrapper.querySelector("select[name*='anfitrion_id']");

        if (!experienciaId || !fecha || !selectCabina || !selectAnfitrion) return;

        const experiencia = (window.ReservasConfig.experiencias || []).find(e => e.id == experienciaId);
        if (!experiencia) return;

        const claseRequerida = ReservationFormHandler.normalizeString(experiencia.clase || '');

        let dia = ReservationFormHandler.diaSemana(fecha);

        // Filtra anfitriones con clase, departamento y horario válido
        const anfitrionesCompatibles = (window.ReservasConfig.anfitriones || []).filter(a => {
            const clasesRaw = (a.operativo?.clases_actividad || a.clases_actividad || []).map(c => typeof c === "string" ? c : (c?.nombre || ''));
            const clases = clasesRaw.map(c => ReservationFormHandler.normalizeString(c));
            const depto = ReservationFormHandler.normalizeString(a.operativo?.departamento || a.departamento || '');
            const horarios = (window.ReservasConfig.horarios?.[a.id]?.[dia]) || [];

            const cumpleClase = clases.includes(claseRequerida);
            const cumpleHorario = horarios.length > 0;
            const cumpleDepto = depto === "spa";

            return cumpleDepto && cumpleClase && cumpleHorario;
        });

        // Filtra cabinas compatibles con clase (correlacionando subtipos con la clase de la experiencia)
        const cabinasCompatibles = (window.ReservasConfig.cabinas || []).filter(c => ReservationFormHandler.cabinaSoportaClase(c, claseRequerida));

        // Actualiza opciones cabinas y anfitriones
        selectCabina.innerHTML = '<option value="">Selecciona cabina</option>';
        cabinasCompatibles.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.textContent = c.nombre;
            selectCabina.appendChild(opt);
        });

        if (selectAnfitrion) {
            selectAnfitrion.innerHTML = '<option value="">Selecciona anfitrión</option>';
            anfitrionesCompatibles.forEach(a => {
                const opt = document.createElement("option");
                opt.value = a.id;
                opt.textContent = a.nombre_usuario + ' ' + (a.apellido_paterno || '');
                selectAnfitrion.appendChild(opt);
            });
        }
    },

    // Inicializa filtro de experiencias según selección de anfitrión (solo principal)
    inicializarFormularioPrincipal() {
        document.addEventListener('DOMContentLoaded', () => {
            const experienciaSelect = document.getElementById('experiencia_id');
            const anfitrionSelect = document.getElementById('selected_anfitrion');

            if (!experienciaSelect || !anfitrionSelect) return;

            anfitrionSelect.addEventListener('change', () => {
                const anfitrionId = parseInt(anfitrionSelect.value);
                const anfitrion = window.ReservasConfig.anfitriones.find(a => a.id === anfitrionId);

                if (!anfitrion || !anfitrion.categoria) {
                    console.warn("Anfitrión sin categoría:", anfitrion);
                    return;
                }

                const categoria = ReservationFormHandler.normalizeString(anfitrion.categoria);

                experienciaSelect.querySelectorAll('option').forEach(option => {
                    if (option.value === "") {
                        option.hidden = false;
                        return;
                    }

                    const experienciaId = parseInt(option.value);
                    const experiencia = window.ReservasConfig.experiencias.find(e => e.id === experienciaId);

                    if (!experiencia || !experiencia.clase) {
                        option.hidden = true;
                        return;
                    }

                    option.hidden = ReservationFormHandler.normalizeString(experiencia.clase) !== categoria;
                });

                experienciaSelect.value = ""; // Reinicia selección
            });
        });
    },

    setupActualizarHoras() {
        const fechaInput = document.getElementById("fecha_reserva");
        const anfitrionSelect = document.getElementById("anfitrion_id_select");
        const horaSelect = document.getElementById("hora");
        const reservaIdInput = document.getElementById("reserva_id");
        const experienciaSelect = document.getElementById("experiencia_id");
    
        const updateHoras = async () => {
            const anfitrionId = anfitrionSelect.value;
            const fecha = fechaInput.value;
            const reservaId = reservaIdInput.value;
            const experienciaId = experienciaSelect.value;
    
            if (anfitrionId && fecha) {
                try {
                    let url = `/anfitriones/${anfitrionId}/horarios/${fecha}?`;
                    const params = new URLSearchParams();
                    if (reservaId) {
                        params.append('reservation_id', reservaId);
                    }
                    if (experienciaId) {
                        params.append('experience_id', experienciaId);
                    }
                    
                    const response = await fetch(url + params.toString());
                    if (!response.ok) {
                        throw new Error('No se pudieron obtener los horarios.');
                    }
                    const horarios = await response.json();
    
                    const horaActual = window.originalHoraReserva;
                    let horaActualEncontrada = false;

                    horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
                    if (horarios && horarios.length > 0) {
                        horarios.forEach(hora => {
                            const option = document.createElement('option');
                            option.value = hora;
                            option.textContent = hora;
                            horaSelect.appendChild(option);
                            if (hora === horaActual) {
                                horaActualEncontrada = true;
                            }
                        });
                    }
                    
                    if (reservaId && !horaActualEncontrada && horaActual) {
                         const originalDate = new Date(document.getElementById("fecha_reserva").getAttribute('data-original-date'));
                         const selectedDate = new Date(fecha);

                         if(originalDate.toDateString() === selectedDate.toDateString()){
                            const option = document.createElement('option');
                            option.value = horaActual;
                            option.textContent = horaActual;
                            option.setAttribute('data-original', 'true');
                            horaSelect.appendChild(option);
                         }
                    }
                    
                    if (horaSelect.options.length <= 1) {
                         horaSelect.innerHTML = '<option value="">No hay horas disponibles</option>';
                    }

                    if (horaActual) {
                        horaSelect.value = horaActual;
                    }

                } catch (error) {
                    console.error('Error al obtener horarios:', error);
                    horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                }
            }
        };
    
        fechaInput?.addEventListener('change', updateHoras);
        anfitrionSelect?.addEventListener('change', updateHoras);
        experienciaSelect?.addEventListener('change', updateHoras); // Also trigger on experience change
    }
};

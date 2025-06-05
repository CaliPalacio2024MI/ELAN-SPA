<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/general_styles.css')
        @vite('resources/css/gimnasio/gimnasio_registro_externo_styles.css')
        @vite('resources/css/gimnasio/gimnasio_formulario_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
        @vite('resources/css/componentes/modal.css')
        @vite('resources/css/componentes/tooltip.css')
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Gimnasio - Registro</title>
</head>

<body>
    <header
        style="background-color: var(--primary3); height: 10dvh; display: flex; align-items: center; justify-content: center;">
        <img src="{{ asset("images/$hotelName/logo.png") }}" alt="Logo de {{ ucfirst($hotelName) }}"
            style="height: 100%; width: auto; padding: 1rem;">
    </header>

    <main>
        <div class="main-container">
            @include('gimnasio.componentes.formulario', ['startActive' => true])

            <a href="{{ route('gimnasio.reglamento') }}" target="_blank"
                style="display: block; text-align: center; padding: 20px;">Leer Reglamento del Gimnasio</a>
        </div>
    </main>

    <footer>
        @php
            $linDecorativa = asset("images/$hotelName/decorativo.png");
        @endphp
        <div class="sidebar-decoration"
            style="background-image: url('{{ $linDecorativa }}'); background-color: var(--primary3);"></div>
    </footer>

    <script>
        const token = @json($token);
    </script>

    <!-- Agregar Menor -->
    <script>
        let contadorMenores = 1;

        function agregarMenor() {
            const container = document.querySelector('.menores-container');

            // Crear contenedor para este menor
            const menorContainer = document.createElement('div');
            menorContainer.classList.add('menor-individual');
            menorContainer.setAttribute('data-menor-id', contadorMenores);
            menorContainer.style.width = 'min(550px, 100%)';
            menorContainer.style.margin = '0 auto';

            const hr = document.createElement('hr');

            // Título con botón eliminar
            const tituloDiv = document.createElement('div');
            tituloDiv.style.display = 'flex';
            tituloDiv.style.justifyContent = 'space-between';
            tituloDiv.style.alignItems = 'center';
            tituloDiv.style.marginBottom = '1rem';
            tituloDiv.style.width = 'min(550px, 100%)';

            const titulo = document.createElement('h4');
            titulo.innerText = `Menor ${contadorMenores + 1}`;
            titulo.style.margin = '0';

            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.innerHTML = '<i class="fas fa-trash"></i> Eliminar';
            btnEliminar.classList.add('btn-eliminar-menor');
            btnEliminar.style.backgroundColor = '#dc3545';
            btnEliminar.style.color = 'white';
            btnEliminar.style.border = 'none';
            btnEliminar.style.padding = '0.5rem 1rem';
            btnEliminar.style.borderRadius = '4px';
            btnEliminar.style.cursor = 'pointer';
            btnEliminar.style.fontSize = '0.9rem';

            const menorIdActual = contadorMenores; // guardar el id actual
            btnEliminar.addEventListener('click', function() {
                eliminarMenor(menorIdActual); // usar la copia, no el contador global
            });

            tituloDiv.appendChild(titulo);
            tituloDiv.appendChild(btnEliminar);

            const nombreDiv = document.createElement('div');
            nombreDiv.classList.add('form-group');
            const labelNombre = document.createElement('label');
            labelNombre.setAttribute('for', `nombre_menor_${contadorMenores}`);
            labelNombre.innerText = 'Nombre completo del menor *';
            const inputNombre = document.createElement('input');
            inputNombre.type = 'text';
            inputNombre.id = `nombre_menor_${contadorMenores}`;
            inputNombre.name = 'nombre_menor[]';

            nombreDiv.appendChild(labelNombre);
            nombreDiv.appendChild(inputNombre);

            const edadDiv = document.createElement('div');
            edadDiv.classList.add('form-group');
            const labelEdad = document.createElement('label');
            labelEdad.setAttribute('for', `edad_menor_${contadorMenores}`);
            labelEdad.innerText = 'Edad *';
            const selectEdad = document.createElement('select');
            selectEdad.id = `edad_menor_${contadorMenores}`;
            selectEdad.name = 'edad[]';

            const edades = ['', '15', '16', '17'];
            const textos = ['Seleccionar edad', '15 años', '16 años', '17 años'];

            for (let i = 0; i < edades.length; i++) {
                const option = document.createElement('option');
                option.value = edades[i];
                option.text = textos[i];
                selectEdad.appendChild(option);
            }

            edadDiv.appendChild(labelEdad);
            edadDiv.appendChild(selectEdad);

            // Armar el contenedor del menor
            menorContainer.appendChild(hr);
            menorContainer.appendChild(tituloDiv);
            menorContainer.appendChild(nombreDiv);
            menorContainer.appendChild(edadDiv);

            // Insertar al DOM
            container.appendChild(menorContainer);

            contadorMenores++;
        }

        function eliminarMenor(menorId) {
            console.log('Eliminando menor con ID:', menorId); // Debug
            const menorContainer = document.querySelector(`[data-menor-id="${menorId}"]`);
            console.log('Contenedor encontrado:', menorContainer); // Debug

            if (menorContainer) {
                menorContainer.remove();
                // Actualizar títulos de los menores restantes
                actualizarTitulosMenores();
                contadorMenores--; // Decrementar el contador
                console.log('Menor eliminado exitosamente'); // Debug
            } else {
                console.error('No se encontró el contenedor del menor'); // Debug
            }
        }

        function actualizarTitulosMenores() {
            const menoresIndividuales = document.querySelectorAll('.menor-individual');
            menoresIndividuales.forEach((menor, index) => {
                const titulo = menor.querySelector('h4');
                if (titulo) {
                    titulo.innerText = `Menor ${index + 2}`; // +2 porque el primero es "Menor 1"
                }
            });
        }
    </script>

    <!-- Manejo de pasos del formulario -->
    <script>
        let signaturesCanvas = {};

        function resetMenoresContainer() {
            const container = document.querySelector('.menores-container');
            // Eliminar todos los menores adicionales (mantener solo los primeros 2 elementos del primer menor)
            const menoresIndividuales = container.querySelectorAll('.menor-individual');
            menoresIndividuales.forEach(menor => menor.remove());
        }

        // Funciones de navegación
        function showSelection() {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.getElementById('step-selection').classList.add('active');
        }

        function showAdultForm() {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.getElementById('step-adult').classList.add('active');
            initSignatureCanvas('firma_adulto');
        }

        function showMinorForm() {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.getElementById('step-minor').classList.add('active');
            initSignatureCanvas('firma_tutor');
            initSignatureCanvas('firma_anfitrion');
        }

        // Funciones para firmas
        function initSignatureCanvas(canvasId) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            let isDrawing = false;

            signaturesCanvas[canvasId] = {
                canvas,
                ctx,
                isEmpty: true
            };

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // Touch events para móviles
            canvas.addEventListener('touchstart', handleTouch);
            canvas.addEventListener('touchmove', handleTouch);
            canvas.addEventListener('touchend', stopDrawing);

            function startDrawing(e) {
                isDrawing = true;
                signaturesCanvas[canvasId].isEmpty = false;
                const rect = canvas.getBoundingClientRect();
                ctx.beginPath();
                ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            }

            function draw(e) {
                if (!isDrawing) return;
                const rect = canvas.getBoundingClientRect();
                ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
                ctx.stroke();
            }

            function stopDrawing() {
                isDrawing = false;
            }

            function handleTouch(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' :
                    e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                        clientX: touch.clientX,
                        clientY: touch.clientY
                    });
                canvas.dispatchEvent(mouseEvent);
            }
        }

        function clearSignature(canvasId) {
            const canvasData = signaturesCanvas[canvasId];
            canvasData.ctx.clearRect(0, 0, canvasData.canvas.width, canvasData.canvas.height);
            canvasData.isEmpty = true;
        }

        // Manejo de formularios
        document.getElementById('form-adult').addEventListener('submit', function(e) {
            e.preventDefault();

            if (signaturesCanvas['firma_adulto'].isEmpty) {
                alert('Por favor, proporcione su firma.');
                return;
            }

            const formData = new FormData();
            formData.append('tipo', 'adulto');
            formData.append('nombre_huesped', document.getElementById('nombre_adulto').value);
            formData.append('firma_huesped', signaturesCanvas['firma_adulto'].canvas.toDataURL());
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('token', token);

            // Enviar datos al servidor
            submitForm(formData);
        });

        document.getElementById('form-minor').addEventListener('submit', function(e) {
            e.preventDefault();

            if (signaturesCanvas['firma_tutor'].isEmpty || signaturesCanvas['firma_anfitrion'].isEmpty) {
                alert('Por favor, complete todas las firmas requeridas.');
                return;
            }

            const formData = new FormData();
            formData.append('tipo', 'menor');

            // Obtener datos de TODOS los menores
            const nombresMenores = [];
            const edadesMenores = [];
            const menoresIncompletos = [];

            // Primer menor (siempre presente)
            const nombrePrimerMenor = document.getElementById('nombre_menor').value.trim();
            const edadPrimerMenor = document.getElementById('edad_menor').value;

            // Verificar si el primer menor está completo o incompleto
            if (nombrePrimerMenor && edadPrimerMenor) {
                nombresMenores.push(nombrePrimerMenor);
                edadesMenores.push(edadPrimerMenor);
            } else if (nombrePrimerMenor || edadPrimerMenor) {
                menoresIncompletos.push('Menor 1');
            }

            // Menores adicionales
            for (let i = 1; i < contadorMenores; i++) {
                const nombreInput = document.getElementById(`nombre_menor_${i}`);
                const edadSelect = document.getElementById(`edad_menor_${i}`);

                if (nombreInput && edadSelect) {
                    const nombre = nombreInput.value.trim();
                    const edad = edadSelect.value;

                    // Si ambos campos están llenos, agregar el menor
                    if (nombre && edad) {
                        nombresMenores.push(nombre);
                        edadesMenores.push(edad);
                    }
                    // Si solo uno está lleno, es incompleto
                    else if (nombre || edad) {
                        menoresIncompletos.push(`Menor ${i + 1}`);
                    }
                    // Si ambos están vacíos, lo ignoramos (está bien)
                }
            }

            // Validar que no hay menores incompletos
            if (menoresIncompletos.length > 0) {
                alert(
                    `Los siguientes menores tienen datos incompletos: ${menoresIncompletos.join(', ')}. Por favor complete todos los campos o déjelos completamente vacíos.`
                );
                return;
            }

            // Validar que al menos hay un menor completo
            if (nombresMenores.length === 0) {
                alert('Por favor, complete al menos los datos de un menor.');
                return;
            }

            // Agregar arrays de menores al FormData
            formData.append('nombres_menores', JSON.stringify(nombresMenores));
            formData.append('edades_menores', JSON.stringify(edadesMenores));

            // Datos del tutor y anfitrión
            formData.append('nombre_tutor', document.getElementById('nombre_tutor').value);
            formData.append('telefono_tutor', document.getElementById('telefono_tutor').value);
            formData.append('nombre_anfitrion', document.getElementById('nombre_anfitrion').value);
            formData.append('firma_tutor', signaturesCanvas['firma_tutor'].canvas.toDataURL());
            formData.append('firma_anfitrion', signaturesCanvas['firma_anfitrion'].canvas.toDataURL());
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('token', token);

            // Enviar datos al servidor
            submitForm(formData);
        });

        function submitForm(formData) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('Error: Token CSRF no encontrado');
                return;
            }

            fetch('{{ route('gimnasio.guardar') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', // Importante para AJAX
                        'Accept': 'application/json', // Forzar respuesta JSON
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Registro exitoso');
                        // Redireccionar o limpiar formulario
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar el registro');
                });
        }
    </script>
</body>

</html>

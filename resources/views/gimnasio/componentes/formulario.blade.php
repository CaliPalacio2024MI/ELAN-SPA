<!-- Paso 1: Selección de tipo de usuario -->
<div class="step {{ isset($startActive) && $startActive ? 'active' : '' }}" id="step-selection">
    @if (isset($showBackToQR) && $showBackToQR)
        <div style="display: flex; align-items: center; justify-content: center; margin-top: 1rem;">
            <button type="button" class="btn" onclick="showQRStep()">
                <i class="fas fa-qrcode" style="padding-right: 10px;"></i> Volver al QR
            </button>
        </div>
    @endif

    <div class="header">
        <h2>¿Quién va a usar el gimnasio?</h2>
    </div>
    <div class="selection-buttons">
        <button class="selection-btn" onclick="showAdultForm()">
            <i class="fas fa-user"></i><br>
            <strong>ADULTO</strong><br>
            (18+ años)
        </button>
        <button class="selection-btn" onclick="showMinorForm()">
            <i class="fas fa-child"></i><br>
            <strong>MENOR</strong><br>
            (15-17 años)
        </button>
    </div>
</div>

<!-- Paso 2: Formulario para adultos -->
<div class="step" id="step-adult">
    <div style="width: 80%; margin-top: 1rem;">
        <button type="button" class="btn" onclick="showSelection()"><i class="fas fa-arrow-left" style="padding-right: 10px;"></i>Regresar</button>
    </div>
    
    <div class="header">
        <h2>Registro - Adulto</h2>
    </div>

    <form id="form-adult">
        <div class="aviso-legal">
            <strong>AVISO LEGAL:</strong><br>
            A través de mi firma, expreso mi voluntad para deslindar a <span style="color: red;">{{ $hotelName }}</span>
                (Organización Ideal, S. de R.L. de C.V.), sus accionistas, directivos, representantes y
            empleados, a fin de que estén y se mantengan libres, sin carga y no tengan ninguna responsabilidad por
            cualquier lesión corporal, menoscabo o pérdida que resulte como consecuencia inmediata, mediata o remota de
            las rutinas que realice -sean o no con aparatos y/o equipos de entrenamiento-, por ende, asumo la
            responsabilidad de las rutinas que realizo y del uso correcto de los aparatos y equipos de entrenamiento.
            Renunciando a ejercer cualquier acción legal o judicial en contra de ellos por dicha situación.
            <br><br>
            Through my signature, I express my will to delimit to <span style="color: red;">{{ $hotelName }}</span>
                 (Organización Ideal, S. de R.L. de C.V.), its shareholders, directors, representatives
            and employees, so that they are and remain free, without charge and have no responsibility for any bodily
            injury, impairment or loss that results as an immediate, mediate or remote consequence of the routines that
            I carry out, whether or not it be with training devices and / or equipment. Therefore, I assume
            responsibility for the routines that I carry out and the correct use of all training apparatus and
            equipment, Waiving to exercise any legal or judicial action against them for said situation.
        </div>

        <div class="form-group">
            <label for="nombre_adulto">Nombre completo *</label>
            <input type="text" id="nombre_adulto" name="nombre_huesped" required>
        </div>

        <div class="form-group">
            <label for="nombre_adulto">Numero de Habitacion *</label>
            <input type="text" id="nombre_adulto" name="nombre_huesped" required>
        </div>

        


        <div class="form-group">
            <label>Firma *</label>
            <button type="button" onclick="clearSignature('firma_adulto')" class="btn-limpiar">Limpiar Firma</button>
            <canvas id="firma_adulto" class="firma-canvas" width="400" height="200"></canvas>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: center;">
            <button type="submit" class="btn">Completar</button>
        </div>
    </form>
</div>

<!-- Paso 3: Formulario para menores -->
<div class="step" id="step-minor">
    <div style="width: 80%; margin-top: 1rem;">
        <button type="button" class="btn" onclick="showSelection()"><i class="fas fa-arrow-left" style="padding-right: 10px;"></i>Regresar</button>
    </div>

    <div class="header">
        <h2>Registro - Menor de Edad</h2>
    </div>

    <form id="form-minor">
        <div class="aviso-legal">
            <strong>AVISO LEGAL:</strong><br>
            Firmo de enterada(o) acerca de la restricción de acceso para menores de edad en el Gimnasio ubicado en
            Palacio Mundo Imperial.
            Me hago totalmente responsable de cualquier lesión o accidente que mi hijo pudiera sufrir, es de mi
            consentimiento que el uso del Gimnasio es solo para mayores de edad y aun sabiendo los riesgos que
            representa asumo las responsabilidades que el mal uso de los aparatos pudiera causar a mi hijo(a).
            Absteniéndome de responsabilizar a Palacio Mundo Imperial y a ELAN Spa & Wellness Experience.
        </div>

        <h3>Datos del Menor</h3>
        <div class="menores-container">
            <div class="form-group">
                <label for="nombre_menor">Nombre completo del menor *</label>
                <input type="text" id="nombre_menor" name="nombre_menor" required>
            </div>
            <div class="form-group">
                <label for="edad_menor">Edad *</label>
                <select id="edad_menor" name="edad" required>
                    <option value="">Seleccionar edad</option>
                    <option value="15">15 años</option>
                    <option value="16">16 años</option>
                    <option value="17">17 años</option>
                </select>
            </div>
        </div>
        <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <button type="button" onclick="agregarMenor()" class="btn-agregar-menor"><i class="fas fa-plus" style="padding-right: 10px;"></i>Agregar Menor</button>
        </div>
        
        <h3>Datos del Padre/Tutor</h3>
        
        <div class="form-group">
            <label for="nombre_tutor">Nombre completo del padre/tutor *</label>
            <input type="text" id="nombre_tutor" name="nombre_tutor" required>
        </div>

        

        <div class="form-group">
            <label for="telefono_tutor">Teléfono del padre/tutor *</label>
            <input type="tel" id="telefono_tutor" name="telefono_tutor" required>
        </div>

        <div class="form-group">
            <label>Firma del Padre/Tutor *</label>
            <button type="button" onclick="clearSignature('firma_tutor')" class="btn-limpiar">Limpiar Firma</button>
            <canvas id="firma_tutor" class="firma-canvas" width="400" height="200"></canvas>
        </div>

        <h3>Datos del Anfitrión (Empleado del Hotel)</h3>
        <div class="form-group">
            <label for="nombre_anfitrion">Nombre del anfitrión *</label>
            <input type="text" id="nombre_anfitrion" name="nombre_anfitrion" required>
        </div>
        <div class="form-group">
            <label>Firma del Anfitrión *</label>
            <button type="button" onclick="clearSignature('firma_anfitrion')" class="btn-limpiar">Limpiar Firma</button>
            <canvas id="firma_anfitrion" class="firma-canvas" width="400" height="200"></canvas>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: center;">
            <button type="submit" class="btn">Completar</button>
        </div>
    </form>
</div>

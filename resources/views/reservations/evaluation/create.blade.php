<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Formulario de Evaluación Médica</title>

  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/sabana_reservaciones/evaluation_form.css'])
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>

  @php
    $soloLectura = $soloLectura ?? false;
    $datos = $datosGuardados ?? [];

    // Autocompletar datos si es un formulario nuevo y existe la reservación
    if (empty($datos) && isset($reservation)) {
        $cliente = $reservation->cliente;
        
        // Generar No. Expediente consecutivo global (independiente del cliente)
        $maxIdEncontrado = 0;
        $posiblesTablas = ['evaluations', 'evaluaciones', 'expedientes', 'medical_evaluations', 'medical_forms', 'evaluation_forms', 'reservation_evaluations'];
        
        // Intentar detectar tabla desde el modelo si existe
        if (class_exists('App\Models\Evaluation')) {
            array_unshift($posiblesTablas, (new \App\Models\Evaluation)->getTable());
        }

        // Intentar detectar tabla desde relaciones de la reservación
        if (isset($reservation)) {
            $relaciones = ['evaluation', 'evaluacion', 'medicalEvaluation', 'medical_evaluation'];
            foreach ($relaciones as $rel) {
                if (method_exists($reservation, $rel)) {
                    try {
                        $related = $reservation->$rel()->getRelated();
                        array_unshift($posiblesTablas, $related->getTable());
                    } catch (\Exception $e) {}
                }
            }
        }

        foreach (array_unique($posiblesTablas) as $tabla) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                    continue;
                }

                // Intentar obtener el consecutivo desde num_expediente (si existe columna) o id
                $currentMax = 0;
                $maxNumExp = 0;

                if (\Illuminate\Support\Facades\Schema::hasColumn($tabla, 'num_expediente')) {
                    $val = \Illuminate\Support\Facades\DB::table($tabla)->selectRaw('MAX(CAST(num_expediente AS UNSIGNED)) as m')->value('m');
                    $maxNumExp = $val ? intval($val) : 0;
                }
                
                // Obtener max ID como respaldo o referencia principal si es mayor
                $maxId = \Illuminate\Support\Facades\DB::table($tabla)->max('id');
                
                // Tomar el valor más alto entre el consecutivo guardado y el ID físico
                $currentMax = max($maxNumExp, $maxId);

                if (!is_null($currentMax)) {
                    if ($currentMax > $maxIdEncontrado) {
                        $maxIdEncontrado = $currentMax;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        $consecutivo = $maxIdEncontrado + 1;
        $datos['num_expediente'] = str_pad($consecutivo, 6, '0', STR_PAD_LEFT);
        $datos['lugar_fecha'] = date('d/m/Y');

        if ($cliente) {
            $datos['nombre_paciente'] = trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellido_paterno ?? '') . ' ' . ($cliente->apellido_materno ?? ''));
            $datos['correo'] = $cliente->email ?? $cliente->correo ?? ''; 
            $datos['telefono'] = $cliente->telefono ?? '';
            $datos['fecha_nacimiento'] = $cliente->fecha_nacimiento ?? '';
            
            if (!empty($cliente->fecha_nacimiento)) {
                try {
                    $datos['edad'] = \Carbon\Carbon::parse($cliente->fecha_nacimiento)->age;
                } catch (\Exception $e) {}
            }
        }

        $anfitrion = $reservation->anfitrion ?? null;
        $datos['terapeuta'] = $anfitrion ? trim(($anfitrion->nombre_usuario ?? '') . ' ' . ($anfitrion->apellido_paterno ?? '') . ' ' . ($anfitrion->apellido_materno ?? '')) : '';
        $datos['tratamiento'] = $reservation->experiencia->nombre ?? '';
        $datos['firma_doctor_nombre'] = $datos['terapeuta'];
    }
  @endphp

<form method="POST"  action="{{ route('evaluation.store', ['reservation' => $reservation->id]) }}">
    @csrf

    <header class="encabezado-formulario">
      <img src="{{ asset('images/LOGO_ES.png') }}" alt="Logo ES" class="logo-izquierdo">
      <h1>Formulario de Evaluación Médica</h1>
      <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo MI" class="logo-derecho">
    </header>

<section>
  <table cellpadding="8" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th colspan="4">Identificación</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><label for="lugar_fecha">Lugar y Fecha:</label></td>
        <td><input type="text" id="lugar_fecha" name="lugar_fecha" value="{{ $datos['lugar_fecha'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td><label for="num_expediente">N. Expediente:</label></td>
        <td><input type="text" id="num_expediente" name="num_expediente" value="{{ $datos['num_expediente'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }} /></td>
      </tr>
      <tr>
        <td><label for="nombre_paciente">Nombre:</label></td>
        <td><input type="text" id="nombre_paciente" name="nombre_paciente" value="{{ $datos['nombre_paciente'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td><label for="menor_edad">Menor de edad:</label></td>
        <td><input type="text" id="menor_edad" name="menor_edad" value="{{ $datos['menor_edad'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
      </tr>
      <tr>
        <td><label for="nombre_tutor">Nombre del padre o tutor:</label></td>
        <td><input type="text" id="nombre_tutor" name="nombre_tutor" value="{{ $datos['nombre_tutor'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td><label for="firma_tutor">Firma del padre o tutor:</label></td>
<td>
  @if (!empty($formulario->firma_padre_url))
    <img src="{{ asset('storage/' . $formulario->firma_padre_url) }}" alt="Firma del padre/tutor" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_padre_url">Firmar</button>
    <input type="hidden" name="firma_padre_url" id="firma_padre_url">
  @endif
</td>

      </tr>
      <tr>
        <td><label for="fecha_nacimiento">Fecha de cumpleaños:</label></td>
        <td><input type="text" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ $datos['fecha_nacimiento'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td><label for="edad">Edad:</label></td>
        <td><input type="text" id="edad" name="edad" value="{{ $datos['edad'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
      </tr>
      <tr>
        <td><label for="correo">Correo:</label></td>
        <td><input type="email" id="correo" name="correo" value="{{ $datos['correo'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td><label for="telefono">Teléfono:</label></td>
        <td><input type="tel" id="telefono" name="telefono" value="{{ $datos['telefono'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
      </tr>
    </tbody>

    <thead>
      <tr>
        <th colspan="4">Terapia</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><label for="terapeuta">Terapeuta:</label></td>
        <td><input type="text" id="terapeuta" name="terapeuta" value="{{ $datos['terapeuta'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        <td rowspan="2"><label for="firma_terapeuta">Firma del Terapeuta:</label></td>
<td rowspan="2">
  @if (!empty($formulario->firma_terapeuta_url))
    <img src="{{ asset('storage/' . $formulario->firma_terapeuta_url) }}" alt="Firma del terapeuta" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_terapeuta_url">Firmar</button>
    <input type="hidden" name="firma_terapeuta_url" id="firma_terapeuta_url">
  @endif
</td>

      </tr>
      <tr>
        <td><label for="tratamiento">Tratamiento:</label></td>
        <td><input type="text" id="tratamiento" name="tratamiento" value="{{ $datos['tratamiento'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
      </tr>
    </tbody>
  </table>
</section>

<section style="margin-top: 2rem;">
  @include('/reservations/evaluation/txt_claridad')
  </section>
  
  <section style="margin-top: 2rem;">
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
      <tbody>
     
        <tr>
          <td>1. ¿Existe alguna condición física o de salud relevante para el tratamiento?</td>
          <td><input type="radio" id="p1_si" name="p1" value="si" {{ ($datos['p1'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p1_no" name="p1" value="no" {{ ($datos['p1'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
        <tr>
          <td>Especificar:</td>
          <td colspan="2"><input type="text" id="p1_especificar" name="p1_especificar" value="{{ $datos['p1_especificar'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
    
        <tr>
          <td>2. ¿Es alérgico a alguna sustancia, incluidos medicamentos o cosméticos?</td>
          <td><input type="radio" id="p2_si" name="p2" value="si" {{ ($datos['p2'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p2_no" name="p2" value="no" {{ ($datos['p2'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
        <tr>
          <td>Especificar:</td>
          <td colspan="2"><input type="text" id="p2_especificar" name="p2_especificar" value="{{ $datos['p2_especificar'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
      
        <tr>
          <td>3. ¿Sigue algún tratamiento médico actualmente?</td>
          <td><input type="radio" id="p3_si" name="p3" value="si" {{ ($datos['p3'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p3_no" name="p3" value="no" {{ ($datos['p3'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
        <tr>
          <td>Especificar (incluya medicamento):</td>
          <td colspan="2"><input type="text" id="p3_especificar" name="p3_especificar" value="{{ $datos['p3_especificar'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
     
        <tr>
          <td>4. ¿Ha tenido alguna herida, cirugía, quemadura, fractura o lesión reciente?</td>
          <td><input type="radio" id="p4_si" name="p4" value="si" {{ ($datos['p4'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p4_no" name="p4" value="no" {{ ($datos['p4'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
        <tr>
          <td>Indique tipo y lugar de la lesión:</td>
          <td colspan="2"><input type="text" id="p4_especificar" name="p4_especificar" value="{{ $datos['p4_especificar'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
     
        <tr>
          <td>5. ¿Está embarazada?</td>
          <td><input type="radio" id="p5_si" name="p5" value="si" {{ ($datos['p5'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p5_no" name="p5" value="no" {{ ($datos['p5'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
        <tr>
          <td>Indique número de semanas de gestación:</td>
          <td colspan="2"><input type="text" id="p5_semanas" name="p5_semanas" value="{{ $datos['p5_especificar'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
       
        <tr>
          <td>6. ¿Usa lentes de contacto?</td>
          <td><input type="radio" id="p6_si" name="p6" value="si" {{ ($datos['p6'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sí</td>
          <td><input type="radio" id="p6_no" name="p6" value="no" {{ ($datos['p6'] ?? '') === 'no' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> No</td>
        </tr>
  
      <tr class="page-break"></tr>

        <tr>
          <td rowspan="2">7. ¿Qué tipo de piel tiene?</td>
          <td><input type="radio" id="p7_seca" name="p7" value="seca" {{ ($datos['p7'] ?? '') === 'seca' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Seca</td>
          <td><input type="radio" id="p7_sensible" name="p7" value="sensible" {{ ($datos['p7'] ?? '') === 'sensible' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Sensible</td>
        </tr>
        <tr>
          <td><input type="radio" id="p7_grasa" name="p7" value="grasa" {{ ($datos['p7'] ?? '') === 'grasa' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Grasa</td>
          <td><input type="radio" id="p7_mixta" name="p7" value="mixta" {{ ($datos['p7'] ?? '') === 'mixta' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Mixta</td>
        </tr>
  
   
        <tr>
          <td rowspan="2">8. ¿Qué grado o nivel de presión prefiere el masaje?</td>
          <td><input type="radio" id="p8_suave" name="p8" value="suave" {{ ($datos['p8'] ?? '') === 'suave' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Suave</td>
          <td><input type="radio" id="p8_fuerte" name="p8" value="fuerte" {{ ($datos['p8'] ?? '') === 'fuerte' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Fuerte</td>
        </tr>
        <tr>
          <td colspan="2"><input type="radio" id="p8_media" name="p8" value="media" {{ ($datos['p8'] ?? '') === 'media' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/> Media</td>
        </tr>
      </tbody>
    </table>
  </section>
  
  <section style="margin-top: 2rem;">
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th colspan="8">Padecimientos Personales</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="8">
            Favor de marcar en caso de presentar o haber presentado alguna de las siguientes condiciones, enfermedades o padecimientos.
          </td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="artritis_si" name="artritis" value="si" {{ ($datos['artritis'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="artritis_si">Artritis (tipo - área)</label></td>
          <td colspan="2"><input type="text" id="artritis_detalles" name="artritis_detalles" value="{{ $datos['artritis_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="asma_si" name="asma" value="si" {{ ($datos['asma'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="asma_si">Asma</label></td>
  
          <td><input type="checkbox" id="sol_si" name="sol" value="si" {{ ($datos['sol'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="sol_si">Exposición excesiva al sol</label></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="cancer_si" name="cancer" value="si" {{ ($datos['cancer'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="cancer_si">Cáncer (tipo - área)</label></td>
          <td colspan="2"><input type="text" id="cancer_detalles" name="cancer_detalles" value="{{ $datos['cancer_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="epilepsia_si" name="epilepsia" value="si" {{ ($datos['epilepsia'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="epilepsia_si">Epilepsia</label></td>
  
          <td><input type="checkbox" id="exfoliacion_si" name="exfoliacion" value="si" {{ ($datos['exfoliacion'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="exfoliacion_si">Exfoliaciones en la piel</label></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="diabetes_si" name="diabetes" value="si" {{ ($datos['diabetes'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="diabetes_si">Diabetes (tipo)</label></td>
          <td colspan="2"><input type="text" id="diabetes_detalles" name="diabetes_detalles" value="{{ $datos['diabetes_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="mareos_si" name="mareos" value="si" {{ ($datos['mareos'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="mareos_si">Mareos</label></td>
  
          <td><input type="checkbox" id="dermatitis_si" name="dermatitis" value="si" {{ ($datos['dermatitis'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="dermatitis_si">Dermatitis o afectaciones en la piel</label></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="presion_si" name="presion" value="si" {{ ($datos['presion'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="presion_si">Presión (alta o baja)</label></td>
          <td colspan="2"><input type="text" id="presion_detalles" name="presion_detalles" value="{{ $datos['presion_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="renal_si" name="renal" value="si" {{ ($datos['renal'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="renal_si">Padecimiento renal</label></td>
  
          <td><input type="checkbox" id="depilacion_si" name="depilacion" value="si" {{ ($datos['depilacion'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="depilacion_si">Tratamiento de depilación definitiva (área)</label></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="calambres_si" name="calambres" value="si" {{ ($datos['calambres'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="calambres_si">Calambres (¿dónde?)</label></td>
          <td colspan="2"><input type="text" id="calambres_detalles" name="calambres_detalles" value="{{ $datos['calambres_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="coagulacion_si" name="coagulacion" value="si" {{ ($datos['coagulacion'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="coagulacion_si">Alteración en la coagulación</label></td>
          <td colspan="2"><input type="text" id="coagulacion_detalles" name="coagulacion_detalles" value="{{ $datos['coagulacion_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="cirugias_si" name="cirugias" value="si" {{ ($datos['cirugias'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="cirugias_si">Cirugías (área - tiempo)</label></td>
          <td colspan="2"><input type="text" id="cirugias_detalles" name="cirugias_detalles" value="{{ $datos['cirugias_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="condicion_otra_si" name="condicion_otra" value="si" {{ ($datos['condicion_otra'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="condicion_otra_si">Otra condición médica (especificar)</label></td>
          <td colspan="2"><input type="text" id="condicion_detalles" name="condicion_detalles" value="{{ $datos['condicion_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="torceduras_si" name="torceduras" value="si" {{ ($datos['torceduras'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="torceduras_si">Torceduras (área - tiempo)</label></td>
          <td colspan="2"><input type="text" id="torceduras_detalles" name="torceduras_detalles" value="{{ $datos['torceduras_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
  
          <td><input type="checkbox" id="auricular_si" name="auricular" value="si" {{ ($datos['auricular'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="auricular_si">Aparato auricular</label></td>
          <td colspan="2"><input type="text" id="auricular_detalles" name="auricular_detalles" value="{{ $datos['auricular_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>
        </tr>
  
        <tr>
          <td><input type="checkbox" id="columna_si" name="columna" value="si" {{ ($datos['columna'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="columna_si">Lesión en columna (área)</label></td>
          <td colspan="2"><input type="text" id="columna_detalles" name="columna_detalles" value="{{ $datos['columna_detalles'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/></td>

          <td><input type="checkbox" id="marcapasos_si" name="marcapasos" value="si" {{ ($datos['marcapasos'] ?? '') === 'si' ? 'checked' : '' }} {{ $soloLectura ? 'disabled' : '' }}/></td>
          <td><label for="marcapasos_si">Marcapasos</label></td>
        </tr>
      </tbody>
    </table>
  </section>
  
 
<section style="margin-top: 2rem;">
    <h3>Gracias por visitarnos</h3>
      @include('/reservations/evaluation/txt_responsabilidades')
  </section>
  
 
  <section style="margin-top: 2rem;">
    <h3>Consentimiento informado</h3>
      @include('/reservations/evaluation/txt_consentimiento')
  </section>
  
 
  <section style="margin-top: 2rem;">
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Campo</th>
          <th>Firma</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <label for="firma_paciente_nombre">Nombre completo del paciente:</label><br />
            <input type="text" id="firma_paciente_nombre" name="firma_paciente_nombre" value="{{ $datos['firma_paciente_nombre'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/>
          </td>
<td>
  @if (!empty($formulario->firma_paciente_url))
    <img src="{{ asset('storage/' . $formulario->firma_paciente_url) }}" alt="Firma del paciente" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_paciente_url">Firmar</button>
    <input type="hidden" name="firma_paciente_url" id="firma_paciente_url">
  @endif
</td>
        </tr>
  
        <tr>
          <td>
            <label for="firma_tutor_nombre">Nombre del padre y/o tutor (si aplica):</label><br />
            <input type="text" id="firma_tutor_nombre" name="firma_tutor_nombre" value="{{ $datos['firma_tutor_nombre'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/>
          </td>
<td>
  @if (!empty($formulario->firma_tutor_url))
    <img src="{{ asset('storage/' . $formulario->firma_tutor_url) }}" alt="Firma del tutor" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_tutor_url">Firmar</button>
    <input type="hidden" name="firma_tutor_url" id="firma_tutor_url">
  @endif
</td>
        </tr>
  
        <tr>
          <td>
            <label for="firma_doctor_nombre">Nombre del Doctor/Terapeuta:</label><br />
            <input type="text" id="firma_doctor_nombre" name="firma_doctor_nombre" value="{{ $datos['firma_doctor_nombre'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/>
          </td>
<td>
  @if (!empty($formulario->firma_doctor_url))
    <img src="{{ asset('storage/' . $formulario->firma_doctor_url) }}" alt="Firma del doctor" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_doctor_url">Firmar</button>
    <input type="hidden" name="firma_doctor_url" id="firma_doctor_url">
  @endif
</td>  
        </tr>
  
        <tr>
          <td>
            <label for="testigo1_nombre">Testigo 1:</label><br />
            <input type="text" id="testigo1_nombre" name="testigo1_nombre" value="{{ $datos['testigo1_nombre'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/>
          </td>
<td>
  @if (!empty($formulario->firma_testigo1_url))
    <img src="{{ asset('storage/' . $formulario->firma_testigo1_url) }}" alt="Firma del testigo 1" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_testigo1_url">Firmar</button>
    <input type="hidden" name="firma_testigo1_url" id="firma_testigo1_url">
  @endif
</td>      
        </tr>
  
        <tr>
          <td>
            <label for="testigo2_nombre">Testigo 2:</label><br />
            <input type="text" id="testigo2_nombre" name="testigo2_nombre" value="{{ $datos['testigo2_nombre'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}/>
          </td>
<td>
  @if (!empty($formulario->firma_testigo2_url))
    <img src="{{ asset('storage/' . $formulario->firma_testigo2_url) }}" alt="Firma del testigo 2" class="img-firma" />
  @elseif (!$soloLectura)
    <button type="button" class="btn btn-outline-primary btn-sm" data-firma-target="firma_testigo2_url">Firmar</button>
    <input type="hidden" name="firma_testigo2_url" id="firma_testigo2_url">
  @endif
</td>
        </tr>
          
        </tr>
      </tbody>
    </table>
  </section>
  
  @if (!$soloLectura)
    <div class="btn-guardar no-print">
        <button class="btn btn-primary" type="submit">Guardar evaluación</button>
    </div>
@else
    <div class="text-center mt-4 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
    </div>
@endif

</form>

<div class="modal fade" id="firmaModal" tabindex="-1" aria-labelledby="firmaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="firmaModalLabel">Dibujar firma</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <canvas id="canvasFirma" width="600" height="250" class="firma-canvas"></canvas>
      </div>
      <div class="modal-footer">
        <button type="button" id="btnLimpiarFirma" class="btn btn-warning">Limpiar</button>
        <button type="button" id="btnGuardarFirma" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

@vite('resources/js/reservation/checkin/checkin.js')
  </html>
  
<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\EvaluationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EvaluationFormController extends Controller
{
    // Mostrar formulario para una reservación, o vista solo lectura si ya hay check-in
    public function create(Reservation $reservation)
    {
        if ($reservation->check_in) {
            $formulario = EvaluationForm::where('reservation_id', $reservation->id)->firstOrFail();
            $preguntasRespuestas = json_decode($formulario->preguntas_respuestas, true);

            return view('reservations.evaluation.create', [
                'reservation' => $reservation,
                'soloLectura' => true,
                'datosGuardados' => $preguntasRespuestas,
                'formulario' => $formulario
            ]);
        }

        return view('reservations.evaluation.create', compact('reservation'));
    }

    // Guardar formulario de evaluación y almacenar firmas como imágenes
    public function store(Request $request, Reservation $reservation)
    {
        $data = $request->except('_token');

        $firmas = [
            'firma_paciente_url',
            'firma_tutor_url',
            'firma_doctor_url',
            'firma_testigo1_url',
            'firma_testigo2_url',
            'firma_padre_url',
            'firma_terapeuta_url',
        ];

        $firmasUrls = [];
        Log::info('Contenido de firma_padre:', [$request->input('firma_padre_url')]);

        foreach ($firmas as $campo) {
            if ($request->filled($campo)) {
                $base64 = $request->input($campo);
                $image = str_replace('data:image/png;base64,', '', $base64);
                $image = str_replace(' ', '+', $image);
                $nombreArchivo = 'firma_' . Str::random(10) . '.png';

                Storage::disk('public')->put('firmas/' . $nombreArchivo, base64_decode($image));
                $url = 'firmas/' . $nombreArchivo;

                $firmasUrls[$campo] = $url;
            }
        }

        EvaluationForm::create(array_merge([
            'reservation_id' => $reservation->id,
            'cliente_id' => $reservation->cliente_id,
            'preguntas_respuestas' => json_encode($data),
            'observaciones' => $request->input('observaciones'),
        ], $firmasUrls));

        $reservation->update(['check_in' => true]);

        return redirect()->route('reservations.index')->with('success', 'Formulario de evaluación registrado correctamente.');
    }

    // Mostrar formulario de evaluación en modo solo lectura
    public function show($reservationId)
    {
        $formulario = EvaluationForm::where('reservation_id', $reservationId)->firstOrFail();
        $reservation = Reservation::findOrFail($reservationId);
        $preguntasRespuestas = json_decode($formulario->preguntas_respuestas, true);

        return view('reservations.evaluation.create', [
            'reservation' => $reservation,
            'soloLectura' => true,
            'datosGuardados' => $preguntasRespuestas,
            'formulario' => $formulario
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Anfitrion;
use App\Models\AnfitrionOperativo;
use App\Models\HorarioAnfitrion;
use App\Models\Experience;

class AnfitrionController extends Controller
{
    // Lista anfitriones del spa actual y prepara datos para la vista
    public function index(Request $request)
    {
        $spaNombre = session('current_spa');

        if (!in_array($spaNombre, ['palacio', 'princess', 'pierre'])) {
            return abort(403, 'No se encontró un spa válido en la base de datos.');
        }

        $spa = Spa::where('nombre', $spaNombre)->first();
        if (!$spa) {
            return abort(403, 'No se encontró el spa en la base de datos.');
        }

        // Obtiene anfitriones del spa excepto rol master, con datos operativos cargados
        $anfitriones = Anfitrion::where('spa_id', $spa->id)
            ->where('rol', '!=', 'master')
            ->with('operativo')
            ->get();

        // Carga nombres de spas adicionales accesibles para cada anfitrion
        foreach ($anfitriones as $anfitrion) {
            $ids = is_array($anfitrion->accesos) 
                ? $anfitrion->accesos 
                : json_decode($anfitrion->accesos, true) ?? [];
            $anfitrion->spaNombres = Spa::whereIn('id', $ids)->pluck('nombre')->toArray();
        }

        $spaId = $spa->id;

        // Obtiene clases únicas de experiencias asociadas al spa actual
        $clasesDesdeExperiencias = Experience::where('spa_id', $spaId)
            ->pluck('clase')
            ->filter()
            ->map(fn($c) => trim($c))
            ->unique()
            ->values()
            ->all();

        // Obtiene clases desde registros operativos antiguos (JSON)
        $clasesDesdeOperativo = AnfitrionOperativo::pluck('clases_actividad')
            ->filter()
            ->flatMap(fn($json) => is_array($json) ? $json : json_decode($json, true) ?? [])
            ->map(fn($c) => trim($c))
            ->unique()
            ->values()
            ->all();

        // Combina y ordena todas las clases únicas
        $todasClases = collect($clasesDesdeExperiencias)
            ->merge($clasesDesdeOperativo)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $clasesDisponibles = Experience::where('spa_id', $spa->id)
            ->pluck('clase')
            ->unique()
            ->filter()
            ->values();

        return view('gestores.gestor_anfitriones', [
            'anfitriones' => $anfitriones,
            'spas' => Spa::all(),
            'spasDisponibles' => Spa::where('id', '!=', $spa->id)->get(),
            'todasClases' => $todasClases,
            'clasesDisponibles' => $clasesDisponibles,
        ]);
    }

    // Guarda nuevo anfitrion con validación y creación relacionada de operativo
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'RFC' => ['required', 'unique:anfitriones,RFC', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'nombre_usuario' => 'required|string|max:255',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
            'rol' => 'required|string',
            'departamento' => 'required|in:spa,gym,valet,salon de belleza',
            'accesos' => 'nullable|array',
            'accesos.*' => 'integer|exists:spas,id',
        ], [
            'RFC.regex' => 'El RFC no es válido, asegúrate de seguir el formato correcto.',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('anfitriones.index')
                ->withErrors($validator, 'create')
                ->withInput();
        }

        $clases = array_values(array_filter($request->input('clases_actividad', [])));
        $spa = Spa::where('nombre', session('current_spa'))->first();
        $spa_id = $spa?->id;

        if (!$spa_id) {
            Log::error("No se encontró un `spa_id` válido.");
            return redirect()->route('anfitriones.index')
                ->withErrors(['error' => 'No se pudo determinar el spa actual.'], 'create')
                ->withInput();
        }

        $anfitrion = Anfitrion::create([
            'RFC' => $request->RFC,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'nombre_usuario' => $request->nombre_usuario,
            'password' => bcrypt($request->password),
            'spa_id' => $spa_id,
            'rol' => $request->rol,
            'departamento' => $request->departamento,
            'accesos' => $request->filled('accesos') ? array_values(array_map('intval', $request->accesos)) : [],
            'activo' => $request->activo ?? true,
        ]);

        AnfitrionOperativo::create([
            'anfitrion_id' => $anfitrion->id,
            'departamento' => $request->departamento,
            'clases_actividad' => $clases,
        ]);

        return redirect()->back()->with('mensaje_exito', 'Anfitrión creado correctamente.');
    }

    // Actualiza anfitrion con validación y actualización relacionada
    public function update(Request $request, $id)
    {
        $anfitrion = Anfitrion::findOrFail($id);

        if ($anfitrion->rol === 'master') {
            return redirect()->route('anfitriones.index')
                ->withErrors(['error' => 'No se puede modificar un usuario con rol master.'], 'edit')
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'RFC' => ['required', 'unique:anfitriones,RFC,' . $anfitrion->id, 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'nombre_usuario' => 'required|string|max:255',
            'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
            'rol' => 'required|string',
            'departamento' => 'required|in:spa,gym,valet,salon de belleza',
            'accesos' => 'nullable|array',
            'accesos.*' => 'integer|exists:spas,id',
            'activo' => 'boolean',
        ], [
            'RFC.regex' => 'El RFC no es válido, asegúrate de seguir el formato correcto.',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('anfitriones.index')
                ->withErrors($validator, 'edit')
                ->withInput();
        }

        try {
            $anfitrion->update([
                'RFC' => $request->RFC,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'nombre_usuario' => $request->nombre_usuario,
                'rol' => $request->rol,
                'departamento' => $request->departamento,
                'accesos' => $request->filled('accesos') ? array_values(array_map('intval', $request->accesos)) : [],
                'activo' => $request->activo ?? true,
            ]);

            if ($request->filled('password')) {
                $anfitrion->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            $clases = array_values(array_filter($request->input('clases_actividad', [])));

            AnfitrionOperativo::updateOrCreate(
                ['anfitrion_id' => $anfitrion->id],
                [
                    'departamento' => $request->departamento,
                    'clases_actividad' => $clases
                ]
            );

            return redirect()->back()->with('mensaje_exito', 'Anfitrión editado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al actualizar anfitrión", ['error' => $e->getMessage()]);
            return redirect()->route('anfitriones.index')
                ->withErrors(['error' => 'No se pudo actualizar el anfitrión.'], 'edit')
                ->withInput();
        }
    }

    // Cambia estado activo/inactivo (toggle)
    public function toggleEstado(Anfitrion $anfitrion)
    {
        $anfitrion->activo = !$anfitrion->activo;
        $anfitrion->save();

        return response()->json([
            'success' => true,
            'activo' => $anfitrion->activo
        ]);
    }

    // Elimina anfitrion
    public function destroy($id)
    {
        $anfitrion = Anfitrion::findOrFail($id);
        $anfitrion->delete();

        return redirect()->back()->with('mensaje_exito', 'Anfitrión eliminado correctamente.');
    }

    // Muestra formulario para editar horario solo para anfitriones
    public function editHorario($id)
    {
        $anfitrion = Anfitrion::findOrFail($id);

        if ($anfitrion->rol !== 'anfitrion') {
            return redirect()->route('anfitriones.index')->with('error', 'Solo se pueden asignar horarios a anfitriones.');
        }

        $horario = $anfitrion->horario?->horarios ?? [];

        return view('gestores.horarios_anfitrion', compact('anfitrion', 'horario'));
    }

    // Guarda o actualiza horarios asignados a anfitrion
    public function storeHorario(Request $request, $id)
    {
        $anfitrion = Anfitrion::findOrFail($id);
        $horarios = $request->input('horarios', []);

        HorarioAnfitrion::updateOrCreate(
            ['anfitrion_id' => $anfitrion->id],
            ['horarios' => $horarios]
        );

        return redirect()->route('anfitriones.index')->with('mensaje_exito', 'Horario guardado correctamente.');
    }
}

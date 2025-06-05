<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Experience;
use App\Models\Spa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExperienceController extends Controller
{
    // Mostrar lista de experiencias para el spa actual
    public function index()
    {
        $spaNombre = session('current_spa');

        if (!$spaNombre) {
            return back()->withErrors(['spa_id' => 'No se encontró un spa asignado.']);
        }

        $spa = Spa::where('nombre', $spaNombre)->first();

        if (!$spa) {
            return back()->withErrors(['spa_id' => 'No se encontró el spa en la base de datos.']);
        }

        $experiences = Experience::where('spa_id', $spa->id)->get();

        $clase = Experience::where('spa_id', $spa->id)
            ->select('clase')
            ->distinct()
            ->pluck('clase');

        return view('gestores.gestor_experiencias', compact('experiences', 'clase'));
    }

    // Crear nueva experiencia validando datos y asignando spa actual
    public function store(Request $request)
    {
        Log::info("Intentando crear una experiencia", [
            'datos_recibidos' => $request->all(),
            'session_current_spa' => session('current_spa'),
            'auth_user_area' => Auth::user()->area
        ]);

        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'clase' => 'required|string',
            'duracion' => 'required|integer|min:10',
            'precio' => 'required|numeric|min:0',
            'color' => 'nullable|string|max:10',
        ]);

        $spaNombre = session('current_spa') ?? Auth::user()->area;
        $spa = Spa::where('nombre', $spaNombre)->first();

        if (!$spa) {
            Log::error("No se encontró un spa con el nombre: $spaNombre");
            return back()->withErrors(['spa_id' => 'No se encontró un spa asignado.']);
        }

        $experience = Experience::create([
            'spa_id' => $spa->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'clase' => $request->clase,
            'duracion' => $request->duracion,
            'precio' => $request->precio,
            'color' => $request->color,
        ]);

        Log::info("Experiencia creada correctamente", [
            'experience_id' => $experience->id,
            'experience' => $experience
        ]);

        return redirect()->route('experiences.index')->with('success', 'Experiencia creada correctamente.');
    }

    // Actualizar experiencia existente con validación
    public function update(Request $request, Experience $experience)
    {
        Log::info("ExperienceController@update ejecutado", [
            'id' => $experience->id,
            'datos_recibidos' => $request->all()
        ]);

        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'clase' => 'required|string',
            'duracion' => 'required|integer|min:10',
            'precio' => 'required|numeric|min:0',
            'activo' => 'required|boolean',
            'color' => 'nullable|string|max:10',
        ]);

        $experience->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'clase' => $request->clase,
            'duracion' => $request->duracion,
            'precio' => $request->precio,
            'color' => $request->color,
            'activo' => $request->activo,
        ]);

        Log::info("Experiencia actualizada correctamente", [
            'experiencia' => $experience
        ]);

        return redirect()->route('experiences.index')->with('success', 'Experiencia actualizada correctamente.');
    }

    // Eliminar experiencia
    public function destroy(Experience $experience)
    {
        $experience->delete();
        return redirect()->route('experiences.index')->with('success', 'Experiencia eliminada correctamente.');
    }

    // Alternar estado activo/inactivo de la experiencia (AJAX)
    public function toggleEstado(Experience $experience)
    {
        $experience->activo = !$experience->activo;
        $experience->save();

        return response()->json([
            'success' => true,
            'activo' => $experience->activo,
        ]);
    }
}

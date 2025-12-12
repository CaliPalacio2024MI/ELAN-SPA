<?php

namespace App\Http\Controllers;

use App\Models\Cabina;
use App\Models\Spa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CabinaController extends Controller
{
    // Muestra la lista de cabinas asociadas al spa actual
    public function index(Request $request)
    {
        $spaNombre = session('current_spa');
        if (!$spaNombre) {
            return abort(403, 'No se encontró un spa válido en la sesión.');
        }

        $spa = Spa::where('nombre', $spaNombre)->first();
        if (!$spa) {
            return abort(403, 'No se encontró el spa en la base de datos.');
        }

        $query = Cabina::where('spa_id', $spa->id);

        // Filtrado por término de búsqueda (nombre o clase)
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('clase', 'like', "%{$search}%");
            });
        }

        $cabinas = $query->get();

        // Obtiene clases únicas de experiencias para filtro o uso en la vista
        $clasesDisponibles = \App\Models\Experience::where('spa_id', $spa->id)
            ->pluck('clase')
            ->unique()
            ->filter()
            ->values();

        // Agrupa experiencias por clase para poblar subtipos (ej. tipos de masaje)
        $experienciasPorClase = \App\Models\Experience::where('spa_id', $spa->id)
            ->get()
            ->groupBy('clase')
            ->map(function ($col) {
                return $col->pluck('nombre')->values();
            })
            ->toArray();

        return view('gestores.gestor_cabinas', compact('cabinas', 'clasesDisponibles', 'experienciasPorClase'));
    }

    // Valida y guarda una nueva cabina
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'clase' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
            'clases_actividad' => 'required|array|min:1',
            'clases_actividad.0' => 'string|max:255',
        ]);

        $spa = Spa::where('nombre', session('current_spa'))->first();
        if (!$spa) {
            return back()->withErrors(['error' => 'Spa inválido.'])->withInput()->withErrors([], 'create');
        }

        Cabina::create([
            'nombre' => $request->nombre,
            'clase' => $request->clase,
            'spa_id' => $spa->id,
            'activo' => $request->activo,
            'clases_actividad' => $request->clases_actividad ?? [],
        ]);

        return redirect()->route('cabinas.index')->with('success', 'Cabina creada correctamente.');
    }

    // Valida y actualiza una cabina existente
    public function update(Request $request, $id)
    {
        $cabina = Cabina::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'clase' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
            'clases_actividad' => 'required|array|min:1',
            'clases_actividad.0' => 'string|max:255',
        ]);

        try {
            $cabina->update([
                'nombre' => $request->nombre,
                'clase' => $request->clase,
                'activo' => $request->activo,
                'clases_actividad' => $request->clases_actividad ?? [],
            ]);

            return redirect()->route('cabinas.index')->with('success', 'Cabina actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al actualizar cabina", ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo actualizar la cabina.'])->withInput()->withErrors([], 'edit');
        }
    }

    // Alterna estado activo/inactivo de la cabina vía AJAX
    public function toggleEstado(Cabina $cabina)
    {
        $cabina->activo = !$cabina->activo;
        $cabina->save();

        return response()->json(['success' => true, 'activo' => $cabina->activo]);
    }

    // Elimina una cabina
    public function destroy($id)
    {
        $cabina = Cabina::findOrFail($id);
        $cabina->delete();

        return redirect()->route('cabinas.index')->with('success', 'Cabina eliminada correctamente.');
    }
}

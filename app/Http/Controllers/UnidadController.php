<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UnidadController extends Controller
{
    // Guarda una nueva unidad (maneja subida de logos)
    /**
     * Almacena una nueva unidad en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_unidad' => 'required|string|max:255|unique:unidades,nombre_unidad',
            'color_unidad' => 'required|string|max:7',
            'logo_unidad_superior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_inferior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
 
        DB::beginTransaction();
        try {
            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
                'spa_id' => session('current_spa_id') ?? null,
            ];
 
            $this->handleLogoUpload($request, 'logo_unidad_superior', $data, 'logo_superior');
            $this->handleLogoUpload($request, 'logo_unidad_inferior', $data, 'logo_inferior');
            
            Unidad::create($data);
            DB::commit();
            return redirect()->route('unidades.create')->with('success', 'Unidad creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear unidad: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'No se pudo guardar la unidad.'])->withInput();
        }
    }

    /**
     * Muestra el formulario para editar una unidad específica.
     *
     * @param \App\Models\Unidad $unidad
     * @return \Illuminate\View\View
     */
    public function edit(Unidad $unidad)
    {
        // Gracias al Route Model Binding, Laravel ya nos da la unidad.
        // Asegúrate de que la vista se llame 'modulos.edit' como la creamos antes.
        return view('modulos.edit', compact('unidad'));
    }

    /**
     * Actualiza la unidad especificada en la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Unidad $unidad
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Unidad $unidad)
    {
        $validated = $request->validate([
            'nombre_unidad' => 'required|string|max:255|unique:unidades,nombre_unidad,' . $unidad->id,
            'color_unidad' => 'required|string|max:7',
            'logo_unidad_superior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_inferior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
 
        DB::beginTransaction();
        try {
            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
            ];
 
            $this->handleLogoUpload($request, 'logo_unidad_superior', $data, 'logo_superior', $unidad);
            $this->handleLogoUpload($request, 'logo_unidad_inferior', $data, 'logo_inferior', $unidad);
 
            $unidad->update($data);
            DB::commit();
            return redirect()->route('unidades.create')->with('success', 'Unidad actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar unidad: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'No se pudo actualizar la unidad.'])->withInput();
        }
    }

    // Elimina una unidad
    public function destroy(Unidad $unidad)
    {
        DB::beginTransaction();
        try {
            // Eliminar logos si existen
            if ($unidad->logo_superior) Storage::disk('public')->delete($unidad->logo_superior);
            if ($unidad->logo_inferior) {
                Storage::disk('public')->delete($unidad->logo_inferior);
            }
 
            $unidad->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Unidad eliminada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar unidad: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar la unidad.'], 500);
        }
    }

    /**
     * Establece la unidad seleccionada en la sesión del usuario.
     *
     * @param \App\Models\Unidad $unidad
     * @return \Illuminate\Http\JsonResponse
     */
    public function select(Unidad $unidad)
    {
        // Guardamos el ID y el nombre de la unidad en la sesión
        session(['current_unidad_id' => $unidad->id]);
        session(['current_unidad_nombre' => $unidad->nombre_unidad]);

        return response()->json(['success' => true, 'message' => 'Unidad seleccionada.']);
    }

    private function handleLogoUpload(Request $request, string $fileKey, array &$data, string $dataKey, ?Unidad $unidad = null)
    {
        // Si no se está actualizando, necesitamos el nombre de la unidad para crear la carpeta
        $unidadNombre = $unidad ? $unidad->nombre_unidad : $data['nombre_unidad'];
        $slug = Str::slug($unidadNombre);
        $directory = "images/{$slug}";

        if ($request->hasFile($fileKey)) {
            // Elimina el logo anterior si existe
            if ($unidad && $unidad->{$dataKey}) {
                // Usamos el disco 'public_path' para interactuar con la carpeta public/
                Storage::disk('public_path')->delete($unidad->{$dataKey});
            }
            // Definimos el nombre del archivo según la clave (logo.png o decorativo.png)
            $fileName = ($dataKey === 'logo_superior') ? 'logo.png' : 'decorativo.png';
            $path = $request->file($fileKey)->storeAs($directory, $fileName, 'public_path');
            $data[$dataKey] = $path; // Guardamos la ruta relativa: 'images/mi-unidad/logo.png'
        }
    }
}
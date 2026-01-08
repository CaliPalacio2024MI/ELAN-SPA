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
    /**
     * Muestra el formulario para crear una nueva unidad, junto con las existentes.
     */
    public function create()
    {
        // Pasamos las unidades y spas desde el controlador a la vista.
        $unidades = Unidad::orderBy('created_at', 'desc')->get();
        $spas = \App\Models\Spa::all(); // Asumiendo que quieres mostrar todos los spas fijos.

        return view('modulos.create', compact('unidades', 'spas'));
    }

    // Guarda una nueva unidad (maneja subida de logos)
    /**
     * Almacena una nueva unidad en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_unidad' => 'required|string|max:255|unique:unidades,nombre_unidad|unique:spas,nombre',
            'color_unidad' => 'required|string|max:7',
            'logo_unidad_superior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_principal' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_inferior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
 
        DB::beginTransaction();
        try {
            // 1. Crear un nuevo registro en la tabla 'spas' con el nombre de la unidad.
            $newSpa = \App\Models\Spa::create([
                'nombre' => $validated['nombre_unidad'],
            ]);

            // 2. Asignar acceso de la nueva unidad al usuario que la crea.
            $user = auth()->user();
            if ($user) {
                // El modelo User debería tener un cast para 'accesos' a 'array' o 'json'
                $accesos = is_array($user->accesos) ? $user->accesos : json_decode($user->accesos, true) ?? [];
                if (!in_array($newSpa->id, $accesos)) {
                    $accesos[] = $newSpa->id;
                    $user->accesos = $accesos;
                    $user->save();
                }
            }

            // 3. Preparar los datos para la tabla 'unidades', usando el ID del nuevo spa.
            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
                'spa_id' => $newSpa->id,
            ];
 
            $this->handleLogoUpload($request, 'logo_unidad_superior', $data, 'logo_superior');
            $this->handleLogoUpload($request, 'logo_unidad_principal', $data, 'logo_unidad');
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
            'nombre_unidad' => 'required|string|max:255|unique:unidades,nombre_unidad,' . $unidad->id . '|unique:spas,nombre,' . $unidad->spa_id,
            'color_unidad' => 'required|string|max:7',
            'logo_unidad_superior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_principal' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_unidad_inferior' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
 
        DB::beginTransaction();
        try {
            // Buscar el spa asociado para actualizar su nombre también.
            $spa = \App\Models\Spa::find($unidad->spa_id);

            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
            ];
 
            $this->handleLogoUpload($request, 'logo_unidad_superior', $data, 'logo_superior', $unidad);
            $this->handleLogoUpload($request, 'logo_unidad_principal', $data, 'logo_unidad', $unidad);
            $this->handleLogoUpload($request, 'logo_unidad_inferior', $data, 'logo_inferior', $unidad);
 
            $unidad->update($data);

            if ($spa) {
                $spa->update(['nombre' => $validated['nombre_unidad']]);
            }

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
            // Buscar el spa asociado para eliminarlo también.
            $spaIdToDelete = $unidad->spa_id;
            $spa = \App\Models\Spa::find($unidad->spa_id);

            // Eliminar logos si existen
            if ($unidad->logo_unidad) Storage::disk('public_path')->delete($unidad->logo_unidad);
            if ($unidad->logo_superior) Storage::disk('public_path')->delete($unidad->logo_superior);
            if ($unidad->logo_inferior) {
                Storage::disk('public_path')->delete($unidad->logo_inferior);
            }

            // Eliminar la unidad.
            $unidad->delete();

            // Eliminar el spa asociado, si existe.
            if ($spa) {
                // Antes de eliminar el spa, quitar el ID de los accesos de todos los usuarios.
                $usersToUpdate = \App\Models\User::whereJsonContains('accesos', $spaIdToDelete)->get();
                foreach ($usersToUpdate as $user) {
                    $accesos = array_filter($user->accesos, fn($id) => $id != $spaIdToDelete);
                    $user->accesos = array_values($accesos); // Re-indexar el array
                    $user->save();
                }
                $spa->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Unidad eliminada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar unidad: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return response()->json(['success' => false, 'message' => 'No se pudo eliminar la unidad porque tiene datos asociados (como reservaciones).'], 500);
            }
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
        // La unidad ya tiene un spa_id asociado que es su ID en la tabla 'spas'
        $spaModel = \App\Models\Spa::find($unidad->spa_id);

        if ($spaModel) {
            session([
                'current_spa' => strtolower($spaModel->nombre),
                'current_spa_id' => $spaModel->id,
                'current_unidad_color' => $unidad->color_unidad, // Guardamos el color para el menú
            ]);
            return response()->json(['success' => true, 'message' => 'Unidad seleccionada.']);
        }

        Log::warning("No se pudo encontrar el spa asociado (ID: {$unidad->spa_id}) para la unidad ID: {$unidad->id}.");
        return response()->json(['success' => false, 'message' => 'No se pudo encontrar el spa asociado a la unidad.'], 404);
    }

    private function handleLogoUpload(Request $request, string $fileKey, array &$data, string $dataKey, ?Unidad $unidad = null)
    {
        // Usamos siempre el nombre de la unidad que se está guardando (nuevo o actualizado)
        // para asegurar que el nombre del directorio coincida con el nombre de la unidad.
        if (empty($data['nombre_unidad'])) {
            Log::error("handleLogoUpload fue llamado sin 'nombre_unidad' en los datos.");
            return;
        }

        $slug = Str::slug($data['nombre_unidad']);
        if (empty($slug)) {
            Log::warning("No se pudo generar un slug para el nombre de unidad: " . $data['nombre_unidad']);
            // Detenemos para evitar crear carpetas sin nombre.
            return;
        }

        $directory = "images/{$slug}";

        if ($request->hasFile($fileKey)) {
            try {
                // Elimina el logo anterior si estamos actualizando y existe uno.
                if ($unidad && $unidad->{$dataKey}) {
                    Storage::disk('public_path')->delete($unidad->{$dataKey});
                }

                // Definimos el nombre del archivo según la clave
                $fileName = 'default.png';
                if ($dataKey === 'logo_superior') {
                    $fileName = 'logo.png';
                } elseif ($dataKey === 'logo_inferior') {
                    $fileName = 'decorativo.png';
                } elseif ($dataKey === 'logo_unidad') {
                    $fileName = 'logounidad.png';
                }

                // Guardar el nuevo archivo y obtener su ruta.
                $path = $request->file($fileKey)->storeAs($directory, $fileName, 'public_path');
                $data[$dataKey] = $path; // Guardamos la ruta relativa: 'images/mi-unidad/logo.png'

            } catch (\Exception $e) {
                Log::error("Error al subir el archivo '{$fileKey}' para la unidad '{$data['nombre_unidad']}': " . $e->getMessage());
                // Re-lanzamos la excepción para que la transacción principal haga rollback.
                throw $e;
            }
        }
    }
}
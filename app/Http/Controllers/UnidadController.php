<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Anfitrion;
use Illuminate\Database\QueryException;
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
            'color_icon' => 'nullable|string|max:7',
            'color_text' => 'nullable|string|max:7',
            // Añadimos validación para los nuevos colores de logout
            'color_logout_text_color' => 'nullable|string|max:7',
            'color_logout_icon_color' => 'nullable|string|max:7',
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

            // --- INICIO: Cálculo automático de colores del tema ---
            $baseColor = $validated['color_unidad'];
            $hoverColor = $this->adjust_color_brightness($baseColor, -0.30); // 30% más oscuro
            $submenuLinkColor = $this->adjust_color_brightness($baseColor, 0.15); // 15% más claro
            // --- FIN: Cálculo automático de colores del tema ---

            // 3. Preparar los datos para la tabla 'unidades', usando el ID del nuevo spa.
            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
                'spa_id' => $newSpa->id,
                'color_sidebar_bg' => $baseColor,
                'color_sidebar_hover_bg' => $hoverColor,
                'color_icon' => $validated['color_icon'] ?? null,
                'color_text' => $validated['color_text'] ?? null,
                'color_submenu_bg' => $baseColor,
                'color_submenu_link_bg' => $submenuLinkColor,
                'color_submenu_link_hover_bg' => $hoverColor,
                // Añadimos los nuevos colores a los datos para guardar
                'color_logout_text_color' => $validated['color_logout_text_color'] ?? null,
                'color_logout_icon_color' => $validated['color_logout_icon_color'] ?? null,
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
            'color_icon' => 'nullable|string|max:7',
            'color_text' => 'nullable|string|max:7',
            // Añadimos validación para los nuevos colores de logout
            'color_logout_text_color' => 'nullable|string|max:7',
            'color_logout_icon_color' => 'nullable|string|max:7',
        ]);
 
        DB::beginTransaction();
        try {
            // Buscar el spa asociado para actualizar su nombre también.
            $spa = \App\Models\Spa::find($unidad->spa_id);

            // --- INICIO: Cálculo automático de colores del tema ---
            $baseColor = $validated['color_unidad'];
            $hoverColor = $this->adjust_color_brightness($baseColor, -0.30); // 30% más oscuro
            $submenuLinkColor = $this->adjust_color_brightness($baseColor, 0.15); // 15% más claro
            // --- FIN: Cálculo automático de colores del tema ---

            $data = [
                'nombre_unidad' => $validated['nombre_unidad'],
                'color_unidad' => $validated['color_unidad'],
                'color_sidebar_bg' => $baseColor,
                'color_sidebar_hover_bg' => $hoverColor,
                'color_icon' => $validated['color_icon'] ?? null,
                'color_text' => $validated['color_text'] ?? null,
                'color_submenu_bg' => $baseColor,
                'color_submenu_link_bg' => $submenuLinkColor,
                'color_submenu_link_hover_bg' => $hoverColor,
                // Añadimos los nuevos colores a los datos para actualizar
                'color_logout_text_color' => $validated['color_logout_text_color'] ?? null,
                'color_logout_icon_color' => $validated['color_logout_icon_color'] ?? null,
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
        // Primero, obtenemos toda la información necesaria del modelo antes de eliminarlo.
        $spaIdToDelete = $unidad->spa_id;
        $slug = Str::slug($unidad->nombre_unidad);
        $directoryPath = "images/{$slug}";

        DB::beginTransaction();
        try {
            // 1. Revocar acceso a todos los usuarios que tuvieran esta unidad/spa.
            if ($spaIdToDelete) {
                // Se corrige el modelo a Anfitrion, que es el que contiene la columna 'accesos'.
                // El modelo User genérico no tiene esta columna.
                $anfitrionesToUpdate = Anfitrion::whereJsonContains('accesos', $spaIdToDelete)->get();
                foreach ($anfitrionesToUpdate as $user) { // 'user' aquí es una instancia de Anfitrion
                    $accesos = is_array($user->accesos) ? $user->accesos : (json_decode($user->accesos, true) ?? []);
                    $accesos = array_filter($accesos, fn($id) => $id != $spaIdToDelete);
                    $user->accesos = array_values($accesos); // Re-indexar el array
                    $user->save();
                }
            }

            // 2. Eliminar la unidad (el registro "hijo").
            // Esto es crucial y debe hacerse ANTES de intentar eliminar el spa para evitar
            // errores de restricción de clave foránea.
            $unidad->delete();

            // 3. Eliminar el registro del spa (el registro "padre").
            // Ahora que la unidad ha sido eliminada, podemos eliminar el spa de forma segura.
            $spa = \App\Models\Spa::find($spaIdToDelete);
            if ($spa) {
                $spa->delete();
            }
 
            // 4. Eliminar el directorio de logos del almacenamiento.
            Storage::disk('public_path')->deleteDirectory($directoryPath);
 
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Unidad eliminada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Logueamos la excepción completa para tener más detalles en los logs del sistema.
            Log::error("Error al intentar eliminar la unidad ID {$unidad->id}", ['exception' => $e]);

            // Verificamos de forma más robusta si el error es una violación de restricción de clave foránea.
            // El código de error '23000' es el estándar SQLSTATE para "integrity constraint violation".
            if ($e instanceof QueryException && $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo eliminar la unidad porque tiene datos asociados (como anfitriones, reservaciones, etc.). Por favor, elimine esos datos primero.'
                ], 500);
            }

            // Si no es un error de FK, devolvemos el mensaje específico de la excepción.
            // Esto ayudará a identificar el problema real (ej. permisos de archivo, etc.).
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado: ' . $e->getMessage()], 500);
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
            // 1. Crear un array con todos los colores del tema desde el modelo de la unidad
            $themeColors = [
                'sidebar_bg' => $unidad->color_sidebar_bg,
                'sidebar_hover_bg' => $unidad->color_sidebar_hover_bg,
                'icon_color' => $unidad->color_icon,
                'text_color' => $unidad->color_text,
                'submenu_bg' => $unidad->color_submenu_bg,
                'submenu_link_bg' => $unidad->color_submenu_link_bg,
                'submenu_link_hover_bg' => $unidad->color_submenu_link_hover_bg,
                // Añadimos los nuevos colores al tema que se guarda en sesión
                'logout_text_color' => $unidad->color_logout_text_color,
                'logout_icon_color' => $unidad->color_logout_icon_color,
            ];

            // 2. Guardar el array completo del tema en la sesión
            session([
                'current_spa' => strtolower($spaModel->nombre),
                'current_spa_id' => $spaModel->id,
                'current_unidad_theme' => $themeColors, // Guardamos el array del tema
            ]);

            session()->forget('current_unidad_color'); // Eliminamos la variable antigua
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

                $fileName = match ($dataKey) {
                    'logo_superior' => 'logo.png',
                    'logo_inferior' => 'decorativo.png',
                    'logo_unidad'   => 'logounidad.png',
                    default         => 'default.png',
                };

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

    /**
     * Ajusta el brillo de un color hexadecimal.
     *
     * @param string $hex El color en formato hexadecimal (ej. #RRGGBB).
     * @param float $percent El porcentaje de ajuste (-1.0 a 1.0).
     *                        Valores negativos oscurecen, positivos aclaran.
     * @return string El nuevo color en formato hexadecimal.
     */
    private function adjust_color_brightness(string $hex, float $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        if (strlen($hex) != 6) {
            return '#' . $hex; // Retorna original si es inválido
        }

        $rgb = array_map('hexdec', str_split($hex, 2));

        foreach ($rgb as &$color) {
            $change = $percent > 0 
                ? (255 - $color) * $percent  // Aclarar (distancia hacia el blanco)
                : $color * $percent;         // Oscurecer (distancia hacia el negro)

            $color = round($color + $change);
            $color = max(0, min(255, $color)); // Asegurar que el valor esté en el rango 0-255
        }

        return '#' . implode('', array_map(fn($c) => str_pad(dechex($c), 2, '0', STR_PAD_LEFT), $rgb));
    }
}
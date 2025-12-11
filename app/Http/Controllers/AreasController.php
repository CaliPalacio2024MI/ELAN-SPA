<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnfitrionOperativo;
use App\Models\Cabina;
use App\Models\Anfitrion;
use App\Models\Spa;
use App\Models\Departamento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AreasController extends Controller
{
    /**
     * Muestra la vista principal del gestor de áreas con una tabla
     * que lista por departamento: anfitriones, especialidades y cabinas relacionadas.
     */
    public function index(Request $request)
{
    // Resolver SPA actual
    // Priorizar el ID si está en sesión
    $spaId = $request->session()->get('current_spa_id'); // Usar get() para mayor claridad

    if (!$spaId) {
        // Si no hay ID, intentar resolver por nombre
        $spaNombre = $request->session()->get('current_spa'); // Usar get() para mayor claridad
        if ($spaNombre) {
            $spa = Spa::where('nombre', $spaNombre)->first();
            // Si no se encuentra por nombre exacto, intentar con un "LIKE" (considerar si esto es deseable)
            if (!$spa) {
                // Convertir la primera letra a mayúscula para la búsqueda LIKE,
                // asumiendo que los nombres de SPA en la DB pueden tener capitalización inicial.
                $spa = Spa::where('nombre', 'LIKE', '%' . ucfirst(strtolower($spaNombre)) . '%')->first();
            }
            $spaId = $spa->id ?? null;
        }
    }

    if (!$spaId) {
        return view('gestores.gestor_areas', [
            'departamentos' => collect(),
            'spaId' => null,
        ]);
    }

    // Anfitriones operativos del spa
    $operativos = AnfitrionOperativo::whereHas('anfitrion', function ($q) use ($spaId) {
        $q->where('spa_id', $spaId)->where('activo', true);
    })
    ->with('anfitrion')
    ->get();

    // Cabinas reales del spa (FALTABA ESTO)
    $cabinas = Cabina::where('spa_id', $spaId)->get();

    // Agrupar operativos por departamento normalizado
    $gruposOperativos = $operativos->groupBy(function ($item) {
        return \Illuminate\Support\Str::ascii(strtolower(trim($item->departamento ?? '')));
    });

    // Departamentos reales en BD - INCLUIR TODOS, incluso los sin operativos
    $departamentosBD = Departamento::where('spa_id', $spaId)->get();

    // Convertir departamentos BD al formato del index
    $departamentos = $departamentosBD->map(function ($d) use ($cabinas, $operativos) {

        // Operativos de ese departamento
        $items = $operativos->filter(function ($op) use ($d) {
            // Normalizar ambos strings: a minúsculas, sin espacios extra y sin acentos.
            $nombreDepartamentoAnfitrion = \Illuminate\Support\Str::ascii(strtolower(trim($op->departamento)));
            $nombreDepartamentoOficial = \Illuminate\Support\Str::ascii(strtolower(trim($d->nombre)));

            return $nombreDepartamentoAnfitrion === $nombreDepartamentoOficial;
        });

        // Lista de anfitriones
        $anfitriones = $items->map(function ($it) {
            return [
                'id' => $it->anfitrion->id ?? null,
                'nombre' => trim(($it->anfitrion->nombre_usuario ?? '') . ' ' . ($it->anfitrion->apellido_paterno ?? '')),
            ];
        })->values();

        // Especialidades
        $especialidades = $items->pluck('clases_actividad')
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        // Cabinas relacionadas según especialidades
        $cabinasRelacionadas = $cabinas->filter(function ($cab) use ($especialidades) {
            // Gracias al cast 'array' en el modelo Cabina (ya presente en tu modelo),
            // $cab->clases_actividad ya será un array o null.
            $cabClases = $cab->clases_actividad;

            if (!is_array($cabClases)) return false;

            return count(array_intersect($cabClases, $especialidades->toArray())) > 0;
        })->map(fn($c) => [
            'id' => $c->id,
            'nombre' => $c->nombre
        ])->values();

            return [
                'id'            => $d->id,
                'departamento'  => $d->nombre,
                'anfitriones'   => $anfitriones,
                'especialidades'=> $especialidades,
                'cabinas'       => $cabinasRelacionadas,
                'activo'        => $d->activo,
            ];
    });

    // NO añadir departamentos infer­idos que NO estén en BD
    // Ahora todos los departamentos deben estar en la BD gracias al seeder BaseDepartamentosSeeder

    return view('gestores.gestor_areas', [
        'departamentos' => $departamentos,
        'spaId' => $spaId,
        'spasDisponibles' => Spa::all(),
    ]);
}


    /**
     * Guarda un nuevo departamento en las spas seleccionadas
     * (método simplificado para demostración)
     */
    public function store(Request $request)
{
    // 1. Validar la entrada
    $validated = $request->validate([
        'nombre_departamento' => ['required', 'string', 'max:255'],
        'spas' => ['required', 'array', 'min:1'],
        'spas.*' => ['string'], // Validamos que los elementos del array sean strings
    ]);

    // 2. Normalizar los nombres de los SPAs recibidos a minúsculas
    $spaNombres = array_map('strtolower', $validated['spas']);

    // 3. Obtener los IDs de los SPAs seleccionados de forma segura
    // Buscamos en la base de datos los spas cuyos nombres coincidan (ignorando mayúsculas/minúsculas)
    $spaIds = Spa::whereIn(DB::raw('LOWER(nombre)'), $spaNombres)->pluck('id');

    // 4. Crear los nuevos departamentos
    // Iteramos sobre los IDs encontrados para crear un registro por cada uno
    foreach ($spaIds as $spaId) {
        // Usamos updateOrCreate para evitar duplicados si ya existe un departamento con el mismo nombre en ese SPA
        Departamento::updateOrCreate(
            ['spa_id' => $spaId, 'nombre' => $validated['nombre_departamento']],
            ['activo' => true]
        );
    }
    
    // 5. Redireccionar con mensaje de éxito
    return redirect()
        ->route('areas.index')
        ->with('success', 'Departamento creado/actualizado en los SPAs seleccionados.');
}

    /**
     * Actualiza el nombre de un departamento.
     */
    public function update(Request $request, $departamento)
    {
        $request->validate([
            'nombre_departamento' => 'required|string|max:255',
        ]);
        
        $spaId = session('current_spa_id');
        if (!$spaId) {
            return back()->withErrors(['error' => 'No se pudo determinar el spa actual.']);
        }
        // Si se recibe un id numérico, buscar por id; si no, buscar por nombre
        if (is_numeric($departamento)) {
            $depto = Departamento::where('id', intval($departamento))->where('spa_id', $spaId)->first();
        } else {
            $depto = Departamento::where('nombre', $departamento)->where('spa_id', $spaId)->first();
        }

        if ($depto) {
            $depto->nombre = $request->nombre_departamento;
            $depto->save();
            return redirect()->route('areas.index')->with('success', 'Departamento actualizado con éxito.');
        }

        // Si no existía como registro persistente, crearlo para el spa actual
        Departamento::create([
            'nombre' => $request->nombre_departamento,
            'spa_id' => $spaId,
            'activo' => true,
        ]);

        return redirect()->route('areas.index')->with('success', 'Departamento creado y actualizado con éxito.');
    }

    /**
     * Elimina un departamento.
     */
    public function destroy($departamento)
    {
        $spaId = session('current_spa_id');
        if (!$spaId) {
            return back()->withErrors(['error' => 'No se pudo determinar el spa actual.']);
        }
        if (is_numeric($departamento)) {
            $depto = Departamento::where('id', intval($departamento))->where('spa_id', $spaId)->firstOrFail();
            $depto->delete();
            return redirect()->route('areas.index')->with('success', 'Departamento eliminado con éxito.');
        }

        // Si se pasa un nombre, intentar eliminar el registro persistente que coincida
        $depto = Departamento::where('nombre', $departamento)->where('spa_id', $spaId)->first();
        if ($depto) {
            $depto->delete();
            return redirect()->route('areas.index')->with('success', 'Departamento eliminado con éxito.');
        }

        return back()->withErrors(['error' => 'No se encontró el departamento para eliminar.']);
    }

    /**
     * Alterna el estado activo/inactivo de un departamento (ya funciona).
     */
    public function toggle($departamento)
    {
        $spaId = session('current_spa_id');
        if (is_numeric($departamento)) {
            $depto = Departamento::where('id', intval($departamento))->where('spa_id', $spaId)->first();
        } else {
            $depto = Departamento::where('nombre', $departamento)->where('spa_id', $spaId)->first();
        }

        if ($depto) {
            $depto->activo = !$depto->activo;
            $depto->save();
            return redirect()->route('areas.index')->with('success', 'Estado del departamento actualizado.');
        }

        return back()->withErrors(['error' => 'No se encontró el departamento para cambiar su estado.']);
    }
}
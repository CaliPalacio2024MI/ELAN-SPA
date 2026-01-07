<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Models\Spa;
use App\Models\Cabina;
use App\Models\Experience;
use App\Models\Anfitrion;
use App\Models\AnfitrionOperativo;
use App\Models\HorarioAnfitrion;

class NewUnidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Identificar el Spa origen (Palacio Mundo Imperial)
        // Buscamos por nombre aproximado
        $origen = Spa::where('nombre', 'LIKE', '%Palacio%')->first();

        if (!$origen) {
            $this->command->error('No se encontró el spa origen "Palacio Mundo Imperial".');
            return;
        }

        $this->command->info("Clonando configuración desde: {$origen->nombre}");

        // 2. Crear el nuevo Spa "NewUnid"
        $destino = Spa::firstOrCreate(
            ['nombre' => 'NewUnid'],
            ['direccion' => $origen->direccion ?? '']
        );

        $this->command->info("Spa destino creado: {$destino->nombre} (ID: {$destino->id})");

        // --- LIMPIEZA DE DATOS PREVIOS ---
        $this->command->warn("Limpiando datos existentes en {$destino->nombre}...");
        
        // Limpieza de Anfitriones y sus datos relacionados
        $anfitrionesDestinoIds = Anfitrion::where('spa_id', $destino->id)->pluck('id');
        if ($anfitrionesDestinoIds->isNotEmpty()) {
            AnfitrionOperativo::whereIn('anfitrion_id', $anfitrionesDestinoIds)->delete();
            HorarioAnfitrion::whereIn('anfitrion_id', $anfitrionesDestinoIds)->delete();
            Anfitrion::whereIn('id', $anfitrionesDestinoIds)->delete();
        }

        // Limpieza de Reservaciones
        Cabina::where('spa_id', $destino->id)->delete();
        Experience::where('spa_id', $destino->id)->delete();
        DB::table('boutique_articulos')->where('fk_id_hotel', $destino->id)->delete();
        DB::table('boutique_ventas')->where('fk_id_hotel', $destino->id)->delete();
        DB::table('boutique_ventas_detalles')->where('fk_id_folio', function($q) use ($destino) { $q->select('id')->from('boutique_ventas')->where('fk_id_hotel', $destino->id); })->delete();

        // Limpieza ordenada de Boutique para evitar errores de FK
        $articulosDestinoIds = DB::table('boutique_articulos')->where('fk_id_hotel', $destino->id)->pluck('id');
        if ($articulosDestinoIds->isNotEmpty()) {
            $comprasDestinoIds = DB::table('boutique_compras')->whereIn('fk_id_articulo', $articulosDestinoIds)->pluck('id');
            if ($comprasDestinoIds->isNotEmpty()) {
                DB::table('boutique_inventario')->whereIn('fk_id_compra', $comprasDestinoIds)->delete();
                DB::table('boutique_compras')->whereIn('id', $comprasDestinoIds)->delete();
            }
            DB::table('boutique_articulos')->where('fk_id_hotel', $destino->id)->delete();
        }
        DB::table('boutique_articulos_familias')->where('fk_id_hotel', $destino->id)->delete();
        
        if (Schema::hasTable('boutique_config_ventas_clasificacion')) {
            DB::table('boutique_config_ventas_clasificacion')->where('fk_id_hotel', $destino->id)->delete();
        }
        if (Schema::hasTable('gimnasio_config_qr_code')) {
            DB::table('gimnasio_config_qr_code')->where('fk_id_hotel', $destino->id)->delete();
        }

        // 3. Clonar Cabinas (para Reservaciones)
        $cabinas = Cabina::where('spa_id', $origen->id)->get();
        foreach ($cabinas as $cabina) {
            $nuevaCabina = $cabina->replicate();
            $nuevaCabina->spa_id = $destino->id;
            $nuevaCabina->save();
        }
        $this->command->info("Se clonaron {$cabinas->count()} cabinas.");

        // 4. Clonar Experiencias (para Reservaciones y Salón de Belleza)
        $experiencias = Experience::where('spa_id', $origen->id)->get();
        foreach ($experiencias as $exp) {
            $nuevaExp = $exp->replicate();
            $nuevaExp->spa_id = $destino->id;
            $nuevaExp->save();
        }
        $this->command->info("Se clonaron {$experiencias->count()} experiencias.");

        // 5. Clonar Anfitriones y sus configuraciones (para Administración y Reservaciones)
        $this->command->info("Clonando Anfitriones y sus configuraciones...");
        $anfitrionesOrigen = Anfitrion::with(['operativo', 'horario'])->where('spa_id', $origen->id)->get();

        foreach ($anfitrionesOrigen as $anfitrion) {
            $nuevoAnfitrion = $anfitrion->replicate(['accesos']); // Replicar sin 'accesos' para manejarlos
            $nuevoAnfitrion->spa_id = $destino->id;

            // Clonar accesos, reemplazando el ID de origen por el de destino si existe
            $accesosOriginales = is_array($anfitrion->accesos) ? $anfitrion->accesos : json_decode($anfitrion->accesos, true) ?? [];
            $nuevosAccesos = [];
            foreach ($accesosOriginales as $accesoId) {
                $nuevosAccesos[] = ($accesoId == $origen->id) ? $destino->id : $accesoId;
            }
            $nuevoAnfitrion->accesos = array_unique($nuevosAccesos);
            
            $nuevoAnfitrion->save();

            // Clonar AnfitrionOperativo
            if ($anfitrion->operativo) {
                $nuevoOperativo = $anfitrion->operativo->replicate();
                $nuevoOperativo->anfitrion_id = $nuevoAnfitrion->id;
                $nuevoOperativo->save();
            }

            // Clonar HorarioAnfitrion
            if ($anfitrion->horario) {
                $nuevoHorario = $anfitrion->horario->replicate();
                $nuevoHorario->anfitrion_id = $nuevoAnfitrion->id;
                $nuevoHorario->save();
            }
        }
        $this->command->info("Se clonaron {$anfitrionesOrigen->count()} anfitriones.");

        // 6. Clonar Configuración de Boutique (Familias, Artículos, Compras e Inventario)
        $this->command->info("Clonando datos de Boutique (incluyendo inventario)...");

        // --- CLONAR FAMILIAS ---
        $familiasOrigen = DB::table('boutique_articulos_familias')->where('fk_id_hotel', $origen->id)->get();
        $mapaFamilias = []; // [old_id => new_id]
        foreach ($familiasOrigen as $familia) {
            $nuevaFamiliaId = DB::table('boutique_articulos_familias')->insertGetId([
                'nombre' => $familia->nombre,
                'fk_id_hotel' => $destino->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mapaFamilias[$familia->id] = $nuevaFamiliaId;
        }

        // --- CLONAR ARTÍCULOS ---
        $articulosOrigen = DB::table('boutique_articulos')->where('fk_id_hotel', $origen->id)->get();
        $mapaArticulos = []; // [old_id => new_id]
        foreach ($articulosOrigen as $articulo) {
            $articuloData = (array)$articulo;
            $oldId = $articuloData['id'];
            unset($articuloData['id']);
            $articuloData['fk_id_familia'] = $mapaFamilias[$articulo->fk_id_familia] ?? null;
            $articuloData['fk_id_hotel'] = $destino->id;
            $articuloData['created_at'] = $articulo->created_at ?? now();
            $articuloData['updated_at'] = $articulo->updated_at ?? now();
            $nuevoArticuloId = DB::table('boutique_articulos')->insertGetId($articuloData);
            $mapaArticulos[$oldId] = $nuevoArticuloId;
        }

        // --- CLONAR COMPRAS E INVENTARIO ---
        $comprasOrigen = DB::table('boutique_compras')->whereIn('fk_id_articulo', array_keys($mapaArticulos))->get();
        foreach($comprasOrigen as $compra) {
            $compraData = (array)$compra;
            $oldId = $compraData['id'];
            unset($compraData['id']);
            $compraData['fk_id_articulo'] = $mapaArticulos[$compra->fk_id_articulo] ?? null;
            if ($compraData['fk_id_articulo']) {
                $nuevaCompraId = DB::table('boutique_compras')->insertGetId($compraData);
                // Clonar el inventario asociado a esta compra
                $inventario = DB::table('boutique_inventario')->where('fk_id_compra', $oldId)->first();
                if ($inventario) {
                    DB::table('boutique_inventario')->insert([
                        'fk_id_compra' => $nuevaCompraId,
                        'cantidad_actual' => $inventario->cantidad_actual
                    ]);
                }
            }
        }
        $this->command->info("Se clonó el inventario de boutique.");

        // 7. Clonar Configuraciones Adicionales (Gimnasio, etc.)
        if (Schema::hasTable('boutique_config_ventas_clasificacion')) {
            $configs = DB::table('boutique_config_ventas_clasificacion')->where('fk_id_hotel', $origen->id)->get();
            foreach ($configs as $conf) {
                $data = (array)$conf;
                unset($data['id']);
                $data['fk_id_hotel'] = $destino->id;
                DB::table('boutique_config_ventas_clasificacion')->insert($data);
            }
            $this->command->info("Se clonó configuración de clasificación de ventas.");
        }

        if (Schema::hasTable('gimnasio_config_qr_code')) {
            $gymConfig = DB::table('gimnasio_config_qr_code')->where('fk_id_hotel', $origen->id)->first();
            if ($gymConfig) {
                $data = (array)$gymConfig;
                unset($data['id']);
                $data['fk_id_hotel'] = $destino->id;
                DB::table('gimnasio_config_qr_code')->insert($data);
                $this->command->info("Se clonó configuración de gimnasio.");
            }
        }

        // 8. Copiar carpeta de imágenes (Logos)
        $origenFolder = 'palacio'; // Asumimos 'palacio' como carpeta origen estándar
        $destinoFolder = 'newunid';

        $sourceDir = public_path("images/{$origenFolder}");
        $targetDir = public_path("images/{$destinoFolder}");

        if (!File::exists($sourceDir)) {
             // Intento alternativo con nombre completo
             $sourceDir = public_path("images/" . strtolower($origen->nombre));
        }

        if (File::exists($sourceDir)) {
                if (!File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
            File::copyDirectory($sourceDir, $targetDir);
            $this->command->info("Imágenes copiadas de '{$sourceDir}' a '{$targetDir}'.");
        } else {
            $this->command->warn("No se encontró la carpeta de imágenes origen.");
        }
    }
}
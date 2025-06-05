<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BoutiqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('boutique_config_ventas_clasificacion')->truncate();
        DB::table('boutique_articulos')->truncate();
        DB::table('boutique_compras')->truncate();
        DB::table('boutique_inventario')->truncate();
        DB::table('boutique_ventas')->truncate();
        DB::table('boutique_ventas_detalles')->truncate();
        DB::table('boutique_articulos_familias')->truncate();
        DB::table('boutique_formas_pago')->truncate();

        $hoteles = DB::table('spas')->pluck('id')->toArray();

        $clasificaciones = [
            ['nombre' => 'Rápido', 'minimo_ventas' => 30],
            ['nombre' => 'Lento', 'minimo_ventas' => 10],
            ['nombre' => 'Obsoleto', 'minimo_ventas' => 0],
        ];

        $familias = ['Corporal', 'Facial', 'Cabello', 'Amenidad'];

        $clasificacionesInsert = [];
        $familiasInsert = [];

        foreach ($hoteles as $hotelId) {
            foreach ($clasificaciones as $c) {
                $clasificacionesInsert[] = [
                    'nombre' => $c['nombre'],
                    'minimo_ventas' => $c['minimo_ventas'],
                    'fk_id_hotel' => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach ($familias as $nombre) {
                $familiasInsert[] = [
                    'nombre' => $nombre,
                    'fk_id_hotel' => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('boutique_config_ventas_clasificacion')->insert($clasificacionesInsert);
        DB::table('boutique_articulos_familias')->insert($familiasInsert);

        DB::table('boutique_formas_pago')->insert([
            ['nombre' => 'Cargo a Habitación'],
            ['nombre' => 'Tarjeta'],
            ['nombre' => 'Misceláneo'],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

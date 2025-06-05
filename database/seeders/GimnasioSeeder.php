<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GimnasioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tablas relacionadas
        DB::table('gimnasio_config_qr_code')->truncate();
        DB::table('gimnasio_qrcodes')->truncate();
        DB::table('gimnasio_registros_adultos')->truncate();
        DB::table('gimnasio_registros_menores')->truncate();

        // Obtener todos los hoteles (spas)
        $hoteles = DB::table('spas')->pluck('id')->toArray();

        // Crear configuraciones QR para cada hotel
        foreach ($hoteles as $hotelId) {
            DB::table('gimnasio_config_qr_code')->insert([
                'fk_id_hotel' => $hotelId,
                'tiempo_renovacion_qr' => 60,
                'tiempo_validez_qr' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

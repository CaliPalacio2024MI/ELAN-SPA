<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Experience;

class ExperienceSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('experiences')->delete();

        Experience::insert([
            [
                'spa_id' => 1,
                'nombre' => 'Masaje Relajante',
                'descripcion' => 'Un masaje de cuerpo completo con aceites esenciales para aliviar el estrés.',
                'clase' => 'Masajes',
                'duracion' => 60,
                'precio' => 1200.00,
                'color' => '#FFC0CB',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 1,
                'nombre' => 'Facial Hidratante',
                'descripcion' => 'Tratamiento facial profundo para rejuvenecer la piel.',
                'clase' => 'Faciales',
                'duracion' => 45,
                'precio' => 900.00,
                'color' => '#ADD8E6',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 2,
                'nombre' => 'Terapia de Piedras Calientes',
                'descripcion' => 'Masaje con piedras volcánicas para liberar la tensión muscular.',
                'clase' => 'Masajes',
                'duracion' => 75,
                'precio' => 1500.00,
                'color' => '#FFA07A',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 2,
                'nombre' => 'Exfoliación Corporal',
                'descripcion' => 'Tratamiento exfoliante para eliminar células muertas de la piel.',
                'clase' => 'Corporales',
                'duracion' => 50,
                'precio' => 1100.00,
                'color' => '#FFE4B5',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 3,
                'nombre' => 'Envoltura de Chocolate',
                'descripcion' => 'Terapia relajante con envoltura de cacao para nutrir la piel.',
                'clase' => 'Corporales',
                'duracion' => 60,
                'precio' => 1300.00,
                'color' => '#D2691E',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 3,
                'nombre' => 'Reflexología Podal',
                'descripcion' => 'Masaje terapéutico en los pies para mejorar la circulación.',
                'clase' => 'Masajes',
                'duracion' => 40,
                'precio' => 800.00,
                'color' => '#90EE90',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 1,
                'nombre' => 'Masaje calmante',
                'descripcion' => 'ni idea',
                'clase' => 'Corporales',
                'duracion' => 120,
                'precio' => 120.00,
                'color' => '#FFB6C1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 1,
                'nombre' => 'Masaje de espalda',
                'descripcion' => 'es un masaje en la espalda',
                'clase' => 'Masajes',
                'duracion' => 83,
                'precio' => 2000.00,
                'color' => '#FA8072',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 1,
                'nombre' => 'Masaje de codos',
                'descripcion' => 'masaje en codos',
                'clase' => 'Masajes',
                'duracion' => 30,
                'precio' => 3000.00,
                'color' => '#F0E68C',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'spa_id' => 1,
                'nombre' => 'Masaje reparador',
                'descripcion' => 'es un masaje',
                'clase' => 'Masajes',
                'duracion' => 90,
                'precio' => 3000.00,
                'color' => '#E6E6FA',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

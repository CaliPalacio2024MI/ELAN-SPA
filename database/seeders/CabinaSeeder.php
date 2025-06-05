<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Cabina;

class CabinaSeeder extends Seeder
{
    public function run()
    {
         
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        
        DB::table('cabinas')->delete();

         
        Cabina::insert([
            ['spa_id' => 1, 'nombre' => 'Cabina Relax',        'clase' => 'individual', 'clases_actividad' => json_encode(['Masajes']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 1, 'nombre' => 'Cabina Deluxe',       'clase' => 'doble',      'clases_actividad' => json_encode(['Masajes', 'Corporales']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 1, 'nombre' => 'Cabina VIP',          'clase' => 'vip',        'clases_actividad' => json_encode(['Masajes', 'Corporales']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 1, 'nombre' => 'Cabina gris',         'clase' => 'Gris',       'clases_actividad' => json_encode(['Masajes']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            ['spa_id' => 2, 'nombre' => 'Cabina Zen',          'clase' => 'individual', 'clases_actividad' => json_encode(['Masajes', 'Corporales']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 2, 'nombre' => 'Cabina Serenity',     'clase' => 'doble',      'clases_actividad' => json_encode(['Masajes']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 2, 'nombre' => 'Cabina VIP Platinum', 'clase' => 'vip',        'clases_actividad' => json_encode(['Masajes', 'Corporales']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            ['spa_id' => 3, 'nombre' => 'Cabina Harmony',      'clase' => 'individual', 'clases_actividad' => json_encode(['Masajes']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 3, 'nombre' => 'Cabina Elegance',     'clase' => 'doble',      'clases_actividad' => json_encode(['Masajes']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['spa_id' => 3, 'nombre' => 'Cabina VIP Diamond',  'clase' => 'vip',        'clases_actividad' => json_encode(['Masajes', 'Corporales']), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

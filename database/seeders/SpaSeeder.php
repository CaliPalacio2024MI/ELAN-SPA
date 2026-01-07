<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Spa;
use Illuminate\Support\Facades\DB;

class SpaSeeder extends Seeder
{
    public function run()
    {
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        
        DB::table('spas')->delete();

         
        Spa::insert([
            [
                'id' => 1,
                'nombre' => 'Palacio',
                'direccion' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Pierre',
                'direccion' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nombre' => 'Princess',
                'direccion' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

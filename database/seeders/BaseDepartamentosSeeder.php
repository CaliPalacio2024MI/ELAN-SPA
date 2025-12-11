<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Spa;
use App\Models\Departamento;

class BaseDepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $spas = Spa::all();
        $departamentosBase = ['Spa', 'Gimnasio', 'Valet', 'Salón de Belleza'];

        foreach ($spas as $spa) {
            foreach ($departamentosBase as $nombreDepartamento) {
                Departamento::updateOrCreate(
                    [
                        'spa_id' => $spa->id,
                        'nombre' => $nombreDepartamento,
                    ],
                    [
                        'activo' => true, // Asegurarse de que estén activos por defecto
                    ]
                );
            }
        }
    }
}

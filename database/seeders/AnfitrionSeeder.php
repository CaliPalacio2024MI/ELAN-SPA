<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Anfitrion;
use App\Models\AnfitrionOperativo;

class AnfitrionSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('anfitriones')->delete();
        DB::table('anfitrion_operativo')->delete();

        $password = Hash::make('Aa12345!');

        $anfitriones = [
            [
                'spa_id' => 1, 'RFC' => 'MASTER001ABC', 'apellido_paterno' => 'Control', 'apellido_materno' => 'Central',
                'nombre_usuario' => 'Master', 'rol' => 'master', 'accesos' => [1, 2, 3]
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MOO', 'apellido_paterno' => 'Gómez', 'apellido_materno' => 'Perez',
                'nombre_usuario' => 'Carlos', 'rol' => 'anfitrion', 'accesos' => [2, 3], 'departamento' => 'spa', 'clases' => ['Masajes']
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827KKP', 'apellido_paterno' => 'Martínez', 'apellido_materno' => 'Perez',
                'nombre_usuario' => 'Ana', 'rol' => 'administrador', 'accesos' => [2]
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MND', 'apellido_paterno' => 'López', 'apellido_materno' => 'Marin',
                'nombre_usuario' => 'Raul', 'rol' => 'anfitrion', 'departamento' => 'gym', 'clases' => ['Entrenamiento'], 'accesos' => null
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MNF', 'apellido_paterno' => 'Ramírez', 'apellido_materno' => 'Medina',
                'nombre_usuario' => 'Maria', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ],
            [
                'spa_id' => 3, 'RFC' => 'SANC950827MNG', 'apellido_paterno' => 'Fernández', 'apellido_materno' => 'Flores',
                'nombre_usuario' => 'Pedro', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Corporales'], 'accesos' => [1]
            ],
            [
                'spa_id' => 3, 'RFC' => 'SANC950827MNH', 'apellido_paterno' => 'Sánchez', 'apellido_materno' => 'Fe',
                'nombre_usuario' => 'Lucio', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Masajes'], 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNI', 'apellido_paterno' => 'Chinchulin', 'apellido_materno' => 'Fernandez',
                'nombre_usuario' => 'Mai', 'rol' => 'administrador', 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNJ', 'apellido_paterno' => 'Morales', 'apellido_materno' => 'Díaz',
                'nombre_usuario' => 'Sandra', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNK', 'apellido_paterno' => 'Torres', 'apellido_materno' => 'Nava',
                'nombre_usuario' => 'Hugo', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Masajes'], 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNZ', 'apellido_paterno' => 'Paredes', 'apellido_materno' => 'García',
                'nombre_usuario' => 'Itzel', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNN', 'apellido_paterno' => 'Vega', 'apellido_materno' => 'Ortega',
                'nombre_usuario' => 'Luis', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Corporales'], 'accesos' => null
            ],
            [
                'spa_id' => 1, 'RFC' => 'SANC950827MNO', 'apellido_paterno' => 'Zamora', 'apellido_materno' => 'Ruiz',
                'nombre_usuario' => 'Brenda', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Masajes'], 'accesos' => null
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MNP', 'apellido_paterno' => 'Ríos', 'apellido_materno' => 'Vargas',
                'nombre_usuario' => 'Diana', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MNQ', 'apellido_paterno' => 'Salas', 'apellido_materno' => 'Montes',
                'nombre_usuario' => 'Oscar', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Corporales'], 'accesos' => null
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MNR', 'apellido_paterno' => 'Mejía', 'apellido_materno' => 'Guzmán',
                'nombre_usuario' => 'Andrea', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Masajes'], 'accesos' => null
            ],
            [
                'spa_id' => 2, 'RFC' => 'SANC950827MNS', 'apellido_paterno' => 'Carrillo', 'apellido_materno' => 'Suárez',
                'nombre_usuario' => 'Fernando', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ],
            [
                'spa_id' => 3, 'RFC' => 'SANC950827MNT', 'apellido_paterno' => 'Navarro', 'apellido_materno' => 'Salinas',
                'nombre_usuario' => 'Claudia', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Masajes'], 'accesos' => null
            ],
            [
                'spa_id' => 3, 'RFC' => 'SANC950827MNU', 'apellido_paterno' => 'Silva', 'apellido_materno' => 'Luna',
                'nombre_usuario' => 'Jonathan', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Corporales'], 'accesos' => null
            ],
            [
                'spa_id' => 3, 'RFC' => 'SANC950827MNLV', 'apellido_paterno' => 'Aguilar', 'apellido_materno' => 'Castro',
                'nombre_usuario' => 'Elena', 'rol' => 'anfitrion', 'departamento' => 'spa', 'clases' => ['Faciales'], 'accesos' => null
            ]

        ];

        foreach ($anfitriones as $datos) {
            $anfitrion = Anfitrion::create([
                'spa_id' => $datos['spa_id'],
                'RFC' => $datos['RFC'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'],
                'nombre_usuario' => $datos['nombre_usuario'],
                'password' => $password,
                'rol' => $datos['rol'],
                'accesos' => $datos['accesos'] ?? null,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if ($datos['rol'] === 'anfitrion') {
                AnfitrionOperativo::create([
                    'anfitrion_id' => $anfitrion->id,
                    'departamento' => $datos['departamento'] ?? 'spa',
                    'clases_actividad' => $datos['clases'] ?? [],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

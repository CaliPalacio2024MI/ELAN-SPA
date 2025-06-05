<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('clients')->truncate(); 

        Client::insert([
            ['nombre' => 'Luis',     'apellido_paterno' => 'Hernández', 'apellido_materno' => 'Abarca',        'telefono' => '5551234567', 'correo' => 'luis@example.com',     'tipo_visita' => 'palacio mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Elena',    'apellido_paterno' => 'Ramírez',   'apellido_materno' => 'Filemon',        'telefono' => '5552345678', 'correo' => 'elena@example.com',    'tipo_visita' => 'palacio mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Carlos',   'apellido_paterno' => 'Gómez',     'apellido_materno' => 'Smith',        'telefono' => '5553456789', 'correo' => 'carlos@example.com',   'tipo_visita' => 'princess mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Sofía',    'apellido_paterno' => 'Díaz',      'apellido_materno' => 'Perez',        'telefono' => '5554567890', 'correo' => 'sofia@example.com',    'tipo_visita' => 'princess mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Javier',   'apellido_paterno' => 'Pérez',     'apellido_materno' => 'Cruz',        'telefono' => '5555678901', 'correo' => 'javier@example.com',   'tipo_visita' => 'pierre mundo imperial',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Marta',    'apellido_paterno' => 'Lopez',     'apellido_materno' => 'Temertizo',        'telefono' => '5556789012', 'correo' => 'marta@example.com',    'tipo_visita' => 'pierre mundo imperial',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Juan',     'apellido_paterno' => 'Santana',   'apellido_materno' => 'Red',        'telefono' => '7444444444', 'correo' => 'juan@example.com',     'tipo_visita' => 'condominio',              'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'José',     'apellido_paterno' => 'Torre',     'apellido_materno' => 'Bello',        'telefono' => '7444444455', 'correo' => 'jose@example.com',     'tipo_visita' => 'locales',                 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Daniela',  'apellido_paterno' => 'Morán',     'apellido_materno' => 'Salmeron',        'telefono' => '9330000000', 'correo' => 'daniela@example.com',  'tipo_visita' => 'palacio mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Daniela',  'apellido_paterno' => 'Luz',       'apellido_materno' => 'mg',        'telefono' => '7443434343', 'correo' => 'daniela2@example.com', 'tipo_visita' => 'palacio mundo imperial', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ramón',    'apellido_paterno' => 'Valdez',    'apellido_materno' => 'Monte',        'telefono' => '5550000000', 'correo' => 'ramon@example.com',    'tipo_visita' => 'locales',                 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

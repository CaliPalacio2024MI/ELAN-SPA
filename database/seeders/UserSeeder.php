<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        
        DB::table('users')->delete();

        
        User::insert([
            [
                'name' => 'Admin Pierre',
                'rfc' => 'PERJ850412ABC',
                'rol' => 'administrador',
                'area' => 'pierre',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin Princess',
                'rfc' => 'GARC891015XYZ',
                'rol' => 'administrador',
                'area' => 'princess',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin Palacio',
                'rfc' => 'LOPM810726DEF',
                'rol' => 'administrador',
                'area' => 'palacio',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Recepcionista Palacio',
                'rfc' => 'HERM920312JKL',
                'rol' => 'recepcionista',
                'area' => 'palacio',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Recepcionista Pierre',
                'rfc' => 'SANC950827MNO',
                'rol' => 'recepcionista',
                'area' => 'pierre',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Recepcionista Princess',
                'rfc' => 'TORR870204PQR',
                'rol' => 'recepcionista',
                'area' => 'princess',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Master',
                'rfc' => 'CAST800610STU',
                'rol' => 'master',
                'area' => 'todas',
                'password' => Hash::make('Aa12345!'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

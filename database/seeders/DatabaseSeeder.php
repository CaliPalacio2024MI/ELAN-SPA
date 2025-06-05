<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Anfitrion;

class DatabaseSeeder extends Seeder
{

    public function run()
{
    $this->call([
        SpaSeeder::class,
        UserSeeder::class,
        CabinaSeeder::class,
        AnfitrionSeeder::class,
        ExperienceSeeder::class,
        ClientSeeder::class,
        BoutiqueSeeder::class,
        GimnasioSeeder::class,
    ]);
}

}

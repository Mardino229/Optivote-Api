<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PersonSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 10; $i++) {
            DB::table('persons')->insert([
                'name' => $faker->name,
                'npi' => $faker->numberBetween(10000000000000, 99999999999999), // Génère une chaîne unique de 10 caractères
                'birthday' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'number' => $faker->numberBetween(40000000, 99999999), // Génère un numéro à 10 chiffres
            ]);
        }
    }
}

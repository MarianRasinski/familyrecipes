<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class FoodSeeder extends Seeder
{
    /**
     * Lebensmittel aus dem Bundeslebensmittelschlüssel (database/data/foods/bls_4_0.csv).
     */
    public function run(): void
    {
        Artisan::call('foods:import');
    }
}

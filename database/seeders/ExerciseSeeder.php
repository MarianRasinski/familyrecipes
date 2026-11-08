<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ExerciseSeeder extends Seeder
{
    /**
     * Übungskatalog aus database/data/exercises/*.json (kann beliebig oft laufen).
     */
    public function run(): void
    {
        Artisan::call('exercises:import');
    }
}

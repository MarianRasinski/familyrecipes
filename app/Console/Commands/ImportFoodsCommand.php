<?php

namespace App\Console\Commands;

use App\Actions\ImportFoods;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportFoodsCommand extends Command
{
    protected $signature = 'foods:import
        {path=database/data/foods/bls_4_0.csv : CSV-Datei (aus scripts/bls/convert.py)}
        {--source=BLS 4.0 : Quellenangabe für die Einträge}';

    protected $description = 'Importiert Lebensmittel mit Nährwerten (Bundeslebensmittelschlüssel); vorhandene werden aktualisiert';

    public function handle(ImportFoods $import): int
    {
        $path = $this->argument('path');
        $path = is_file($path) ? $path : base_path($path);

        if (! is_file($path)) {
            $this->components->error("Datei nicht gefunden: {$path}");

            return self::FAILURE;
        }

        try {
            $counts = $import->handle($path, $this->option('source'));
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$counts['created']} neu, {$counts['updated']} aktualisiert.");

        return self::SUCCESS;
    }
}

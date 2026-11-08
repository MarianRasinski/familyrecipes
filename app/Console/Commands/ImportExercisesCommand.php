<?php

namespace App\Console\Commands;

use App\Actions\ImportExercises;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ImportExercisesCommand extends Command
{
    protected $signature = 'exercises:import {path=database/data/exercises : JSON-Datei oder Ordner mit JSON-Dateien}';

    protected $description = 'Importiert Übungen (TrainJ-Format); vorhandene Übungen derselben Datei werden aktualisiert';

    public function handle(ImportExercises $import): int
    {
        $path = $this->argument('path');
        $path = file_exists($path) ? $path : base_path($path);

        $files = match (true) {
            is_dir($path) => collect(File::files($path))->filter(fn ($file) => $file->getExtension() === 'json')->map->getPathname()->sort()->values(),
            is_file($path) => collect([$path]),
            default => collect(),
        };

        if ($files->isEmpty()) {
            $this->components->error("Keine JSON-Datei gefunden: {$path}");

            return self::FAILURE;
        }

        $failed = false;

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $data = json_decode(File::get($file), true);

            if (! is_array($data)) {
                $this->components->twoColumnDetail($name, '<fg=red>kein gültiges JSON</>');
                $failed = true;

                continue;
            }

            try {
                $counts = $import->handle($data, $name);
            } catch (ValidationException $e) {
                $this->components->twoColumnDetail($name, '<fg=red>Fehler</>');
                $this->components->bulletList($e->validator->errors()->all());
                $failed = true;

                continue;
            }

            $this->components->twoColumnDetail($name, "<fg=green>{$counts['created']} neu, {$counts['updated']} aktualisiert</>");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

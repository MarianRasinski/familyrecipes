<?php

namespace App\Actions\Training;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Support\Facades\DB;

/**
 * Speichert Name, Notizen und Übungen einer Einheit. Optional wird dabei eine Übung entfernt
 * oder nach oben/unten verschoben (Knöpfe im selben Formular, damit Eingaben nicht verloren gehen).
 */
class SaveWorkout
{
    /**
     * @param  array{name: string, notes?: string|null, exercises?: array<int|string, array{sets: int|string, target?: string|null, rest_seconds?: int|string|null, notes?: string|null}>}  $data
     * @param  string|null  $move  „{id}:up“ oder „{id}:down“
     */
    public function handle(Workout $workout, array $data, ?int $remove = null, ?string $move = null): void
    {
        DB::transaction(function () use ($workout, $data, $remove, $move) {
            $workout->update(['name' => $data['name'], 'notes' => $data['notes'] ?? null]);

            $items = $workout->exercises()->get();

            foreach ($items as $item) {
                $row = $data['exercises'][$item->id] ?? null;

                if ($row !== null) {
                    $item->fill([
                        'sets' => (int) $row['sets'],
                        'target' => filled($row['target'] ?? null) ? trim($row['target']) : null,
                        'rest_seconds' => filled($row['rest_seconds'] ?? null) ? (int) $row['rest_seconds'] : null,
                        'notes' => filled($row['notes'] ?? null) ? trim($row['notes']) : null,
                    ]);
                }
            }

            if ($remove !== null) {
                $items->firstWhere('id', $remove)?->delete();
                $items = $items->reject(fn (WorkoutExercise $item) => $item->id === $remove)->values();
            }

            if ($move !== null && preg_match('/^(\d+):(up|down)$/', $move, $m)) {
                $index = $items->search(fn (WorkoutExercise $item) => $item->id === (int) $m[1]);
                $target = $m[2] === 'up' ? $index - 1 : $index + 1;

                if ($index !== false && $target >= 0 && $target < $items->count()) {
                    $all = $items->all();
                    [$all[$index], $all[$target]] = [$all[$target], $all[$index]];
                    $items = collect($all);
                }
            }

            // Positionen lückenlos neu vergeben
            $items->values()->each(fn (WorkoutExercise $item, int $i) => $item->fill(['position' => $i + 1])->save());
        });
    }
}

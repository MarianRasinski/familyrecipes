<?php

namespace App\Support\Training;

use App\Models\Exercise;
use App\Models\ExerciseMuscleGroup;
use App\Models\MuscleGroup;
use Illuminate\Support\Collection;

/**
 * Welche Körperbereiche der Muskelkarte kräftig (Hauptmuskulatur) bzw. hell (Nebenmuskulatur) gefärbt werden.
 * Zuordnung Muskelgruppe → Bereiche in `config/muscle_map.php`. Ein Bereich, der irgendwo Hauptmuskulatur ist,
 * gilt als Hauptmuskulatur.
 */
final class MuscleMap
{
    /**
     * @param  list<string>  $primary
     * @param  list<string>  $secondary
     */
    private function __construct(
        public readonly array $primary,
        public readonly array $secondary,
    ) {}

    /**
     * Für eine Übung. Erwartet geladene `muscleGroups`.
     */
    public static function forExercise(Exercise $exercise): self
    {
        return self::forExercises(collect([$exercise]));
    }

    /**
     * Für mehrere Übungen zusammen (z. B. eine Trainingseinheit). Erwartet geladene `muscleGroups`.
     *
     * @param  Collection<int, Exercise>  $exercises
     */
    public static function forExercises(Collection $exercises): self
    {
        $groups = $exercises->flatMap(fn (Exercise $exercise) => $exercise->muscleGroups);

        $isPrimary = fn (MuscleGroup $group) => $group->pivot instanceof ExerciseMuscleGroup && $group->pivot->is_primary;

        $primary = self::regions($groups->filter($isPrimary)->pluck('slug'));
        $secondary = self::regions($groups->reject($isPrimary)->pluck('slug'))->diff($primary);

        return new self($primary->values()->all(), $secondary->values()->all());
    }

    /**
     * @param  Collection<int, string>  $slugs
     * @return Collection<int, string>
     */
    private static function regions(Collection $slugs): Collection
    {
        $map = config('muscle_map.groups');

        return $slugs->flatMap(fn (string $slug) => $map[$slug] ?? [])->unique()->values();
    }

    public function isEmpty(): bool
    {
        return $this->primary === [] && $this->secondary === [];
    }

    /**
     * Füllung eines Bereichs (Theme-Farben; Nebenmuskulatur als hellere Stufe derselben Farbe).
     *
     * @return array{fill: string, opacity: string}
     */
    public function style(string $region): array
    {
        return match (true) {
            in_array($region, $this->primary, true) => ['fill' => 'var(--color-herb)', 'opacity' => '1'],
            in_array($region, $this->secondary, true) => ['fill' => 'var(--color-herb)', 'opacity' => '0.4'],
            default => ['fill' => 'var(--color-line)', 'opacity' => '1'],
        };
    }
}

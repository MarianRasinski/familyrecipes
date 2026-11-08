<?php

namespace App\Actions;

use App\Enums\ExerciseLevel;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Support\Training\ExerciseTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Importiert einen Übungskatalog (Format der TrainJ-Datei, siehe database/data/exercises/).
 *
 * Ein erneuter Import derselben Quelle aktualisiert die Übungen anhand ihrer ID, statt sie doppelt
 * anzulegen. Geräte werden auf Gerätetypen zusammengefasst: Aus „Kettlebell 12 kg“ im Geräte-
 * bestand wird der Typ „Kettlebell“ mit der Ausführung „12 kg“; „Kettlebell optional“ bei einer
 * Übung bedeutet: Gerät „Kettlebell“, aber nicht zwingend nötig.
 */
class ImportExercises
{
    private const OPTIONAL_SUFFIX = '/\s+optional$/iu';

    /**
     * @param  array<string, mixed>  $data  Dekodierte JSON-Datei
     * @param  string  $source  Herkunft, z. B. der Dateiname („ganzkoerper“)
     * @return array{created: int, updated: int}
     *
     * @throws ValidationException
     */
    public function handle(array $data, string $source): array
    {
        $data = Validator::make($data, [
            'kategorie' => ['nullable', 'string', 'max:255'],
            'trainingsgeraete' => ['array'],
            'trainingsgeraete.*' => ['string', 'max:255'],
            'uebungen' => ['required', 'array', 'min:1'],
            'uebungen.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'uebungen.*.name' => ['required', 'string', 'max:255'],
            'uebungen.*.geraet' => ['array'],
            'uebungen.*.geraet.*' => ['string', 'max:255'],
            'uebungen.*.muskelgruppen_haupt' => ['array'],
            'uebungen.*.muskelgruppen_haupt.*' => ['string', 'max:255'],
            'uebungen.*.muskelgruppen_neben' => ['array'],
            'uebungen.*.muskelgruppen_neben.*' => ['string', 'max:255'],
            'uebungen.*.schwierigkeit' => ['nullable', 'string'],
            'uebungen.*.saetze' => ['nullable', 'integer', 'min:1', 'max:255'],
            'uebungen.*.wiederholungen' => ['nullable', 'string', 'max:255'],
            'uebungen.*.beschreibung' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($data, $source) {
            $equipment = $this->syncEquipment($data);
            $counts = ['created' => 0, 'updated' => 0];

            foreach ($data['uebungen'] as $raw) {
                $exercise = $this->saveExercise($raw, $source, $data['kategorie'] ?? null);
                $counts[$exercise->wasRecentlyCreated ? 'created' : 'updated']++;

                $exercise->equipment()->sync($this->equipmentPivot($raw['geraet'] ?? [], $equipment));
                $exercise->muscleGroups()->sync($this->muscleGroupPivot($raw));
            }

            return $counts;
        });
    }

    /**
     * Legt alle Gerätetypen an und markiert den Gerätebestand als vorhanden.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<string, Equipment> nach kleingeschriebenem Namen
     */
    private function syncEquipment(array $data): Collection
    {
        $types = collect($data['uebungen'])
            ->flatMap(fn (array $raw) => $raw['geraet'] ?? [])
            ->map(fn (string $name) => trim(preg_replace(self::OPTIONAL_SUFFIX, '', $name)))
            ->filter()
            ->unique(fn (string $name) => mb_strtolower($name));

        $equipment = $types->mapWithKeys(fn (string $name) => [mb_strtolower($name) => $this->findOrCreateEquipment($name)]);

        // Längste Namen zuerst, damit z. B. „Kurzhanteln“ vor „Hantel“ greift.
        $byLength = $types->sortByDesc(fn (string $name) => mb_strlen($name));

        foreach ($data['trainingsgeraete'] ?? [] as $item) {
            $item = trim($item);
            $type = $byLength->first(fn (string $name) => Str::contains($item, $name, ignoreCase: true));
            $variant = $type === null ? null : trim(Str::replace($type, '', $item, caseSensitive: false));

            $model = $type === null
                ? ($equipment[mb_strtolower($item)] ??= $this->findOrCreateEquipment($item))
                : $equipment[mb_strtolower($type)];

            $variants = collect($model->variants ?? [])->when(filled($variant), fn ($c) => $c->push($variant))->unique()->values();

            $model->fill(['is_available' => true, 'variants' => $variants->isEmpty() ? null : $variants->all()])->save();
        }

        return $equipment;
    }

    private function findOrCreateEquipment(string $name): Equipment
    {
        return Equipment::firstOrCreate(['slug' => Str::slug($name, language: 'de')], ['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function saveExercise(array $raw, string $source, ?string $category): Exercise
    {
        $target = ExerciseTarget::parse($raw['wiederholungen'] ?? null);

        $exercise = Exercise::firstOrNew(['source' => $source, 'external_id' => $raw['id']]);

        $exercise->fill([
            'name' => trim($raw['name']),
            'category' => $category,
            'level' => ExerciseLevel::fromLabel($raw['schwierigkeit'] ?? null),
            'description' => $raw['beschreibung'] ?? null,
            'default_sets' => $raw['saetze'] ?? null,
            'target' => $target->text ?: null,
            'measure' => $target->measure,
            'target_min' => $target->min,
            'target_max' => $target->max,
            'per_side' => $target->perSide,
        ])->save();

        return $exercise;
    }

    /**
     * @param  list<string>  $names
     * @param  Collection<string, Equipment>  $equipment
     * @return array<int, array{is_optional: bool}>
     */
    private function equipmentPivot(array $names, Collection $equipment): array
    {
        return collect($names)
            ->mapWithKeys(function (string $name) use ($equipment) {
                $optional = (bool) preg_match(self::OPTIONAL_SUFFIX, $name);
                $key = mb_strtolower(trim(preg_replace(self::OPTIONAL_SUFFIX, '', $name)));

                return [$equipment[$key]->id => ['is_optional' => $optional]];
            })
            ->all();
    }

    /**
     * Hauptmuskulatur gewinnt, falls eine Gruppe in beiden Listen steht.
     *
     * @param  array<string, mixed>  $raw
     * @return array<int, array{is_primary: bool}>
     */
    private function muscleGroupPivot(array $raw): array
    {
        $pivot = [];

        foreach (['muskelgruppen_neben' => false, 'muskelgruppen_haupt' => true] as $key => $primary) {
            foreach ($raw[$key] ?? [] as $name) {
                $group = MuscleGroup::firstOrCreate(['slug' => Str::slug(trim($name), language: 'de')], ['name' => trim($name)]);
                $pivot[$group->id] = ['is_primary' => $primary];
            }
        }

        return $pivot;
    }
}

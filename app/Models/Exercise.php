<?php

namespace App\Models;

use App\Enums\ExerciseLevel;
use App\Enums\ExerciseMeasure;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Eine Übung aus dem Übungskatalog mit Standardvorgabe (Sätze × Ziel).
 */
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'level',
        'description',
        'default_sets',
        'target',
        'measure',
        'target_min',
        'target_max',
        'per_side',
        'source',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => ExerciseLevel::class,
            'measure' => ExerciseMeasure::class,
            'default_sets' => 'integer',
            'target_min' => 'integer',
            'target_max' => 'integer',
            'per_side' => 'boolean',
            'external_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Exercise $exercise) {
            $exercise->slug ??= static::uniqueSlug($exercise->name);
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, language: 'de') ?: 'uebung';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class)->using(EquipmentExercise::class)->withPivot('is_optional')->orderBy('name');
    }

    public function muscleGroups(): BelongsToMany
    {
        return $this->belongsToMany(MuscleGroup::class)
            ->using(ExerciseMuscleGroup::class)
            ->withPivot('is_primary')
            ->orderByPivot('is_primary', 'desc')
            ->orderBy('name');
    }

    /**
     * Vorgabe zum Anzeigen, z. B. „3 × 10-12 je Seite“.
     */
    public function prescription(): ?string
    {
        if (blank($this->target)) {
            return $this->default_sets ? "{$this->default_sets} Sätze" : null;
        }

        return $this->default_sets ? "{$this->default_sets} × {$this->target}" : $this->target;
    }

    /**
     * Braucht die Übung nur Geräte, die im Haushalt vorhanden sind? Optionale Geräte zählen nicht.
     * Erwartet geladene `equipment`-Beziehung.
     */
    public function isDoableAtHome(): bool
    {
        return $this->equipment
            ->reject(fn (Equipment $equipment) => $equipment->pivot->is_optional)
            ->every(fn (Equipment $equipment) => $equipment->is_available);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Übungen, die die Muskelgruppe trainieren (als Haupt- oder Nebenmuskulatur).
     */
    #[Scope]
    protected function forMuscleGroup(Builder $query, ?string $slug): void
    {
        if (blank($slug)) {
            return;
        }

        $query->whereHas('muscleGroups', fn (Builder $query) => $query->where('slug', $slug));
    }

    #[Scope]
    protected function withEquipment(Builder $query, ?string $slug): void
    {
        if (blank($slug)) {
            return;
        }

        $query->whereHas('equipment', fn (Builder $query) => $query->where('slug', $slug));
    }

    /**
     * Nur Übungen, für die alle nötigen (nicht optionalen) Geräte vorhanden sind.
     */
    #[Scope]
    protected function doableAtHome(Builder $query): void
    {
        $query->whereDoesntHave('equipment', fn (Builder $query) => $query
            ->where('is_available', false)
            ->where('equipment_exercise.is_optional', false));
    }

    #[Scope]
    protected function ofLevel(Builder $query, ?ExerciseLevel $level): void
    {
        if ($level !== null) {
            $query->where('level', $level);
        }
    }
}

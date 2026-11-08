<?php

namespace App\Support\Planning;

use App\Enums\MealSlot;
use App\Models\PlannedMeal;
use App\Support\Nutrition\Nutrients;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Ein Tag im persönlichen Wochenplaner: Einträge je Mahlzeit, Summe und Abgleich mit dem Tagesbudget.
 */
final class DayPlan
{
    private ?Nutrients $total = null;

    /**
     * @param  Collection<int, PlannedMeal>  $entries
     * @param  Nutrients|null  $target  Tagesziel (null = kein Budget, z. B. Kinder oder ohne Profil)
     */
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly Collection $entries,
        public readonly bool $isTrainingDay,
        public readonly ?Nutrients $target,
    ) {}

    /**
     * @return Collection<int, PlannedMeal>
     */
    public function entriesFor(MealSlot $slot): Collection
    {
        return $this->entries->filter(fn (PlannedMeal $entry) => $entry->meal === $slot)->values();
    }

    public function total(): Nutrients
    {
        return $this->total ??= $this->entries
            ->map(fn (PlannedMeal $entry) => $entry->nutrients())
            ->filter()
            ->reduce(fn (Nutrients $sum, Nutrients $n) => $sum->add($n), Nutrients::zero());
    }

    public function slotTotal(MealSlot $slot): Nutrients
    {
        return $this->entriesFor($slot)
            ->map(fn (PlannedMeal $entry) => $entry->nutrients())
            ->filter()
            ->reduce(fn (Nutrients $sum, Nutrients $n) => $sum->add($n), Nutrients::zero());
    }

    /**
     * Einträge ohne Nährwerte (Rezept noch nicht berechnet) – die Summe ist dann zu niedrig.
     */
    public function unknownCount(): int
    {
        return $this->entries->filter(fn (PlannedMeal $entry) => $entry->nutrients() === null)->count();
    }

    public function isEmpty(): bool
    {
        return $this->entries->isEmpty();
    }

    public function hasTarget(): bool
    {
        return $this->target !== null;
    }

    public function remainingKcal(): ?int
    {
        return $this->target ? (int) round($this->target->kcal - $this->total()->kcal) : null;
    }

    /**
     * Deutlich über dem Budget (Toleranz in config/nutrition.php, budget.over_tolerance)?
     */
    public function isOverBudget(): bool
    {
        if (! $this->target) {
            return false;
        }

        $tolerance = (float) config('nutrition.budget.over_tolerance');

        return $this->total()->kcal > $this->target->kcal * (1 + $tolerance);
    }

    /**
     * Anteil am Kalorienbudget in Prozent (für den Fortschrittsbalken, höchstens 100).
     */
    public function progressPercent(): int
    {
        if (! $this->target || $this->target->kcal <= 0) {
            return 0;
        }

        return (int) min(100, round($this->total()->kcal / $this->target->kcal * 100));
    }
}

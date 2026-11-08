<?php

namespace App\Support\Planning;

use App\Models\PlannedMeal;
use App\Models\TrainingDay;
use App\Models\User;
use App\Support\Nutrition\EnergyBudget;
use App\Support\Nutrition\Nutrients;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Persönlicher Wochenplan einer Person: sieben Tage mit Einträgen und Tagesbudget.
 *
 * Ein Budget gibt es nur mit Profil (Erwachsene); an markierten Trainingstagen gilt das Trainingstag-Budget.
 */
final class WeekPlan
{
    /**
     * @param  Collection<int, DayPlan>  $days  Schlüssel 1 (Montag) bis 7 (Sonntag)
     */
    private function __construct(
        public readonly Week $week,
        public readonly Collection $days,
        public readonly ?EnergyBudget $budget,
    ) {}

    public static function for(User $user, Week $week): self
    {
        $user->loadMissing('bodyProfile');
        $budget = $user->can('manage-body-profile') ? $user->bodyProfile?->budget() : null;

        $entries = PlannedMeal::query()
            ->whereBelongsTo($user)
            ->whereDate('date', '>=', $week->start)
            ->whereDate('date', '<=', $week->end())
            ->withDetails()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PlannedMeal $entry) => $entry->date->toDateString());

        $trainingDates = TrainingDay::query()
            ->whereBelongsTo($user)
            ->whereDate('date', '>=', $week->start)
            ->whereDate('date', '<=', $week->end())
            ->get()
            ->map(fn (TrainingDay $day) => $day->date->toDateString());

        $days = $week->days()->map(function (CarbonImmutable $date) use ($entries, $trainingDates, $budget) {
            $isTraining = $trainingDates->contains($date->toDateString());

            return new DayPlan(
                $date,
                $entries->get($date->toDateString(), collect()),
                $isTraining,
                $budget ? ($isTraining ? $budget->trainingDay() : $budget->restDay()) : null,
            );
        });

        return new self($week, $days, $budget);
    }

    public function total(): Nutrients
    {
        return $this->days->reduce(fn (Nutrients $sum, DayPlan $day) => $sum->add($day->total()), Nutrients::zero());
    }

    /**
     * Summe der Tagesbudgets aller Tage, an denen schon etwas geplant ist.
     */
    public function plannedDaysTargetKcal(): ?int
    {
        if (! $this->budget) {
            return null;
        }

        return (int) $this->plannedDays()->sum(fn (DayPlan $day) => $day->target->kcal);
    }

    /**
     * @return Collection<int, DayPlan>
     */
    public function plannedDays(): Collection
    {
        return $this->days->reject->isEmpty();
    }

    public function trainingDayCount(): int
    {
        return $this->days->filter->isTrainingDay->count();
    }
}

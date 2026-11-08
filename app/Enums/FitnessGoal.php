<?php

namespace App\Enums;

/**
 * Ziel des Profils; Kalorien-Zu-/Abschlag und Eiweiß je kg stehen in `config/nutrition.php` (budget.goals).
 */
enum FitnessGoal: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';

    public function label(): string
    {
        return match ($this) {
            self::Lose => 'Abnehmen',
            self::Maintain => 'Gewicht halten',
            self::Gain => 'Muskeln aufbauen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Lose => 'Moderates Defizit, viel Eiweiß, damit Muskeln erhalten bleiben.',
            self::Maintain => 'So viel essen, wie du verbrauchst.',
            self::Gain => 'Leichter Überschuss für Muskelaufbau mit Krafttraining.',
        };
    }

    public function kcalAdjustment(): int
    {
        return (int) config("nutrition.budget.goals.{$this->value}.kcal");
    }

    public function proteinPerKg(): float
    {
        return (float) config("nutrition.budget.goals.{$this->value}.protein_per_kg");
    }
}

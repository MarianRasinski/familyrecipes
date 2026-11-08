<?php

namespace App\Enums;

/**
 * Art des Trainings; der MET-Wert (Stoffwechseläquivalent) steht in `config/nutrition.php` (budget.training_met).
 */
enum TrainingType: string
{
    case Strength = 'strength';
    case Mixed = 'mixed';
    case Cardio = 'cardio';

    public function label(): string
    {
        return match ($this) {
            self::Strength => 'Krafttraining',
            self::Mixed => 'Gemischt (Kraft und Ausdauer)',
            self::Cardio => 'Ausdauer (Laufen, Rad, Schwimmen)',
        };
    }

    public function met(): float
    {
        return (float) config("nutrition.budget.training_met.{$this->value}");
    }
}

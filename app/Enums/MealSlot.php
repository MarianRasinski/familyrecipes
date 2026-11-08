<?php

namespace App\Enums;

/**
 * Mahlzeit im persönlichen Wochenplaner (Reihenfolge = Reihenfolge am Tag).
 */
enum MealSlot: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Frühstück',
            self::Lunch => 'Mittagessen',
            self::Dinner => 'Abendessen',
            self::Snack => 'Snacks',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Breakfast => '🥣',
            self::Lunch => '🍲',
            self::Dinner => '🍽️',
            self::Snack => '🍎',
        };
    }
}

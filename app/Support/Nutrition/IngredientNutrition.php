<?php

namespace App\Support\Nutrition;

use App\Models\Food;
use App\Models\Ingredient;

/**
 * Beitrag einer Zutat zu den Nährwerten eines Rezepts.
 */
final class IngredientNutrition
{
    public const COUNTED = 'counted';        // eingerechnet

    public const OPTIONAL = 'optional';      // optionale Zutat, nicht eingerechnet

    public const NEGLIGIBLE = 'negligible';  // Gewürz/ohne Menge, vernachlässigt

    public const NO_FOOD = 'no_food';        // kein Lebensmittel zugeordnet

    public const NO_WEIGHT = 'no_weight';    // Lebensmittel bekannt, Gewicht nicht

    public function __construct(
        public readonly Ingredient $ingredient,
        public readonly ?Food $food,
        public readonly WeightEstimate $weight,
        public readonly string $status,
        public readonly Nutrients $nutrients,
    ) {}

    public static function for(Ingredient $ingredient): self
    {
        $food = $ingredient->food;
        $weight = IngredientWeight::for($ingredient, $food);

        // Gewürze/Kräuter ohne Zuordnung oder ohne Menge fallen kaum ins Gewicht.
        // Andere Zutaten ohne Menge („Tomaten“) zählen dagegen als fehlendes Gewicht.
        $spice = FoodMatcher::isNegligible($ingredient->name);

        $status = match (true) {
            $ingredient->is_optional => self::OPTIONAL,
            $spice && ($food === null || $weight->negligible) => self::NEGLIGIBLE,
            $food === null => self::NO_FOOD,
            ! $weight->isKnown() => self::NO_WEIGHT,
            default => self::COUNTED,
        };

        $nutrients = Nutrients::zero();

        if ($status === self::COUNTED) {
            $nutrients = $food->nutrientsFor($weight->grams);
            $nutrients = $weight->estimated ? $nutrients->markEstimated() : $nutrients;
        }

        return new self($ingredient, $food, $weight, $status, $nutrients);
    }

    public function isCounted(): bool
    {
        return $this->status === self::COUNTED;
    }

    /**
     * Fehlt etwas, damit die Zutat eingerechnet werden kann?
     */
    public function isMissing(): bool
    {
        return in_array($this->status, [self::NO_FOOD, self::NO_WEIGHT], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::COUNTED => $this->nutrients->estimated ? 'geschätzt' : 'berechnet',
            self::OPTIONAL => 'optional, nicht eingerechnet',
            self::NEGLIGIBLE => 'vernachlässigt',
            self::NO_FOOD => 'kein Lebensmittel zugeordnet',
            self::NO_WEIGHT => 'Gewicht fehlt',
        };
    }
}

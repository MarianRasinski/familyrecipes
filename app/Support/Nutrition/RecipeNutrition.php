<?php

namespace App\Support\Nutrition;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Collection;

/**
 * Nährwerte eines Rezepts aus seinen Zutaten (gesamt und pro Portion).
 *
 * Optionale Zutaten und Gewürze ohne Menge zählen nicht mit. Fehlt bei einer Zutat das
 * Lebensmittel oder das Gewicht, ist das Ergebnis unvollständig und gilt als Schätzung.
 * Erwartet geladene Beziehung `ingredients.food`.
 */
final class RecipeNutrition
{
    /**
     * @param  Collection<int, IngredientNutrition>  $lines
     */
    private function __construct(
        public readonly Recipe $recipe,
        public readonly Collection $lines,
        public readonly Nutrients $total,
    ) {}

    public static function for(Recipe $recipe): self
    {
        $lines = $recipe->ingredients->map(fn (Ingredient $ingredient) => IngredientNutrition::for($ingredient));

        $total = $lines
            ->filter->isCounted()
            ->reduce(fn (Nutrients $sum, IngredientNutrition $line) => $sum->add($line->nutrients), Nutrients::zero());

        if ($lines->contains->isMissing()) {
            $total = $total->markEstimated();
        }

        return new self($recipe, $lines, $total);
    }

    public function perServing(): Nutrients
    {
        return $this->total->scale(1 / max(1, (int) $this->recipe->servings));
    }

    /**
     * Gibt es überhaupt berechnete Werte?
     */
    public function hasData(): bool
    {
        return $this->lines->contains->isCounted();
    }

    /**
     * Alle Zutaten zugeordnet und gewogen?
     */
    public function isComplete(): bool
    {
        return ! $this->lines->contains->isMissing();
    }

    public function isEstimated(): bool
    {
        return $this->total->estimated;
    }

    public function countedCount(): int
    {
        return $this->lines->filter->isCounted()->count();
    }

    public function missingCount(): int
    {
        return $this->lines->filter->isMissing()->count();
    }

    /**
     * Zutaten, die eingerechnet werden sollten (ohne optionale und vernachlässigte).
     */
    public function relevantCount(): int
    {
        return $this->countedCount() + $this->missingCount();
    }
}

<?php

namespace App\Enums;

use App\Models\Recipe;
use App\Support\Nutrition\RecipeNutrition;

/**
 * Filter der Rezeptübersicht, berechnet aus Nährwerten bzw. Zeiten (Grenzen in config/nutrition.php, recipe_filters).
 */
enum RecipeFilter: string
{
    case HighProtein = 'high-protein';
    case LowCarb = 'low-carb';
    case Quick = 'schnell';

    public function label(): string
    {
        return match ($this) {
            self::HighProtein => 'High Protein',
            self::LowCarb => 'Low Carb',
            self::Quick => 'Schnell',
        };
    }

    public function description(): string
    {
        $config = config('nutrition.recipe_filters');

        return match ($this) {
            self::HighProtein => sprintf('ab %d g Eiweiß pro Portion und mind. %d %% der Kalorien aus Eiweiß',
                $config['high_protein_min_g'], round($config['high_protein_min_share'] * 100)),
            self::LowCarb => sprintf('höchstens %d g Kohlenhydrate pro Portion, alle Zutaten berechnet', $config['low_carb_max_g']),
            self::Quick => sprintf('fertig in höchstens %d Minuten', $config['quick_max_minutes']),
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::HighProtein => '💪',
            self::LowCarb => '🥗',
            self::Quick => '⚡',
        };
    }

    /**
     * Erwartet geladene Beziehung `ingredients.food` (für die Nährwerte).
     */
    public function matches(Recipe $recipe, RecipeNutrition $nutrition): bool
    {
        $config = config('nutrition.recipe_filters');

        return match ($this) {
            // Fehlende Zutaten machen das Eiweiß nur kleiner – das Ergebnis bleibt vorsichtig
            self::HighProtein => $nutrition->hasData()
                && ($perServing = $nutrition->perServing())->protein >= $config['high_protein_min_g']
                && $perServing->kcal > 0
                && $perServing->protein * 4 / $perServing->kcal >= $config['high_protein_min_share'],
            // Fehlende Zutaten würden die Kohlenhydrate zu klein wirken lassen → nur vollständige Rezepte
            self::LowCarb => $nutrition->hasData()
                && $nutrition->isComplete()
                && $nutrition->perServing()->carbs <= $config['low_carb_max_g'],
            self::Quick => $recipe->total_minutes !== null && $recipe->total_minutes <= $config['quick_max_minutes'],
        };
    }
}

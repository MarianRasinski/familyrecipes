<?php

namespace App\Models;

use App\Enums\MealSlot;
use App\Support\Nutrition\Nutrients;
use Database\Factories\PlannedMealFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Eintrag im persönlichen Wochenplaner: Rezept × Portionen oder Lebensmittel × Gramm.
 */
class PlannedMeal extends Model
{
    /** @use HasFactory<PlannedMealFactory> */
    use HasFactory;

    // Zwischenspeicher für nutrients() (Rezeptberechnung läuft sonst je Anzeige mehrfach)
    private ?Nutrients $nutrients = null;

    private bool $nutrientsCalculated = false;

    /**
     * user_id wird nur über die Beziehung gesetzt.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'meal',
        'position',
        'recipe_id',
        'servings',
        'food_id',
        'grams',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal' => MealSlot::class,
            'position' => 'integer',
            'servings' => 'float',
            'grams' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * Alles, was für Anzeige und Nährwerte gebraucht wird.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with(['recipe.ingredients.food', 'food']);
    }

    public function title(): string
    {
        return $this->recipe?->title ?? $this->food?->name ?? '–';
    }

    /**
     * „1 Portion“, „1,5 Portionen“ oder „80 g“.
     */
    public function amountLabel(): string
    {
        if ($this->recipe_id !== null) {
            $servings = (float) $this->servings;

            return self::number($servings).' '.($servings === 1.0 ? 'Portion' : 'Portionen');
        }

        return self::number((float) $this->grams).' g';
    }

    /**
     * Nährwerte des Eintrags; null, wenn für das Rezept noch nichts berechnet ist.
     * Erwartet geladene Beziehungen (scopeWithDetails).
     */
    public function nutrients(): ?Nutrients
    {
        if ($this->nutrientsCalculated) {
            return $this->nutrients;
        }

        $this->nutrientsCalculated = true;

        if ($this->recipe) {
            $nutrition = $this->recipe->nutrition();

            return $this->nutrients = $nutrition->hasData() ? $nutrition->perServing()->scale((float) $this->servings) : null;
        }

        return $this->nutrients = $this->food?->nutrientsFor((float) $this->grams);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }
}

<?php

namespace App\Models;

use App\Enums\FoodCategory;
use App\Support\Nutrition\Nutrients;
use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lebensmittel mit Nährwerten je 100 g (BLS 4.0 oder eigener Eintrag).
 */
class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory;

    // „food“ ist für Laravels Pluralisierung unzählbar → Tabellenname explizit
    protected $table = 'foods';

    protected $fillable = [
        'bls_code',
        'name',
        'name_en',
        'category',
        'kcal',
        'protein',
        'fat',
        'carbs',
        'fiber',
        'sugar',
        'saturated_fat',
        'salt',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'category' => FoodCategory::class,
            'kcal' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
            'fiber' => 'float',
            'sugar' => 'float',
            'saturated_fat' => 'float',
            'salt' => 'float',
        ];
    }

    /**
     * Nährwerte für eine Menge in Gramm.
     */
    public function nutrientsFor(float $grams): Nutrients
    {
        return Nutrients::fromPer100g($this->only(Nutrients::FIELDS), $grams);
    }

    /**
     * Fehlen Werte (keine Daten im BLS)?
     */
    public function hasMissingValues(): bool
    {
        return collect($this->only(Nutrients::FIELDS))->contains(null);
    }

    /**
     * Jedes Suchwort muss im Namen vorkommen („zwiebel roh“ findet „Speisezwiebel roh“).
     * Reihenfolge: Grundlebensmittel vor Gerichten und Gebäck, rohe zuerst (Rezeptzutaten sind
     * meist roh), dann Treffer am Namensanfang und kurze Namen – „Olivenöl“ vor „Olivenöl-Kräuter-Dip“.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $words = preg_split('/\s+/u', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === []) {
            return;
        }

        foreach ($words as $word) {
            $query->where('name', 'like', '%'.addcslashes($word, '%_\\').'%');
        }

        $composite = [FoodCategory::Dishes->value, FoodCategory::MeatAndFishDishes->value, FoodCategory::Pastries->value];

        $query->orderByRaw('CASE WHEN category IN (?, ?, ?) THEN 1 ELSE 0 END', $composite)
            ->orderByRaw("CASE WHEN name LIKE '%roh%' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [addcslashes($words[0], '%_\\').'%'])
            ->orderByRaw('LENGTH(name)');
    }

    #[Scope]
    protected function inCategory(Builder $query, ?FoodCategory $category): void
    {
        if ($category !== null) {
            $query->where('category', $category);
        }
    }
}

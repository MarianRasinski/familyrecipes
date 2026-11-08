<?php

namespace App\Http\Requests;

use App\Enums\MealSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Eintrag im persönlichen Plan: entweder Rezept + Portionen oder Lebensmittel + Gramm.
 * Jede Person plant nur für sich selbst (kein Benutzer-Feld).
 */
class StorePlannedMealRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after:'.now()->subYear()->toDateString(), 'before:'.now()->addYear()->toDateString()],
            'meal' => ['required', Rule::enum(MealSlot::class)],
            'type' => ['required', Rule::in(['recipe', 'food'])],
            'recipe_id' => ['exclude_unless:type,recipe', 'required', 'integer', Rule::exists('recipes', 'id')],
            'servings' => ['exclude_unless:type,recipe', 'required', 'numeric', 'between:0.25,20'],
            'food_id' => ['exclude_unless:type,food', 'required', 'integer', Rule::exists('foods', 'id')],
            'grams' => ['exclude_unless:type,food', 'required', 'numeric', 'between:1,5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => 'Tag',
            'meal' => 'Mahlzeit',
            'recipe_id' => 'Rezept',
            'servings' => 'Portionen',
            'food_id' => 'Lebensmittel',
            'grams' => 'Menge',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipe_id.required' => 'Bitte wähle ein Rezept aus.',
            'food_id.required' => 'Bitte wähle ein Lebensmittel aus.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Deutsche Kommazahlen („1,5“) zulassen
        $this->merge(collect(['servings', 'grams'])
            ->filter(fn (string $key) => $this->has($key))
            ->mapWithKeys(fn (string $key) => [$key => str_replace(',', '.', (string) $this->input($key))])
            ->all());
    }
}

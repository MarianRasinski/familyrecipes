<?php

namespace App\Http\Requests;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipeNutritionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('recipe'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Recipe $recipe */
        $recipe = $this->route('recipe');

        return [
            'ingredients' => ['required', 'array'],
            'ingredients.*.id' => ['required', 'integer', 'distinct', Rule::exists('ingredients', 'id')->where('recipe_id', $recipe->id)],
            'ingredients.*.food_id' => ['nullable', 'integer', Rule::exists('foods', 'id')],
            'ingredients.*.grams' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ingredients.*.food_id' => 'Lebensmittel',
            'ingredients.*.grams' => 'Gewicht',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Deutsche Kommazahlen („12,5“) zulassen
        $this->merge([
            'ingredients' => collect($this->input('ingredients', []))
                ->map(fn ($row) => is_array($row) && isset($row['grams']) ? [...$row, 'grams' => str_replace(',', '.', (string) $row['grams'])] : $row)
                ->all(),
        ]);
    }
}

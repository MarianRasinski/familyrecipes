<?php

namespace App\Http\Requests;

use App\Models\PlannedMeal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Menge eines Eintrags ändern: Portionen (Rezept) bzw. Gramm (Lebensmittel).
 */
class UpdatePlannedMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plannedMeal'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PlannedMeal $entry */
        $entry = $this->route('plannedMeal');

        return [
            'amount' => $entry->recipe_id !== null
                ? ['required', 'numeric', 'between:0.25,20']
                : ['required', 'numeric', 'between:1,5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['amount' => 'Menge'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => str_replace(',', '.', (string) $this->input('amount'))]);
        }
    }
}

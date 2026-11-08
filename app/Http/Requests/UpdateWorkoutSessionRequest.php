<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Trainingsprotokoll speichern: je Satz Wiederholungen bzw. Sekunden, Gewicht und „erledigt“.
 */
class UpdateWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workoutSession'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sets' => ['array'],
            'sets.*.reps' => ['nullable', 'integer', 'between:0,1000'],
            'sets.*.seconds' => ['nullable', 'integer', 'between:0,36000'],
            'sets.*.weight_kg' => ['nullable', 'numeric', 'between:0,999'],
            'sets.*.done' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'finish' => ['nullable', 'boolean'],
            'add_set' => ['nullable', 'integer', 'min:1'],
            'remove_set' => ['nullable', 'integer', Rule::exists('workout_sets', 'id')->where('workout_session_id', $this->route('workoutSession')->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sets.*.reps' => 'Wiederholungen',
            'sets.*.seconds' => 'Sekunden',
            'sets.*.weight_kg' => 'Gewicht',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Deutsche Kommazahlen beim Gewicht („12,5“)
        $this->merge([
            'sets' => collect($this->input('sets', []))
                ->map(fn ($row) => is_array($row) && isset($row['weight_kg']) ? [...$row, 'weight_kg' => str_replace(',', '.', (string) $row['weight_kg'])] : $row)
                ->all(),
        ]);
    }
}

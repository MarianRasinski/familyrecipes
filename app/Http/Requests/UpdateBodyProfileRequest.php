<?php

namespace App\Http\Requests;

use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Enums\Sex;
use App\Enums\TrainingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBodyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-body-profile');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sex' => ['required', Rule::enum(Sex::class)],
            // Die Formeln gelten für Erwachsene
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:'.now()->subYears(110)->toDateString()],
            'height_cm' => ['required', 'integer', 'between:120,230'],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'activity_level' => ['required', Rule::enum(ActivityLevel::class)],
            'goal' => ['required', Rule::enum(FitnessGoal::class)],
            'training_days' => ['required', 'integer', 'between:0,7'],
            'training_minutes' => ['nullable', 'required_unless:training_days,0', 'integer', 'between:10,300'],
            'training_type' => ['required', Rule::enum(TrainingType::class)],
            'kcal_override' => ['nullable', 'integer', 'between:1000,6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sex' => 'Geschlecht',
            'birth_date' => 'Geburtsdatum',
            'height_cm' => 'Größe',
            'weight_kg' => 'Gewicht',
            'activity_level' => 'Aktivität im Alltag',
            'goal' => 'Ziel',
            'training_days' => 'Trainingstage pro Woche',
            'training_minutes' => 'Dauer einer Einheit',
            'training_type' => 'Trainingsart',
            'kcal_override' => 'eigenes Tagesbudget',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Das Profil ist vorerst nur für Erwachsene (ab 18 Jahren) gedacht.',
            'training_minutes.required_unless' => 'Bitte gib an, wie lange eine Einheit dauert.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Deutsche Kommazahlen („72,5“) zulassen
        if ($this->has('weight_kg')) {
            $this->merge(['weight_kg' => str_replace(',', '.', (string) $this->input('weight_kg'))]);
        }
    }
}

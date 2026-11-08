<?php

namespace App\Http\Requests;

use App\Models\Workout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Einheit speichern: Name, Notizen, je Übung Sätze/Ziel/Pause/Notiz; optional entfernen oder verschieben.
 */
class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workout'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Workout $workout */
        $workout = $this->route('workout');

        return [
            'name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'exercises' => ['array'],
            'exercises.*.sets' => ['required', 'integer', 'between:1,20'],
            'exercises.*.target' => ['nullable', 'string', 'max:50'],
            'exercises.*.rest_seconds' => ['nullable', 'integer', 'between:0,900'],
            'exercises.*.notes' => ['nullable', 'string', 'max:200'],
            'remove' => ['nullable', 'integer', Rule::exists('workout_exercises', 'id')->where('workout_id', $workout->id)],
            'move' => ['nullable', 'string', 'regex:/^\d+:(up|down)$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'exercises.*.sets' => 'Sätze',
            'exercises.*.target' => 'Ziel',
            'exercises.*.rest_seconds' => 'Pause',
        ];
    }
}

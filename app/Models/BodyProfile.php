<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Enums\Sex;
use App\Enums\TrainingType;
use App\Support\Nutrition\EnergyBudget;
use Carbon\CarbonInterface;
use Database\Factories\BodyProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Körperdaten und Ziel einer Person – Grundlage für das Tagesbudget ({@see EnergyBudget}).
 */
class BodyProfile extends Model
{
    /** @use HasFactory<BodyProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sex',
        'birth_date',
        'height_cm',
        'weight_kg',
        'activity_level',
        'goal',
        'training_days',
        'training_minutes',
        'training_type',
        'kcal_override',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'birth_date' => 'date',
            'height_cm' => 'integer',
            'weight_kg' => 'float',
            'activity_level' => ActivityLevel::class,
            'goal' => FitnessGoal::class,
            'training_days' => 'integer',
            'training_minutes' => 'integer',
            'training_type' => TrainingType::class,
            'kcal_override' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ageOn(?CarbonInterface $date = null): int
    {
        return (int) $this->birth_date->diffInYears($date ?? now());
    }

    public function budget(?CarbonInterface $date = null): EnergyBudget
    {
        return new EnergyBudget($this, $date);
    }
}

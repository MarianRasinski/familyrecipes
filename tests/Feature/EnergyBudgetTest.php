<?php

namespace Tests\Feature;

use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Enums\Sex;
use App\Enums\TrainingType;
use App\Models\BodyProfile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnergyBudgetTest extends TestCase
{
    private function profile(array $attributes = []): BodyProfile
    {
        // Reine Rechnung ohne Datenbank (user_id gesetzt, sonst legt die Factory einen Benutzer an)
        return BodyProfile::factory()->make([
            'user_id' => 1,
            'sex' => Sex::Male,
            'birth_date' => '1991-01-01',
            'height_cm' => 180,
            'weight_kg' => 80,
            'activity_level' => ActivityLevel::Light,
            'goal' => FitnessGoal::Lose,
            ...$attributes,
        ]);
    }

    private function date(): Carbon
    {
        return Carbon::parse('2026-11-08');
    }

    public function test_calculates_bmr_maintenance_and_target_for_a_man(): void
    {
        $budget = $this->profile()->budget($this->date());

        $this->assertSame(35, $budget->age());
        // 10 × 80 + 6,25 × 180 − 5 × 35 + 5
        $this->assertSame(1755.0, $budget->basalMetabolicRate());
        $this->assertEqualsWithDelta(2808.0, $budget->maintenance(), 0.001);
        // 2808 − 500 = 2308 → auf 10 gerundet
        $this->assertSame(2310, $budget->restDayKcal());
    }

    public function test_uses_the_female_formula(): void
    {
        $budget = $this->profile([
            'sex' => Sex::Female, 'birth_date' => '1996-01-01', 'height_cm' => 165, 'weight_kg' => 60,
            'activity_level' => ActivityLevel::Sedentary, 'goal' => FitnessGoal::Maintain,
        ])->budget($this->date());

        // 600 + 1031,25 − 150 − 161
        $this->assertSame(1320.25, $budget->basalMetabolicRate());
        // × 1,4 = 1848,35
        $this->assertSame(1850, $budget->restDayKcal());
    }

    public function test_macros_split_protein_fat_and_carbs(): void
    {
        $targets = $this->profile()->budget($this->date())->restDay();

        $this->assertSame(2310.0, $targets->kcal);
        $this->assertSame(160.0, $targets->protein);  // 2,0 g × 80 kg
        $this->assertSame(77.0, $targets->fat);       // 30 % von 2310 ÷ 9
        $this->assertSame(244.0, $targets->carbs);    // (2310 − 640 − 693) ÷ 4
        $this->assertSame(30.0, $targets->fiber);
    }

    public function test_protein_uses_reference_weight_for_a_high_bmi(): void
    {
        $budget = $this->profile(['weight_kg' => 120])->budget($this->date());

        // BMI 25 bei 1,80 m = 81 kg
        $this->assertSame(81.0, $budget->proteinReferenceWeight());
        $this->assertSame(162.0, $budget->restDay()->protein);
    }

    public function test_training_days_add_the_training_consumption(): void
    {
        $budget = $this->profile([
            'training_days' => 3, 'training_minutes' => 60, 'training_type' => TrainingType::Strength,
        ])->budget($this->date());

        // (5 − 1) × 80 kg × 1 h
        $this->assertSame(320, $budget->trainingKcal());
        $this->assertSame(2630, $budget->trainingDayKcal());
        // (2310 × 4 + 2630 × 3) ÷ 7 = 2447 → 2450
        $this->assertSame(2450, $budget->weeklyAverageKcal());
        // Eiweiß bleibt gleich, die Extra-Kalorien gehen in die Kohlenhydrate
        $this->assertSame(160.0, $budget->trainingDay()->protein);
        $this->assertGreaterThan($budget->restDay()->carbs, $budget->trainingDay()->carbs);
    }

    public function test_no_training_means_no_extra(): void
    {
        $budget = $this->profile(['training_days' => 0])->budget($this->date());

        $this->assertSame(0, $budget->trainingKcal());
        $this->assertSame($budget->restDayKcal(), $budget->weeklyAverageKcal());
    }

    public function test_override_replaces_the_rest_day_target(): void
    {
        $budget = $this->profile(['kcal_override' => 2000, 'training_days' => 2])->budget($this->date());

        $this->assertTrue($budget->isOverridden());
        $this->assertSame(2310, $budget->calculatedTarget());
        $this->assertSame(2000, $budget->restDayKcal());
        $this->assertSame(2320, $budget->trainingDayKcal());
        $this->assertContains('Selbst festgelegtes Budget', array_column($budget->steps(), 'label'));
    }

    public function test_warns_when_budget_is_below_bmr(): void
    {
        $budget = $this->profile(['kcal_override' => 1500])->budget($this->date());

        $this->assertCount(1, $budget->warnings());
        $this->assertStringContainsString('Grundumsatz', $budget->warnings()[0]);
        $this->assertSame([], $this->profile()->budget($this->date())->warnings());
    }

    public function test_steps_show_the_calculation(): void
    {
        $steps = $this->profile()->budget($this->date())->steps();

        $this->assertSame('10 × 80 kg + 6,25 × 180 cm − 5 × 35 Jahre + 5', $steps[0]['formula']);
        $this->assertSame('1.755 kcal', $steps[0]['result']);
        $this->assertSame('2.310 kcal', $steps[2]['result']);
    }
}

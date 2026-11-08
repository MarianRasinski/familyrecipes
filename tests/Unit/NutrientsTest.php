<?php

namespace Tests\Unit;

use App\Support\Nutrition\Nutrients;
use PHPUnit\Framework\TestCase;

class NutrientsTest extends TestCase
{
    public function test_values_are_scaled_from_per_100g(): void
    {
        $oats = Nutrients::fromPer100g(['kcal' => 348, 'protein' => 13.22, 'fat' => 6.65, 'carbs' => 53.3, 'fiber' => 10.98], grams: 50);

        $this->assertSame(['kcal' => 174, 'protein' => 6.6, 'fat' => 3.3, 'carbs' => 26.7, 'fiber' => 5.5], $oats->rounded());
        $this->assertFalse($oats->estimated);
    }

    public function test_missing_values_count_as_zero_and_mark_the_result_as_estimated(): void
    {
        $nutrients = Nutrients::fromPer100g(['kcal' => 100, 'protein' => 2, 'fat' => 1, 'carbs' => 20, 'fiber' => null]);

        $this->assertSame(0.0, $nutrients->fiber);
        $this->assertTrue($nutrients->estimated);
    }

    public function test_adding_keeps_the_estimated_flag(): void
    {
        $exact = new Nutrients(100, 10, 5, 2, 1);
        $estimate = (new Nutrients(50, 1, 1, 10, 0))->markEstimated();

        $sum = $exact->add($estimate);

        $this->assertSame(150.0, $sum->kcal);
        $this->assertSame(11.0, $sum->protein);
        $this->assertTrue($sum->estimated);
        $this->assertFalse($exact->add($exact)->estimated);
    }

    public function test_scaling_divides_into_portions(): void
    {
        $portion = (new Nutrients(800, 40, 20, 100, 12))->scale(1 / 4);

        $this->assertSame(['kcal' => 200, 'protein' => 10.0, 'fat' => 5.0, 'carbs' => 25.0, 'fiber' => 3.0], $portion->rounded());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Models\Food;
use App\Models\Ingredient;
use App\Support\Nutrition\IngredientWeight;
use Tests\TestCase;

class IngredientWeightTest extends TestCase
{
    public function test_weight_units_are_exact(): void
    {
        $this->assertWeight(250.0, false, IngredientWeight::estimate('250', 'g', 'Magerquark'));
        $this->assertWeight(1500.0, false, IngredientWeight::estimate('1,5', 'kg', 'Kartoffeln'));
        $this->assertWeight(400.0, false, IngredientWeight::estimate('400', 'ml', 'Vegane Sahne'));
    }

    public function test_ranges_use_the_middle_and_are_estimated(): void
    {
        $this->assertWeight(550.0, true, IngredientWeight::estimate('500–600', 'g', 'Süßkartoffeln'));
    }

    public function test_spoons_depend_on_the_food_group(): void
    {
        $oil = new Food(['category' => FoodCategory::FatsAndOils]);

        $this->assertWeight(20.0, true, IngredientWeight::estimate('2', 'EL', 'Olivenöl', $oil));
        $this->assertWeight(30.0, true, IngredientWeight::estimate('2', 'EL', 'Naturjoghurt'));
        $this->assertWeight(5.0, true, IngredientWeight::estimate('1', 'TL', 'Honig'));
    }

    public function test_pieces_use_average_weights_and_size(): void
    {
        $this->assertWeight(120.0, true, IngredientWeight::estimate('2', 'Stück', 'Eier'));
        $this->assertWeight(56.0, true, IngredientWeight::estimate('1', 'klein', 'Rote Zwiebel'));
        $this->assertWeight(208.0, true, IngredientWeight::estimate('1', 'groß', 'Paprika'));
        $this->assertWeight(10.0, true, IngredientWeight::estimate('2–3', 'Stück', 'Knoblauchzehen'));
        $this->assertWeight(30.0, true, IngredientWeight::estimate('2', '', 'Kirschtomaten'));
        $this->assertWeight(150.0, true, IngredientWeight::estimate('1', 'Stück', 'Hähnchenbrustfilet'));
    }

    public function test_unknown_cases_have_no_weight(): void
    {
        $this->assertNull(IngredientWeight::estimate('1', 'Stück', 'Drachenfrucht-Chutney')->grams);
        $this->assertNull(IngredientWeight::estimate('1', 'Schuss', 'Milch')->grams);
        $this->assertNull(IngredientWeight::estimate('nach Geschmack', '', 'Salz')->grams);
    }

    public function test_missing_amount_is_flagged(): void
    {
        $estimate = IngredientWeight::estimate(null, null, 'Salz');

        $this->assertTrue($estimate->negligible);
        $this->assertNull($estimate->grams);
    }

    public function test_entered_grams_win(): void
    {
        $ingredient = new Ingredient(['amount' => '2', 'unit' => 'EL', 'name' => 'Olivenöl', 'grams' => 18]);

        $this->assertWeight(18.0, false, IngredientWeight::for($ingredient));
    }

    private function assertWeight(float $grams, bool $estimated, $estimate): void
    {
        $this->assertEqualsWithDelta($grams, $estimate->grams, 0.01);
        $this->assertSame($estimated, $estimate->estimated);
    }
}

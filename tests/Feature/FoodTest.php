<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->child()->create());
    }

    public function test_food_nutrients_are_calculated_for_an_amount(): void
    {
        $oil = Food::factory()->create(['kcal' => 899, 'protein' => 0, 'fat' => 99.9, 'carbs' => 0, 'fiber' => 0]);

        $this->assertSame(['kcal' => 90, 'protein' => 0.0, 'fat' => 10.0, 'carbs' => 0.0, 'fiber' => 0.0], $oil->nutrientsFor(10)->rounded());
        $this->assertFalse($oil->nutrientsFor(10)->estimated);
    }

    public function test_search_needs_every_word_and_prefers_short_names_starting_with_the_term(): void
    {
        Food::factory()->create(['name' => 'Zwiebelkuchen']);
        Food::factory()->create(['name' => 'Speisezwiebel roh']);
        Food::factory()->create(['name' => 'Speisezwiebel gedünstet']);
        Food::factory()->create(['name' => 'Zwiebel roh']);

        $names = fn (string $q) => $this->get(route('foods.index', ['q' => $q]))->viewData('foods')->pluck('name')->all();

        $this->assertSame(['Zwiebel roh', 'Speisezwiebel roh'], $names('zwiebel roh'));
        $this->assertSame('Zwiebel roh', $names('zwiebel')[0]);
    }

    public function test_search_puts_raw_basic_foods_before_dishes(): void
    {
        Food::factory()->create(['name' => 'Zwiebeln gebraten', 'category' => FoodCategory::Dishes]);
        Food::factory()->create(['name' => 'Zwiebelkuchen', 'category' => FoodCategory::Pastries]);
        Food::factory()->create(['name' => 'Speisezwiebel gedünstet']);
        Food::factory()->create(['name' => 'Speisezwiebel roh']);

        $names = $this->get(route('foods.index', ['q' => 'zwiebel']))->viewData('foods')->pluck('name')->all();

        $this->assertSame(['Speisezwiebel roh', 'Speisezwiebel gedünstet', 'Zwiebelkuchen', 'Zwiebeln gebraten'], $names);
    }

    public function test_index_filters_by_category(): void
    {
        Food::factory()->create(['name' => 'Apfel roh', 'category' => FoodCategory::Fruit]);
        Food::factory()->create(['name' => 'Möhre roh', 'category' => FoodCategory::Vegetables]);

        $this->get(route('foods.index', ['gruppe' => 'F']))
            ->assertOk()
            ->assertSee('Apfel roh')
            ->assertDontSee('Möhre roh');
    }

    public function test_missing_values_are_flagged(): void
    {
        Food::factory()->create(['name' => 'Acerola roh', 'fiber' => null]);

        $this->get(route('foods.index'))
            ->assertSee('Acerola roh')
            ->assertSee('unvollständige Daten');
    }

    public function test_source_is_credited(): void
    {
        $this->get(route('foods.index'))->assertSee('Bundeslebensmittelschlüssel (BLS), Version 4.0');
    }
}

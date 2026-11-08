<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Models\Food;
use App\Support\Nutrition\FoodMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodMatcherTest extends TestCase
{
    use RefreshDatabase;

    private FoodMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new FoodMatcher;
    }

    public function test_synonyms_map_to_bls_codes_also_in_plural(): void
    {
        $egg = Food::factory()->create(['bls_code' => 'E111100', 'name' => 'Hühnerei roh']);
        $oats = Food::factory()->create(['bls_code' => 'C133000', 'name' => 'Hafer Flocken']);

        $this->assertTrue($egg->is($this->matcher->suggest('Ei')));
        $this->assertTrue($egg->is($this->matcher->suggest('Eier')));
        $this->assertTrue($oats->is($this->matcher->suggest('Zarte Haferflocken')));
    }

    public function test_brackets_alternatives_and_filler_words_are_ignored(): void
    {
        $chicken = Food::factory()->create(['bls_code' => 'V416100', 'name' => 'Hähnchen Brustfilet, roh']);
        $tofu = Food::factory()->create(['bls_code' => 'H861000', 'name' => 'Tofu']);

        $this->assertTrue($chicken->is($this->matcher->suggest('Hähnchenbrust oder Putenschnitzel (optional)')));
        $this->assertTrue($tofu->is($this->matcher->suggest('Frischer Natur-Tofu')));
    }

    public function test_free_search_prefers_raw_basic_foods(): void
    {
        Food::factory()->create(['name' => 'Zucchini gegrillt']);
        Food::factory()->create(['name' => 'Zucchinisuppe', 'category' => FoodCategory::Dishes]);
        $raw = Food::factory()->create(['name' => 'Rote Bete roh']);

        $this->assertTrue($raw->is($this->matcher->suggest('Rote Bete')));
    }

    public function test_spices_get_no_free_search_suggestion(): void
    {
        Food::factory()->create(['name' => 'Krabben in Dillcreme', 'category' => FoodCategory::Fish]);

        $this->assertNull($this->matcher->suggest('Frischer Dill'));
        $this->assertTrue(FoodMatcher::isNegligible('Frischer Dill'));
        $this->assertFalse(FoodMatcher::isNegligible('Gewürzgurken'));
    }

    public function test_unknown_ingredients_have_no_suggestion(): void
    {
        $this->assertNull($this->matcher->suggest('Zitronen Flavor Powder'));
    }
}

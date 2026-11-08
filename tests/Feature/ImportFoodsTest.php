<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Models\Food;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportFoodsTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "code,name,name_en,kcal,protein,fat,carbs,fiber,sugar,saturated_fat,salt,missing\n";

    public function test_foods_are_imported_with_category_and_nullable_values(): void
    {
        $path = $this->csv(
            "G480100,Speisezwiebel roh,Onion raw,34,1.16,0.15,6.01,1.4,6.01,0.03,0.02,\n".
            "F511100,Acerola roh,Acerola raw,40,0.4,0.3,7.1,,7,0.07,0.02,fiber\n"
        );

        $this->artisan('foods:import', ['path' => $path])
            ->expectsOutputToContain('2 neu, 0 aktualisiert')
            ->assertSuccessful();

        $onion = Food::where('bls_code', 'G480100')->sole();
        $this->assertSame('Speisezwiebel roh', $onion->name);
        $this->assertSame(FoodCategory::Vegetables, $onion->category);
        $this->assertSame(1.16, $onion->protein);
        $this->assertSame('BLS 4.0', $onion->source);

        $acerola = Food::where('bls_code', 'F511100')->sole();
        $this->assertNull($acerola->fiber);
        $this->assertTrue($acerola->hasMissingValues());
    }

    public function test_reimport_updates_bls_entries_and_keeps_own_foods(): void
    {
        $own = Food::factory()->create(['bls_code' => null, 'name' => 'Omas Pesto']);
        $path = $this->csv("G480100,Speisezwiebel roh,Onion raw,34,1.16,0.15,6.01,1.4,6.01,0.03,0.02,\n");
        $this->artisan('foods:import', ['path' => $path])->assertSuccessful();

        $path = $this->csv("G480100,Speisezwiebel roh,Onion raw,36,1.2,0.15,6.01,1.4,6.01,0.03,0.02,\n");
        $this->artisan('foods:import', ['path' => $path])
            ->expectsOutputToContain('0 neu, 1 aktualisiert')
            ->assertSuccessful();

        $this->assertSame(36.0, Food::where('bls_code', 'G480100')->sole()->kcal);
        $this->assertTrue($own->fresh()->exists);
        $this->assertSame(2, Food::count());
    }

    public function test_unknown_format_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'foods');
        file_put_contents($path, "a,b,c\n1,2,3\n");

        $this->artisan('foods:import', ['path' => $path])->assertFailed();
        $this->assertSame(0, Food::count());
    }

    public function test_bundled_bls_file_can_be_imported(): void
    {
        $this->artisan('foods:import')->assertSuccessful();

        $this->assertSame(7140, Food::count());
        $this->assertSame(899.0, Food::where('name', 'Olivenöl')->sole()->kcal);
    }

    private function csv(string $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'foods');
        file_put_contents($path, self::HEADER.$rows);

        return $path;
    }
}

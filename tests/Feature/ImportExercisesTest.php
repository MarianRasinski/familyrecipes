<?php

namespace Tests\Feature;

use App\Enums\ExerciseLevel;
use App\Enums\ExerciseMeasure;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportExercisesTest extends TestCase
{
    use RefreshDatabase;

    public function test_exercises_are_imported_with_equipment_and_muscle_groups(): void
    {
        $this->artisan('exercises:import', ['path' => $this->fixture()])->assertSuccessful();

        $twist = Exercise::with('equipment', 'muscleGroups')->where('name', 'Russian Twist')->sole();

        $this->assertSame('Ganzkörpertraining', $twist->category);
        $this->assertSame(ExerciseLevel::Intermediate, $twist->level);
        $this->assertSame(3, $twist->default_sets);
        $this->assertSame(ExerciseMeasure::Reps, $twist->measure);
        $this->assertSame([16, 20], [$twist->target_min, $twist->target_max]);
        $this->assertSame('3 × 16-20 insgesamt', $twist->prescription());

        // „Kettlebell optional“ → Gerät Kettlebell, als optional markiert
        $this->assertEqualsCanonicalizing(['Kettlebell', 'Yogamatte'], $twist->equipment->pluck('name')->all());
        $this->assertTrue($twist->equipment->firstWhere('name', 'Kettlebell')->pivot->is_optional);
        $this->assertFalse($twist->equipment->firstWhere('name', 'Yogamatte')->pivot->is_optional);

        $this->assertSame(['Schräge Bauchmuskeln'], $twist->muscleGroups->filter(fn ($g) => $g->pivot->is_primary)->pluck('name')->all());
        $this->assertSame(['Gerader Bauchmuskel'], $twist->muscleGroups->reject(fn ($g) => $g->pivot->is_primary)->pluck('name')->all());
    }

    public function test_inventory_is_grouped_into_equipment_types_with_variants(): void
    {
        $this->artisan('exercises:import', ['path' => $this->fixture()])->assertSuccessful();

        $kettlebell = Equipment::where('name', 'Kettlebell')->sole();
        $this->assertTrue($kettlebell->is_available);
        $this->assertSame(['12 kg', '16 kg'], $kettlebell->variants);

        $this->assertTrue(Equipment::where('name', 'Yogamatte')->sole()->is_available);

        // Gerät nur bei einer Übung, nicht im Bestand → nicht vorhanden
        $this->assertFalse(Equipment::where('name', 'Klimmzugstange')->sole()->is_available);

        $this->assertSame(1, Equipment::where('name', 'like', 'Kettlebell%')->count());
    }

    public function test_reimport_updates_instead_of_duplicating(): void
    {
        $path = $this->fixture();
        $this->artisan('exercises:import', ['path' => $path])->assertSuccessful();

        $data = json_decode(file_get_contents($path), true);
        $data['uebungen'][0]['saetze'] = 5;
        file_put_contents($path, json_encode($data));

        $this->artisan('exercises:import', ['path' => $path])
            ->expectsOutputToContain('0 neu, 3 aktualisiert')
            ->assertSuccessful();

        $this->assertSame(3, Exercise::count());
        $this->assertSame(5, Exercise::where('name', 'Plank')->sole()->default_sets);
        $this->assertSame(['12 kg', '16 kg'], Equipment::where('name', 'Kettlebell')->sole()->variants);
        $this->assertSame(1, MuscleGroup::where('name', 'Bauch')->count());
    }

    public function test_invalid_files_are_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'uebungen').'.json';
        file_put_contents($path, json_encode(['uebungen' => [['id' => 1]]]));

        $this->artisan('exercises:import', ['path' => $path])->assertFailed();

        $this->assertSame(0, Exercise::count());
    }

    public function test_bundled_catalog_can_be_imported(): void
    {
        $this->artisan('exercises:import')->assertSuccessful();

        $this->assertSame(20, Exercise::count());
        $this->assertSame(
            ['Kettlebell', 'Kurzhanteln', 'Liegestützgriffe', 'Rudermaschine', 'Yogamatte'],
            Equipment::orderBy('name')->pluck('name')->all(),
        );
    }

    private function fixture(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'uebungen').'.json';

        file_put_contents($path, json_encode([
            'kategorie' => 'Ganzkörpertraining',
            'trainingsgeraete' => ['Yogamatte', 'Kettlebell 12 kg', 'Kettlebell 16 kg'],
            'uebungen' => [
                [
                    'id' => 14, 'name' => 'Plank', 'geraet' => ['Yogamatte'],
                    'muskelgruppen_haupt' => ['Bauch'], 'muskelgruppen_neben' => ['Schultern'],
                    'schwierigkeit' => 'Anfänger', 'saetze' => 3, 'wiederholungen' => '30-60 Sekunden',
                    'beschreibung' => 'Unterarme aufstützen.',
                ],
                [
                    'id' => 15, 'name' => 'Russian Twist', 'geraet' => ['Yogamatte', 'Kettlebell optional'],
                    'muskelgruppen_haupt' => ['Schräge Bauchmuskeln'], 'muskelgruppen_neben' => ['Gerader Bauchmuskel'],
                    'schwierigkeit' => 'Mittel', 'saetze' => 3, 'wiederholungen' => '16-20 insgesamt',
                ],
                [
                    'id' => 30, 'name' => 'Klimmzug', 'geraet' => ['Klimmzugstange'],
                    'muskelgruppen_haupt' => ['Latissimus'], 'muskelgruppen_neben' => ['Bauch'],
                    'schwierigkeit' => 'Fortgeschritten', 'saetze' => 3, 'wiederholungen' => '5-8',
                ],
            ],
        ]));

        return $path;
    }
}

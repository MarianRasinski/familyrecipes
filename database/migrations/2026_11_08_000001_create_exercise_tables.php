<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Übungskatalog: Übungen, Trainingsgeräte und Muskelgruppen.
     *
     * Geräte und Muskelgruppen sind eigene Tabellen (statt JSON-Spalten), damit sich
     * Übungen später danach filtern und Trainingspläne darauf aufbauen lassen.
     */
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Gerätetyp, z. B. „Kettlebell“
            $table->string('slug')->unique();
            $table->json('variants')->nullable(); // vorhandene Ausführungen, z. B. ["12 kg", "16 kg"]
            $table->boolean('is_available')->default(false); // im Haushalt vorhanden
            $table->timestamps();
        });

        Schema::create('muscle_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable(); // z. B. „Ganzkörpertraining“
            $table->string('level')->nullable(); // App\Enums\ExerciseLevel
            $table->text('description')->nullable();

            // Standardvorgabe: Sätze × Ziel. Der Originaltext bleibt erhalten,
            // die Zahlen dienen später dem Trainingsprotokoll.
            $table->unsignedTinyInteger('default_sets')->nullable();
            $table->string('target')->nullable(); // Originaltext, z. B. „10-12 je Seite“
            $table->string('measure')->nullable(); // App\Enums\ExerciseMeasure
            $table->unsignedSmallInteger('target_min')->nullable(); // Wiederholungen bzw. Sekunden
            $table->unsignedSmallInteger('target_max')->nullable();
            $table->boolean('per_side')->default(false);

            // Herkunft beim Import, damit ein erneuter Import aktualisiert statt dupliziert.
            $table->string('source')->nullable();
            $table->unsignedInteger('external_id')->nullable();
            $table->unique(['source', 'external_id']);

            $table->timestamps();
        });

        Schema::create('equipment_exercise', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->primary(['exercise_id', 'equipment_id']);
        });

        Schema::create('exercise_muscle_group', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true); // Haupt- oder Nebenmuskulatur
            $table->primary(['exercise_id', 'muscle_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle_group');
        Schema::dropIfExists('equipment_exercise');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('muscle_groups');
        Schema::dropIfExists('equipment');
    }
};

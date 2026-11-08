<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trainingsplan pro Person.
     *
     * `workouts`: Trainingseinheiten („Ganzkörper A“) mit Übungen aus dem Katalog (`workout_exercises`).
     * `scheduled_workouts`: welche Einheit an welchem Wochentag (1 = Montag … 7 = Sonntag), jede Woche gleich.
     * `training_days.is_training`: Abweichung vom Wochenplan für einen einzelnen Tag (Training ausfallen lassen
     * oder zusätzlich trainieren).
     */
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('sets')->default(3);
            $table->string('target', 50)->nullable(); // z. B. „10-12“, „45 Sekunden“, „8 je Seite“
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->string('notes', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('scheduled_workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'weekday']);
        });

        Schema::table('training_days', function (Blueprint $table) {
            $table->boolean('is_training')->default(true)->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('training_days', function (Blueprint $table) {
            $table->dropColumn('is_training');
        });
        Schema::dropIfExists('scheduled_workouts');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workouts');
    }
};

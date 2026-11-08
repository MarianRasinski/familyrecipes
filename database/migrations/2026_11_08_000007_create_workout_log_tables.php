<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trainingsprotokoll: ein durchgeführtes Training (`workout_sessions`) mit den tatsächlichen Sätzen (`workout_sets`).
     *
     * Name und Ziel werden beim Start kopiert, damit das Protokoll stimmt, auch wenn die Einheit später geändert
     * oder gelöscht wird.
     */
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->date('date');
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position'); // Reihenfolge der Übung im Training
            $table->unsignedTinyInteger('set_number');
            $table->string('target', 50)->nullable(); // Vorgabe beim Start, z. B. „10-12“
            $table->unsignedSmallInteger('reps')->nullable();
            $table->unsignedSmallInteger('seconds')->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->boolean('done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
        Schema::dropIfExists('workout_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil & Ziele: ein Profil pro Person (vorerst nur Erwachsene), sichtbar nur für die Person selbst.
     *
     * `kcal_override` = selbst festgelegtes Tagesbudget für Ruhetage; leer = aus dem Profil berechnen.
     */
    public function up(): void
    {
        Schema::create('body_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sex', 10);
            $table->date('birth_date');
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 1);
            $table->string('activity_level', 20);
            $table->string('goal', 20);
            $table->unsignedTinyInteger('training_days')->default(0);
            $table->unsignedSmallInteger('training_minutes')->default(60);
            $table->string('training_type', 20)->default('strength');
            $table->unsignedSmallInteger('kcal_override')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_profiles');
    }
};

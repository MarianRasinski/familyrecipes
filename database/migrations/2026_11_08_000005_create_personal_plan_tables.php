<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persönlicher Wochenplaner: jede Person plant ihre eigenen Mahlzeiten.
     *
     * `planned_meals`: ein Rezept (mit Portionen) oder ein Lebensmittel (mit Gramm) in einer Mahlzeit.
     * `training_days`: Tage, an denen die Person trainiert (Zeile vorhanden = Trainingstag).
     */
    public function up(): void
    {
        Schema::create('planned_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal', 20);
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('recipe_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('servings', 4, 2)->nullable();
            $table->foreignId('food_id')->nullable()->constrained('foods')->cascadeOnDelete();
            $table->decimal('grams', 6, 1)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('training_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_days');
        Schema::dropIfExists('planned_meals');
    }
};

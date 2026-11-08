<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lebensmittel mit Nährwerten je 100 g essbarem Anteil (Grundlage: Bundeslebensmittelschlüssel 4.0).
     *
     * Nährwerte sind nullable: Ein fehlender Wert („keine Daten“) ist nicht dasselbe wie 0.
     */
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('bls_code', 16)->nullable()->unique(); // leer bei eigenen Lebensmitteln
            $table->string('name')->index();
            $table->string('name_en')->nullable();
            $table->string('category', 1)->nullable()->index(); // App\Enums\FoodCategory

            foreach (['kcal', 'protein', 'fat', 'carbs', 'fiber', 'sugar', 'saturated_fat', 'salt'] as $column) {
                $table->decimal($column, 7, 2)->nullable();
            }

            $table->string('source')->nullable(); // z. B. „BLS 4.0“
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};

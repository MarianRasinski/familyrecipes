<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zutat ↔ Lebensmittel für die Nährwertberechnung.
     *
     * `grams` = selbst eingetragenes Gewicht der ganzen Zutatenmenge; leer = aus Menge und Einheit schätzen.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('food_id')->nullable()->after('name')->constrained('foods')->nullOnDelete();
            $table->decimal('grams', 8, 1)->nullable()->after('food_id');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('food_id');
            $table->dropColumn('grams');
        });
    }
};

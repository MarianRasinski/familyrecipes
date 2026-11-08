<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Ein Gerätetyp (z. B. „Kettlebell“) mit den im Haushalt vorhandenen Ausführungen („12 kg“, „16 kg“).
 */
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = ['name', 'slug', 'variants', 'is_available'];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_available' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Equipment $equipment) {
            $equipment->slug ??= Str::slug($equipment->name, language: 'de');
        });
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)->using(EquipmentExercise::class)->withPivot('is_optional');
    }
}

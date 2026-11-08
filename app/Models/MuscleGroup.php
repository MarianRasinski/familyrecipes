<?php

namespace App\Models;

use Database\Factories\MuscleGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MuscleGroup extends Model
{
    /** @use HasFactory<MuscleGroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (MuscleGroup $group) {
            $group->slug ??= Str::slug($group->name, language: 'de');
        });
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)->using(ExerciseMuscleGroup::class)->withPivot('is_primary');
    }
}

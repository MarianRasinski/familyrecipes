<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wochenplan: an diesem Wochentag (1 = Montag … 7 = Sonntag) steht diese Einheit an.
 */
class ScheduledWorkout extends Model
{
    /**
     * user_id wird nur über die Beziehung gesetzt.
     *
     * @var list<string>
     */
    protected $fillable = ['weekday', 'workout_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}

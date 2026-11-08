<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Tag, an dem eine Person trainiert – dann gilt das Budget für Trainingstage.
 */
class TrainingDay extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['date'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

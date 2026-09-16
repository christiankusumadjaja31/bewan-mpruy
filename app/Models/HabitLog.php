<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitLog extends Model
{
    protected $fillable = ['habit_id', 'date', 'value', 'completed'];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'completed' => 'boolean',
            'value'     => 'decimal:2',
        ];
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Challenge extends Model
{
    protected $fillable = [
        'creator_id', 'name', 'description', 'habit_name', 'type', 'start_date', 'end_date',
        'target', 'points_per_completion', 'visibility', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'target'     => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'challenge_members')
                    ->withPivot('joined_at', 'habit_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ChallengeLog::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Challenge $challenge) {
            do {
                $code = strtoupper(Str::random(6));
            } while (self::where('join_code', $code)->exists());

            $challenge->join_code = $code;
        });
    }
}
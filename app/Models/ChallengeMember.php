<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeMember extends Model
{
    public $timestamps = false;

    protected $fillable = ['challenge_id', 'user_id', 'habit_id', 'joined_at'];

    public function habit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

}
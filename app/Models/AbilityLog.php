<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbilityLog extends Model
{
    protected $fillable = ['user_id', 'character_id', 'ability_type', 'amount', 'context'];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
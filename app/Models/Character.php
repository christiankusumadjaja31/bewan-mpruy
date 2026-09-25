<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Character extends Model
{
    protected $fillable = ['key', 'name', 'image', 'tier', 'is_default', 'description', 'ability_type', 'ability_name', 'ability_description', 'ability_value', 'price_coins'];

    public function stars(): int
    {
        return $this->tier === 'epic' ? 4 : 3;
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_characters')->withPivot('unlocked_at');
    }
}
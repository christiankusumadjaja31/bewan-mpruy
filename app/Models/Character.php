<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Character extends Model
{
        protected $fillable = ['key', 'name', 'image', 'description', 'ability_type', 'ability_value', 'price_coins'];

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_characters')->withPivot('unlocked_at');
    }
}
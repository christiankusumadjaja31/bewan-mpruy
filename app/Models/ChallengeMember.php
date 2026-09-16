<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeMember extends Model
{
    public $timestamps = false;

    protected $fillable = ['challenge_id', 'user_id', 'joined_at'];
}
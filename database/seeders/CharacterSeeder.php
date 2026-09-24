<?php

namespace Database\Seeders;

use App\Models\Character;
use Illuminate\Database\Seeder;

class CharacterSeeder extends Seeder
{
    public function run(): void
    {
        Character::updateOrCreate(['key' => 'myth'], [
            'name'          => 'My-th',
            'emoji'         => '🐭',
            'description'   => 'A resourceful little mouse who always keeps an extra reserve stashed away. Grants +1 extra Streak Freeze per month, for every habit.',
            'ability_type'  => 'freeze_bonus',
            'ability_value' => 1,
            'price_coins'   => 150,
        ]);

        Character::updateOrCreate(['key' => 'maou'], [
            'name'          => 'Maou',
            'emoji'         => '🐱',
            'description'   => 'A commanding cat with an aura of dominance. Grants +10% bonus points on every challenge check-in.',
            'ability_type'  => 'points_bonus',
            'ability_value' => 0.10,
            'price_coins'   => 300,
        ]);
    }
}
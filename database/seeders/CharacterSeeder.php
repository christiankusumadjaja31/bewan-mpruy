<?php

namespace Database\Seeders;

use App\Models\Character;
use Illuminate\Database\Seeder;

class CharacterSeeder extends Seeder
{
    public function run(): void
    {
        Character::updateOrCreate(['key' => 'myth'], [
            'name'                 => 'My-th',
            'image'                => '/images/characters/myth.png',
            'tier'                 => 'basic',
            'is_default'           => true,
            'description'          => 'A resourceful little mouse who quietly squirrels away a bonus stash whenever a challenge wraps up.',
            'ability_type'         => 'challenge_completion_bonus',
            'ability_name'         => 'Challenge Harvest',
            'ability_description'  => '+5 bonus coins whenever you complete a challenge.',
            'ability_value'        => 5,
            'price_coins'          => 0,
        ]);

        Character::updateOrCreate(['key' => 'mutn'], [
            'name'                 => 'Mutn',
            'image'                => '/images/characters/mutn.png',
            'tier'                 => 'basic',
            'description'          => 'A calm, watchful sheep that helps you rest without ever breaking your rhythm.',
            'ability_type'         => 'freeze_bonus',
            'ability_name'         => 'Safe Pasture',
            'ability_description'  => '+1 extra Streak Freeze per month, for every habit.',
            'ability_value'        => 1,
            'price_coins'          => 150,
        ]);

        Character::updateOrCreate(['key' => 'ende'], [
            'name'                 => 'Ende',
            'image'                => '/images/characters/ende.png',
            'tier'                 => 'basic',
            'description'          => 'A quick, competitive monkey that always finds a way to climb higher on the podium.',
            'ability_type'         => 'podium_bonus_coins',
            'ability_name'         => 'Victory Leap',
            'ability_description'  => '+10 bonus coins whenever you finish in the top 3 of a challenge.',
            'ability_value'        => 10,
            'price_coins'          => 150,
        ]);

        Character::updateOrCreate(['key' => 'maou'], [
            'name'                 => 'Maou',
            'image'                => '/images/characters/maou.png',
            'tier'                 => 'epic',
            'description'          => 'A commanding cat with royal presence, yielding extra treasure with every step.',
            'ability_type'         => 'checkin_coin_bonus',
            'ability_name'         => 'Regal Yield',
            'ability_description'  => '+2 bonus coins on every habit check-in.',
            'ability_value'        => 2,
            'price_coins'          => 300,
        ]);
    }
}
<?php

namespace App\Services;

use App\Models\AbilityLog;
use App\Models\Character;
use App\Models\User;

class AbilityEngine
{
    /**
     * Dipanggil dari Habits::toggleDay() tiap kali habit di-check-in.
     * Ngasih koin dasar + bonus dari karakter kalau ada, sekalian nyatet ke log.
     */
    public static function onCheckin(User $user, string $habitName): int
    {
        $baseCoins = 2;
        $character = $user->equippedCharacter;
        $bonus = 0;

        if ($character && $character->ability_type === 'checkin_coin_bonus') {
            $bonus = (int) $character->ability_value;
        }

        $total = $baseCoins + $bonus;
        $user->increment('coins', $total);

        if ($bonus > 0) {
            self::log($user, $character, 'checkin_coin_bonus', $bonus, "Check-in: {$habitName}");
        }

        return $total;
    }

    /**
     * Dipanggil dari Habits::freezesRemaining() — berapa extra freeze dari karakter.
     */
    public static function freezeBonusFor(User $user): int
    {
        $character = $user->equippedCharacter;

        if ($character && $character->ability_type === 'freeze_bonus') {
            return (int) $character->ability_value;
        }

        return 0;
    }

    /**
     * Belum ada trigger-nya di kode manapun — disiapin buat waktu kita
     * bangun logic "settlement" pas challenge selesai (end_date lewat).
     */
    public static function onChallengeCompletion(User $user, string $challengeName, int $completedDays): int
    {
        $character = $user->equippedCharacter;

        if (! $character || $character->ability_type !== 'challenge_completion_bonus') {
            return 0;
        }

        $amount = (int) $character->ability_value;
        $user->increment('coins', $amount);
        self::log($user, $character, 'challenge_completion_bonus', $amount, "Completed: {$challengeName}");

        return $amount;
    }

    /**
     * Belum ada trigger-nya di kode manapun — disiapin buat waktu kita
     * bangun logic podium settlement pas challenge selesai.
     */
    public static function onPodiumFinish(User $user, string $challengeName, int $rank): int
    {
        $character = $user->equippedCharacter;

        if (! $character || $character->ability_type !== 'podium_bonus_coins' || $rank > 3) {
            return 0;
        }

        $amount = (int) $character->ability_value;
        $user->increment('coins', $amount);
        self::log($user, $character, 'podium_bonus_coins', $amount, "Rank #{$rank}: {$challengeName}");

        return $amount;
    }

    private static function log(User $user, ?Character $character, string $type, int $amount, string $context): void
    {
        AbilityLog::create([
            'user_id'      => $user->id,
            'character_id' => $character?->id,
            'ability_type' => $type,
            'amount'       => $amount,
            'context'      => $context,
        ]);
    }
}
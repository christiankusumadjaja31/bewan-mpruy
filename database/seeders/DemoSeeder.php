<?php

namespace Database\Seeders;

use App\Models\AbilityLog;
use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\ChallengeMember;
use App\Models\Character;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\HabitStreakFreeze;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private array $habitAttrs = [
        'Study 1 Hour'        => ['category' => 'Study',       'target' => 1,  'unit' => 'hours'],
        'Run 30 Minutes'      => ['category' => 'Health',      'target' => 30, 'unit' => 'min'],
        'Meditate 10 Minutes' => ['category' => 'Mindfulness', 'target' => 10, 'unit' => 'min'],
    ];

    public function run(): void
    {
        $this->call(CharacterSeeder::class);

        $main  = $this->makeUser('Raka Pratama', 'demo@versus.test',  480, 'maou', ['maou']);
        $sarah = $this->makeUser('Sarah Wijaya', 'sarah@versus.test', 120, 'ende', ['ende']);
        $dimas = $this->makeUser('Dimas Kurnia', 'dimas@versus.test', 90,  'mutn', ['mutn']);

        // ---------- Challenges ----------
        $study = $this->makeChallenge($sarah, [
            'name'        => '30 Days Study Rush',
            'description' => 'One focused hour of study, every single day, for a month.',
            'habit_name'  => 'Study 1 Hour',
            'start' => -16, 'end' => 13, 'points' => 10, 'status' => 'active',
        ]);

        $run = $this->makeChallenge($main, [
            'name'        => 'Morning Run Club',
            'description' => 'Lace up before breakfast and log 30 minutes of running.',
            'habit_name'  => 'Run 30 Minutes',
            'start' => -10, 'end' => 20, 'points' => 15, 'status' => 'active',
        ]);

        $mindful = $this->makeChallenge($dimas, [
            'name'        => 'Mindful Week',
            'description' => 'Seven days, ten minutes of meditation a day.',
            'habit_name'  => 'Meditate 10 Minutes',
            'start' => -24, 'end' => -18, 'points' => 10, 'status' => 'completed',
        ]);

        // ---------- Keanggotaan + riwayat check-in challenge ----------
        // miss_idx = urutan hari sejak challenge dimulai (0 = hari pertama) yang sengaja dilewatkan
        $this->join($sarah, $study, ['rate' => 100, 'today' => true]);
        $this->join($main,  $study, ['rate' => 100, 'miss_idx' => [5, 12], 'today' => true]);
        $this->join($dimas, $study, ['rate' => 60,  'miss_idx' => [1, 2, 3, 6, 8, 9, 13], 'today' => false]);

        $this->join($main,  $run, ['rate' => 100, 'miss_idx' => [7], 'today' => false]);
        $this->join($dimas, $run, ['rate' => 70,  'miss_idx' => [2, 6], 'today' => false]);
        $this->join($sarah, $run, ['rate' => 55,  'miss_idx' => [1, 3, 5, 7], 'today' => true]);

        $this->join($dimas, $mindful, ['rate' => 100]);
        $this->join($main,  $mindful, ['rate' => 100, 'miss_idx' => [4]]);
        $this->join($sarah, $mindful, ['rate' => 100, 'miss_idx' => [1, 2, 5]]);

        // ---------- Habit tambahan akun utama (di luar jendela challenge) ----------
        // Meditate: kemarin sengaja bolong -> muncul tombol "Missed yesterday? Freeze"
        $this->seedHabit($main, 'Meditate 10 Minutes', $this->habitAttrs['Meditate 10 Minutes'], [
            'since' => 17, 'rate' => 65, 'miss' => [1], 'must' => [2, 3, 4, 5, 6], 'today' => false,
        ]);

        // Read: streak panjang + satu hari diselamatkan freeze
        $read = $this->seedHabit($main, 'Read 20 Pages', ['category' => 'Study', 'target' => 20, 'unit' => 'pages'], [
            'since' => 100, 'rate' => 70, 'miss' => [18],
            'must' => array_values(array_diff(range(1, 24), [18])), 'today' => true,
        ]);

        if (! HabitStreakFreeze::where('habit_id', $read->id)->exists()) {
            HabitStreakFreeze::create(['habit_id' => $read->id, 'date' => today()->subDays(18)->toDateString()]);
        }

        $this->seedHabit($main, 'Drink 2L Water', ['category' => 'Health', 'target' => 2, 'unit' => 'liters'], [
            'since' => 90, 'rate' => 62, 'today' => false,
        ]);

        // Urutan tampil di halaman Habits
        $order = ['Study 1 Hour' => 1, 'Run 30 Minutes' => 2, 'Read 20 Pages' => 3, 'Meditate 10 Minutes' => 4, 'Drink 2L Water' => 5];
        foreach ($order as $name => $minutesAgo) {
            Habit::where('user_id', $main->id)->where('name', $name)->update(['created_at' => now()->subMinutes($minutesAgo)]);
        }

        // Riwayat aktivitas Maou (muncul di Shop -> Recent Activity)
        if (AbilityLog::where('user_id', $main->id)->doesntExist()) {
            $maou = Character::where('key', 'maou')->first();
            foreach (['Study 1 Hour', 'Read 20 Pages', 'Run 30 Minutes', 'Study 1 Hour', 'Read 20 Pages'] as $i => $habitName) {
                $entry = new AbilityLog([
                    'user_id'      => $main->id,
                    'character_id' => $maou?->id,
                    'ability_type' => 'checkin_coin_bonus',
                    'amount'       => 2,
                    'context'      => "Check-in: {$habitName}",
                ]);
                $entry->forceFill([
                    'created_at' => now()->subHours(($i + 1) * 5),
                    'updated_at' => now()->subHours(($i + 1) * 5),
                ])->save();
            }
        }

        // ---------- Ringkasan ----------
        $this->command->info('Demo accounts ready (password: "password")');
        foreach ([$main, $sarah, $dimas] as $u) {
            $this->command->line("  {$u->email}");
        }
        foreach ([$study, $run, $mindful] as $c) {
            $rows = ChallengeLog::where('challenge_id', $c->id)
                ->selectRaw('user_id, SUM(points) as pts')->groupBy('user_id')->orderByDesc('pts')->get();
            $line = $rows->map(fn ($r) => User::find($r->user_id)->name . ' ' . $r->pts)->implode(' > ');
            $this->command->line("  {$c->name}: {$line}");
        }
    }

    private function makeUser(string $name, string $email, int $coins, string $equip, array $unlock): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name'              => $name,
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
        ])->save();

        $default = Character::where('is_default', true)->first();
        $keys = array_unique(array_merge($default ? [$default->key] : [], $unlock));

        foreach (Character::whereIn('key', $keys)->get() as $character) {
            $user->characters()->syncWithoutDetaching([$character->id => ['unlocked_at' => now()]]);
        }

        $user->forceFill([
            'coins'                 => $coins,
            'equipped_character_id' => Character::where('key', $equip)->value('id'),
        ])->save();

        return $user;
    }

    private function makeChallenge(User $creator, array $c): Challenge
    {
        return Challenge::firstOrCreate(
            ['creator_id' => $creator->id, 'name' => $c['name']],
            [
                'description'           => $c['description'],
                'habit_name'            => $c['habit_name'],
                'type'                  => 'point',
                'start_date'            => today()->addDays($c['start']),
                'end_date'              => today()->addDays($c['end']),
                'target'                => 1,
                'points_per_completion' => $c['points'],
                'visibility'            => 'public',
                'status'                => $c['status'],
                'settled_at'            => $c['status'] === 'completed' ? today()->addDays($c['end'] + 1) : null,
            ]
        );
    }

    private function join(User $user, Challenge $challenge, array $spec): Habit
    {
        $start = $challenge->start_date->copy()->startOfDay();

        $habit = Habit::firstOrCreate(
            ['user_id' => $user->id, 'name' => $challenge->habit_name],
            array_merge($this->habitAttrs[$challenge->habit_name] ?? ['target' => 1], [
                'frequency'  => 'daily',
                'start_date' => $start,
                'is_active'  => true,
            ])
        );

        ChallengeMember::firstOrCreate(
            ['challenge_id' => $challenge->id, 'user_id' => $user->id],
            ['habit_id' => $habit->id, 'joined_at' => $start]
        );

        $last = $challenge->end_date->lt(today()) ? $challenge->end_date->copy() : today();

        for ($day = $start->copy(); $day->lte($last); $day->addDay()) {
            $idx = (int) $start->diffInDays($day);

            $done = $day->isToday()
                ? (bool) ($spec['today'] ?? false)
                : (! in_array($idx, $spec['miss_idx'] ?? [], true)
                    && $this->roll($user->id . $habit->name, $day, $spec['rate'] ?? 100));

            if ($done) {
                $this->logDay($habit, $day, $challenge, $user);
            }
        }

        return $habit;
    }

    private function seedHabit(User $user, string $name, array $attrs, array $spec): Habit
    {
        $habit = Habit::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            array_merge($attrs, [
                'frequency'  => 'daily',
                'start_date' => today()->subDays($spec['since']),
                'is_active'  => true,
            ])
        );

        // Hari yang masuk jendela challenge sudah ditangani join(), jadi dilewati di sini
        $windows = ChallengeMember::where('habit_id', $habit->id)->with('challenge')->get()
            ->map(fn ($m) => $m->challenge)->filter();

        for ($o = $spec['since']; $o >= 0; $o--) {
            $day = today()->subDays($o);

            $inWindow = $windows->contains(
                fn ($c) => $day->between($c->start_date->copy()->startOfDay(), $c->end_date->copy()->endOfDay())
            );
            if ($inWindow) {
                continue;
            }

            if ($o === 0) {
                $done = (bool) ($spec['today'] ?? false);
            } elseif (in_array($o, $spec['miss'] ?? [], true)) {
                $done = false;
            } elseif (in_array($o, $spec['must'] ?? [], true)) {
                $done = true;
            } else {
                $done = $this->roll($user->id . $name, $day, $spec['rate'] ?? 70);
            }

            if ($done) {
                $this->logDay($habit, $day);
            }
        }

        return $habit;
    }

    private function logDay(Habit $habit, Carbon $day, ?Challenge $challenge = null, ?User $user = null): void
    {
        $date = $day->toDateString();

        $log = HabitLog::where('habit_id', $habit->id)->whereDate('date', $date)->first();

        if (! $log) {
            $log = new HabitLog([
                'habit_id'  => $habit->id,
                'date'      => $date,
                'completed' => true,
                'value'     => $habit->target,
            ]);
            $log->forceFill(['coin_awarded_at' => $day->copy()->setTime(20, 0)])->save();
        } elseif (! $log->completed) {
            $log->forceFill(['completed' => true, 'value' => $habit->target])->save();
        }

        if ($challenge && $user) {
            $exists = ChallengeLog::where('challenge_id', $challenge->id)
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->exists();

            if (! $exists) {
                ChallengeLog::create([
                    'challenge_id' => $challenge->id,
                    'user_id'      => $user->id,
                    'habit_log_id' => $log->id,
                    'points'       => $challenge->points_per_completion,
                    'date'         => $date,
                ]);
            }
        }
    }

    // Acak yang stabil: hasil sama untuk kunci + tanggal yang sama di setiap run
    private function roll(string $key, Carbon $day, int $rate): bool
    {
        return (crc32($key . '|' . $day->toDateString()) % 100) < $rate;
    }
}
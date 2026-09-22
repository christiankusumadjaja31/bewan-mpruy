<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Habit;
use App\Models\Challenge;
use App\Models\ChallengeMember;
use App\Models\HabitLog;
use App\Models\ChallengeLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CLEAN SLATE: Menghapus semua data testing lama
        DB::table('challenge_logs')->delete();
        DB::table('habit_logs')->delete();
        DB::table('habit_streak_freezes')->delete();
        DB::table('challenge_members')->delete();
        DB::table('challenges')->delete();
        DB::table('habits')->delete();

        // Mengambil user pertama di database (akun yang kamu gunakan untuk login)
        $user = User::first();

        if (!$user) {
            $this->command->warn('No user found! Please register a user first.');
            return;
        }

        $today = Carbon::today();

        // ==========================================
        // DUMMY 1: Hadoop Ecosystem (Public)
        // ==========================================
        $challenge1 = Challenge::create([
            'creator_id'            => $user->id,
            'name'                  => '30 Days Hadoop Ecosystem',
            'description'           => 'Mastering Big Data architecture layers, HDFS, MapReduce, and Apache Spark.',
            'habit_name'            => 'Study Big Data concepts',
            'type'                  => 'point',
            'start_date'            => $today->copy()->subDays(5)->toDateString(), // Mulai 5 hari yang lalu
            'end_date'              => $today->copy()->addDays(25)->toDateString(),
            'target'                => 1,
            'points_per_completion' => 15,
            'visibility'            => 'public',
            'status'                => 'active',
            'join_code'             => strtoupper(Str::random(6)),
        ]);

        $habit1 = Habit::create([
            'user_id'    => $user->id,
            'name'       => $challenge1->habit_name,
            'category'   => 'Study',
            'frequency'  => 'daily',
            'target'     => 1,
            'unit'       => 'hour',
            'start_date' => $challenge1->start_date,
        ]);

        ChallengeMember::create([
            'challenge_id' => $challenge1->id,
            'user_id'      => $user->id,
            'habit_id'     => $habit1->id,
            'joined_at'    => $today->copy()->subDays(5),
        ]);

        // ==========================================
        // DUMMY 2: Math Tutor Prep (Private)
        // ==========================================
        $challenge2 = Challenge::create([
            'creator_id'            => $user->id,
            'name'                  => 'Math Tutor Module Prep',
            'description'           => 'Consistent preparation for 9th and 10th-grade mathematics tutoring (Logarithms & Trigonometry).',
            'habit_name'            => 'Create math exercises',
            'type'                  => 'point',
            'start_date'            => $today->copy()->subDays(2)->toDateString(), // Mulai 2 hari lalu
            'end_date'              => $today->copy()->addDays(12)->toDateString(),
            'target'                => 1,
            'points_per_completion' => 10,
            'visibility'            => 'private',
            'status'                => 'active',
            'join_code'             => strtoupper(Str::random(6)),
        ]);

        $habit2 = Habit::create([
            'user_id'    => $user->id,
            'name'       => $challenge2->habit_name,
            'category'   => 'Work',
            'frequency'  => 'daily',
            'target'     => 1,
            'unit'       => 'module',
            'start_date' => $challenge2->start_date,
        ]);

        ChallengeMember::create([
            'challenge_id' => $challenge2->id,
            'user_id'      => $user->id,
            'habit_id'     => $habit2->id,
            'joined_at'    => $today->copy()->subDays(2),
        ]);

        // ==========================================
        // DUMMY 3: NLP Model Evaluation (Public)
        // ==========================================
        $challenge3 = Challenge::create([
            'creator_id'            => $user->id,
            'name'                  => 'NLP Model Faithfulness',
            'description'           => 'Evaluating Llama-3 self-explanations on Indonesian slang text sentiment analysis.',
            'habit_name'            => 'Review model datasets',
            'type'                  => 'point',
            'start_date'            => $today->copy()->toDateString(), // Mulai hari ini
            'end_date'              => $today->copy()->addDays(21)->toDateString(),
            'target'                => 1,
            'points_per_completion' => 20,
            'visibility'            => 'public',
            'status'                => 'active',
            'join_code'             => strtoupper(Str::random(6)),
        ]);

        $habit3 = Habit::create([
            'user_id'    => $user->id,
            'name'       => $challenge3->habit_name,
            'category'   => 'Research',
            'frequency'  => 'daily',
            'target'     => 1,
            'unit'       => 'dataset',
            'start_date' => $challenge3->start_date,
        ]);

        ChallengeMember::create([
            'challenge_id' => $challenge3->id,
            'user_id'      => $user->id,
            'habit_id'     => $habit3->id,
            'joined_at'    => $today->copy(),
        ]);

        // ==========================================
        // Menambahkan Sejarah Check-in (Agar tampilan tidak kosong)
        // ==========================================
        
        // Simulasi check-in 3 hari berturut-turut untuk Challenge 1
        for ($i = 3; $i >= 1; $i--) {
            $logDate = $today->copy()->subDays($i);
            $hLog = HabitLog::create([
                'habit_id'  => $habit1->id,
                'date'      => $logDate->toDateString(),
                'completed' => true,
                'value'     => $habit1->target,
            ]);
            ChallengeLog::create([
                'challenge_id' => $challenge1->id,
                'user_id'      => $user->id,
                'date'         => $logDate->toDateString(),
                'habit_log_id' => $hLog->id,
                'points'       => $challenge1->points_per_completion,
            ]);
        }

        // Simulasi check-in kemarin untuk Challenge 2
        $hLog2 = HabitLog::create([
            'habit_id'  => $habit2->id,
            'date'      => $today->copy()->subDay()->toDateString(),
            'completed' => true,
            'value'     => $habit2->target,
        ]);
        ChallengeLog::create([
            'challenge_id' => $challenge2->id,
            'user_id'      => $user->id,
            'date'         => $today->copy()->subDay()->toDateString(),
            'habit_log_id' => $hLog2->id,
            'points'       => $challenge2->points_per_completion,
        ]);

        $this->command->info('Clean slate done! 3 dummy challenges successfully generated.');
    }
}
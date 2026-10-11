<?php

use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\ChallengeMember;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\HabitStreakFreeze;
use App\Services\AbilityEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public ?int $selectedChallengeId = null;

    public function greeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return 'Good morning';
        if ($hour < 17) return 'Good afternoon';
        if ($hour < 21) return 'Good evening';
        return 'Ready for the night';
    }

    public function equippedCharacter()
    {
        return Auth::user()->equippedCharacter;
    }

    public function activeChallenges()
    {
        return ChallengeMember::where('user_id', Auth::id())
            ->whereHas('challenge', fn ($q) => $q->where('status', 'active')->whereDate('end_date', '>=', today()))
            ->with('challenge')
            ->get();
    }

    #[Computed]
    public function todaySummary(): array
    {
        $habits = Auth::user()->habits()
            ->with(['logs' => fn ($q) => $q->whereDate('date', today())])
            ->get();

        $done = $habits->filter(fn ($h) => (bool) ($h->logs->first()?->completed ?? false))->count();

        return ['done' => $done, 'total' => $habits->count()];
    }

    #[Computed]
    public function bestStreak(): int
    {
        return Auth::user()->habits()->get()
            ->map(fn ($h) => $this->habitStreak($h))
            ->max() ?? 0;
    }

    #[Computed]
    public function stats(): array
    {
        $habits = Auth::user()->habits()->get();
        $habitIds = $habits->pluck('id')->all();

        $totalCompletions = HabitLog::whereIn('habit_id', $habitIds)
            ->where('completed', true)
            ->count();

        $baseFreezeLimit = 3 + AbilityEngine::freezeBonusFor(Auth::user());

        $freezesRemaining = $habits->sum(function ($habit) use ($baseFreezeLimit) {
            $used = HabitStreakFreeze::where('habit_id', $habit->id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count();
            return max(0, $baseFreezeLimit - $used);
        });

        return [
            'total_completions' => $totalCompletions,
            'freezes_remaining' => $freezesRemaining,
        ];
    }

    #[Computed]
    public function pointsChart(): array
    {
        $start = today()->subDays(13);

        // Ambil barisnya lalu kelompokkan lewat Carbon — jangan lewat string SQL,
        // karena kolom date tersimpan dengan jam (00:00:00).
        $byDay = ChallengeLog::where('user_id', Auth::id())
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', today()->toDateString())
            ->get(['date', 'points'])
            ->groupBy(fn ($log) => $log->date->toDateString())
            ->map(fn ($group) => (int) $group->sum('points'));

        $values = [];
        for ($i = 0; $i < 14; $i++) {
            $values[] = (int) ($byDay[$start->copy()->addDays($i)->toDateString()] ?? 0);
        }

        $max = max(1, max($values));
        $coords = [];
        foreach ($values as $i => $v) {
            $coords[] = [
                'x' => round(2 + $i * (96 / 13), 2),
                'y' => round(92 - ($v / $max) * 84, 2),
            ];
        }

        $line  = collect($coords)->map(fn ($c) => "{$c['x']},{$c['y']}")->implode(' ');
        $first = $coords[0];
        $last  = end($coords);

        return [
            'polyline'    => $line,
            'area'        => $line . " {$last['x']},100 {$first['x']},100",
            'last'        => $last,
            'total'       => array_sum($values),
            'has_data'    => array_sum($values) > 0,
            'start_label' => $start->format('d M'),
        ];
    }

    #[Computed]
    public function activityHeatmap(): array
    {
        $habitIds = Auth::user()->habits()->pluck('id');
        $total = max(1, $habitIds->count());

        $start = today()->startOfWeek(Carbon::MONDAY)->subWeeks(15); // 16 minggu, mulai Senin

        $counts = HabitLog::whereIn('habit_id', $habitIds)
            ->where('completed', true)
            ->whereDate('date', '>=', $start->toDateString())
            ->get(['habit_id', 'date'])
            ->groupBy(fn ($log) => $log->date->toDateString())
            ->map(fn ($group) => $group->count());

        $cells = [];
        $activeDays = 0;

        for ($i = 0; $i < 112; $i++) {
            $date = $start->copy()->addDays($i);

            if ($date->gt(today())) {
                $cells[] = ['future' => true];
                continue;
            }

            $done  = (int) ($counts[$date->toDateString()] ?? 0);
            $ratio = $done / $total;
            $level = $done === 0 ? 0 : ($ratio <= 0.34 ? 1 : ($ratio <= 0.67 ? 2 : ($ratio < 1 ? 3 : 4)));

            if ($done > 0) $activeDays++;

            $cells[] = [
                'future'  => false,
                'date'    => $date->format('d M'),
                'done'    => $done,
                'level'   => $level,
                'isToday' => $date->isToday(),
            ];
        }

        return ['cells' => $cells, 'active_days' => $activeDays];
    }

    public function habitStreak(Habit $habit): int
    {
        $completedDates = $habit->logs()->where('completed', true)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();
        $frozenDates = HabitStreakFreeze::where('habit_id', $habit->id)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();
        $counted = array_unique(array_merge($completedDates, $frozenDates));

        $cursor = today();
        if (! in_array($cursor->toDateString(), $counted)) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while (in_array($cursor->toDateString(), $counted)) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    public function challengeStats(Challenge $challenge): array
    {
        $ranked = ChallengeLog::where('challenge_id', $challenge->id)
            ->selectRaw('user_id, SUM(points) as total_points')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->pluck('user_id')
            ->values();

        $myRank = $ranked->search(Auth::id());
        $myPoints = ChallengeLog::where('challenge_id', $challenge->id)->where('user_id', Auth::id())->sum('points');

        return [
            'members_count' => $challenge->members()->count(),
            'my_points'     => (int) $myPoints,
            'my_rank'       => $myRank === false ? null : $myRank + 1,
        ];
    }

    public function selectChallenge(int $id): void
    {
        $this->selectedChallengeId = $this->selectedChallengeId === $id ? null : $id;
    }

    public function closePanel(): void
    {
        $this->selectedChallengeId = null;
    }
};

?>

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- ============================== --}}
    {{-- SISI KIRI (2/3) --}}
    {{-- ============================== --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 lg:mb-8">
            <div>
                <h1 class="font-heading text-2xl font-bold text-zinc-100">Dashboard</h1>
                <p class="text-sm text-zinc-500 mt-0.5">{{ $this->greeting() }}, {{ explode(' ', Auth::user()->name)[0] }}</p>
            </div>

            <div class="flex items-center gap-2">
                @php $char = $this->equippedCharacter(); @endphp
                @if ($char)
                    <div class="hidden sm:flex items-center gap-2 px-2.5 py-1.5 bg-zinc-900 border border-zinc-800 rounded-lg">
                        <div class="w-6 h-6 rounded-full overflow-hidden shrink-0 {{ $char->tier === 'epic' ? 'ring-2 ring-purple-500/60' : '' }}">
                            <img src="{{ $char->image }}" alt="{{ $char->name }}" class="block w-full h-full object-cover object-center">
                        </div>
                        <span class="text-xs text-zinc-300 font-medium">{{ $char->name }}</span>
                    </div>
                @endif

                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                    <span class="text-sm">🪙</span>
                    <span class="font-heading font-bold text-amber-400 text-sm">{{ Auth::user()->coins ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        @php $today = $this->todaySummary; @endphp
        <div class="grid grid-cols-3 gap-3 mb-4 lg:mb-6">
            <a href="/habits" wire:navigate class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-3 lg:p-4 text-center transition hover:bg-zinc-800/60">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 lg:w-6 lg:h-6 text-emerald-500 mx-auto mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xl lg:text-2xl font-heading font-bold {{ $today['total'] > 0 && $today['done'] === $today['total'] ? 'text-emerald-400' : 'text-zinc-100' }}">{{ $today['done'] }}/{{ $today['total'] }}</p>
                <p class="text-[10px] lg:text-xs text-zinc-500 mt-0.5">Done today</p>
            </a>

            <div class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-3 lg:p-4 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 lg:w-6 lg:h-6 text-orange-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"></path>
                </svg>
                <p class="text-xl lg:text-2xl font-heading font-bold text-zinc-100">{{ $this->bestStreak }}</p>
                <p class="text-[10px] lg:text-xs text-zinc-500 mt-0.5">Best streak</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-3 lg:p-4 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 lg:w-6 lg:h-6 text-blue-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"></path>
                </svg>
                <p class="text-xl lg:text-2xl font-heading font-bold text-zinc-100">{{ $this->stats['total_completions'] }}</p>
                <p class="text-[10px] lg:text-xs text-zinc-500 mt-0.5">Check-ins</p>
            </div>
        </div>

        {{-- Charts --}}
        @php
            $pc = $this->pointsChart;
            $hm = $this->activityHeatmap;
            $levels = ['bg-zinc-800', 'bg-emerald-900', 'bg-emerald-700', 'bg-emerald-500', 'bg-emerald-400'];
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 lg:mb-8">

            {{-- Points trend --}}
            <div class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Points — 14 days</p>
                    <p class="text-sm font-heading font-bold text-emerald-400">{{ $pc['total'] }} pts</p>
                </div>

                @if ($pc['has_data'])
                    <div class="relative flex-1 min-h-20">
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 w-full h-full overflow-visible">
                            <polygon points="{{ $pc['area'] }}" fill="rgba(16,185,129,0.12)" />
                            <polyline points="{{ $pc['polyline'] }}" fill="none" stroke="#10b981" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="absolute w-2.5 h-2.5 rounded-full bg-emerald-400 -translate-x-1/2 -translate-y-1/2"
                              style="left: {{ $pc['last']['x'] }}%; top: {{ $pc['last']['y'] }}%"></span>
                    </div>
                    <div class="flex justify-between text-[10px] text-zinc-600 mt-2">
                        <span>{{ $pc['start_label'] }}</span>
                        <span>Today</span>
                    </div>
                @else
                    <div class="flex-1 min-h-20 flex items-center justify-center">
                        <p class="text-sm text-zinc-600 text-center">Check in a habit linked to a challenge to see your trend here.</p>
                    </div>
                @endif
            </div>

            {{-- Consistency heatmap --}}
            <div class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Consistency — 16 weeks</p>
                    <p class="text-[11px] text-zinc-500">{{ $hm['active_days'] }} active days</p>
                </div>

                <div class="grid grid-flow-col grid-rows-7 gap-[3px]" style="grid-template-columns: repeat(16, minmax(0, 1fr));">
                    @foreach ($hm['cells'] as $cell)
                        @if ($cell['future'])
                            <div wire:key="hm-{{ $loop->index }}" class="aspect-square"></div>
                        @else
                            <div wire:key="hm-{{ $loop->index }}"
                                 title="{{ $cell['date'] }} — {{ $cell['done'] }} done"
                                 class="aspect-square rounded-sm {{ $levels[$cell['level']] }} {{ $cell['isToday'] ? 'ring-1 ring-emerald-300' : '' }}"></div>
                        @endif
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-1 mt-3 text-[10px] text-zinc-600">
                    <span>Less</span>
                    @foreach ($levels as $l)
                        <span class="w-2.5 h-2.5 rounded-sm {{ $l }}"></span>
                    @endforeach
                    <span>More</span>
                </div>
            </div>
        </div>

        {{-- Active Challenges --}}
        <div class="mb-6 lg:mb-0 lg:pb-20">
            <h2 class="font-heading text-lg font-bold text-zinc-100 mb-4">Active Challenges</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($this->activeChallenges() as $member)
                    @php $challenge = $member->challenge; $cs = $this->challengeStats($challenge); @endphp
                    <button wire:key="challenge-{{ $challenge->id }}"
                            wire:click="selectChallenge({{ $challenge->id }})"
                            class="text-left bg-zinc-900 rounded-xl py-4 px-4 transition
                                   {{ $selectedChallengeId === $challenge->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 font-heading font-bold text-sm
                                        {{ ['bg-emerald-300 text-emerald-900', 'bg-blue-300 text-blue-900', 'bg-purple-300 text-purple-900', 'bg-rose-300 text-rose-900'][$challenge->id % 4] }}">
                                {{ strtoupper(mb_substr($challenge->name, 0, 1)) }}
                            </div>
                            <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[9px] font-semibold rounded-full uppercase tracking-wider border border-emerald-500/20">Active</span>
                        </div>
                        <p class="font-heading font-medium text-zinc-100 truncate mb-1">{{ $challenge->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $challenge->points_per_completion }} pts/check-in &middot; Ends {{ $challenge->end_date->format('d M') }}</p>
                        <p class="text-xs mt-2 font-medium {{ $cs['my_rank'] ? 'text-emerald-400' : 'text-zinc-600' }}">
                            {{ $cs['my_rank'] ? "Rank #{$cs['my_rank']} · {$cs['my_points']} pts" : 'Not ranked yet' }}
                        </p>
                    </button>
                @empty
                    <div class="col-span-full border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm">You're not in any challenges right now.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Overview: inline di mobile saja (di desktop sudah jadi panel kanan) --}}
        <div class="lg:hidden pb-32">
            @include('pages.dashboard.partials.overview', ['inline' => true])
        </div>
    </div>

    {{-- ============================== --}}
    {{-- SISI KANAN (1/3) --}}
    {{-- ============================== --}}
    <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
        <div class="w-full">
            @include('pages.dashboard.partials.panel-content')
        </div>
    </div>

    @if ($selectedChallengeId)
        <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
            @include('pages.dashboard.partials.panel-content')
        </div>
    @endif

</div>
<?php

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\ChallengeMember;
use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\HabitStreakFreeze;
use App\Services\AbilityEngine;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Flux\Flux;

new #[Title('Dashboard')] class extends Component {
    public ?int $selectedHabitId = null;
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

    public function todayHabits()
    {
        return Auth::user()->habits()
            ->with(['logs' => fn ($q) => $q->whereDate('date', today())])
            ->get();
    }

    public function activeChallenges()
    {
        return ChallengeMember::where('user_id', Auth::id())
            ->whereHas('challenge', fn ($q) => $q->where('status', 'active')->whereDate('end_date', '>=', today()))
            ->with('challenge')
            ->get();
    }

    public function isCompletedToday(Habit $habit): bool
    {
        return (bool) ($habit->logs->first()?->completed ?? false);
    }

    public function toggleToday(int $habitId): void
    {
        $habit = Auth::user()->habits()->findOrFail($habitId);
        $date = today()->toDateString();

        $log = HabitLog::where('habit_id', $habit->id)->whereDate('date', $date)->first();

        if ($log) {
            $log->completed = ! $log->completed;
            $log->value = $log->completed ? $habit->target : 0;
            $log->save();
        } else {
            $log = HabitLog::create([
                'habit_id'  => $habit->id,
                'date'      => $date,
                'completed' => true,
                'value'     => $habit->target,
            ]);
        }

        if ($log->completed && ! $log->coin_awarded_at) {
            $awarded = AbilityEngine::onCheckin(Auth::user(), $habit->name);
            $log->coin_awarded_at = now();
            $log->save();
            Flux::toast(text: "+{$awarded} Coins earned!", variant: 'success');
        }

        $this->syncChallengePoints($habit, $log);
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

        $totalFreezesRemaining = $habits->sum(function ($habit) use ($baseFreezeLimit) {
            $used = HabitStreakFreeze::where('habit_id', $habit->id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count();
            return max(0, $baseFreezeLimit - $used);
        });

        return [
            'total_completions' => $totalCompletions,
            'freezes_remaining' => $totalFreezesRemaining,
        ];
    }

    #[Computed]
    public function pointsChart(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => today()->subDays($i)->toDateString());

        $raw = ChallengeLog::where('user_id', Auth::id())
            ->whereBetween('date', [today()->subDays(13)->toDateString(), today()->toDateString()])
            ->selectRaw('date, SUM(points) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $values = $days->map(fn ($d) => (int) ($raw[$d] ?? 0))->toArray();

        $width = 280;
        $height = 60;
        $padding = 6;
        $max = max(1, max($values));
        $count = count($values);
        $stepX = $count > 1 ? ($width - $padding * 2) / ($count - 1) : 0;

        $coords = [];
        foreach ($values as $i => $v) {
            $x = round($padding + $i * $stepX, 1);
            $y = round($height - $padding - (($v / $max) * ($height - $padding * 2)), 1);
            $coords[] = compact('x', 'y');
        }

        return [
            'polyline' => collect($coords)->map(fn ($c) => "{$c['x']},{$c['y']}")->implode(' '),
            'last'     => end($coords),
            'total'    => array_sum($values),
            'has_data' => array_sum($values) > 0,
        ];
    }

    private function syncChallengePoints(Habit $habit, HabitLog $log): void
    {
        $logDate = $log->date instanceof \Carbon\Carbon ? $log->date->toDateString() : $log->date;

        $memberships = ChallengeMember::where('user_id', Auth::id())
            ->where('habit_id', $habit->id)
            ->get();

        foreach ($memberships as $membership) {
            $challenge = Challenge::find($membership->challenge_id);
            if (! $challenge || $challenge->status !== 'active') continue;

            $existing = ChallengeLog::where('challenge_id', $challenge->id)
                ->where('user_id', Auth::id())
                ->whereDate('date', $logDate)
                ->first();

            if ($log->completed) {
                if ($existing) {
                    $existing->update([
                        'habit_log_id' => $log->id,
                        'points'       => $challenge->points_per_completion,
                    ]);
                } else {
                    ChallengeLog::create([
                        'challenge_id' => $challenge->id,
                        'user_id'      => Auth::id(),
                        'habit_log_id' => $log->id,
                        'points'       => $challenge->points_per_completion,
                        'date'         => $logDate,
                    ]);
                }
            } elseif ($existing) {
                $existing->delete();
            }
        }
    }

    public function habitStreak(Habit $habit): int
    {
        $completedDates = $habit->logs()->where('completed', true)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();
        $frozenDates = HabitStreakFreeze::where('habit_id', $habit->id)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();
        $counted = array_unique(array_merge($completedDates, $frozenDates));

        $cursor = today();
        if (! in_array($cursor->toDateString(), $counted)) $cursor = $cursor->subDay();

        $streak = 0;
        while (in_array($cursor->toDateString(), $counted)) {
            $streak++;
            $cursor = $cursor->subDay();
        }
        return $streak;
    }

    public function habitTotalCompletions(Habit $habit): int
    {
        return $habit->logs()->where('completed', true)->count();
    }

    public function habitHeatmap(Habit $habit): array
    {
        $start = today()->subDays(69)->toDateString();

        $completed = HabitLog::where('habit_id', $habit->id)->where('completed', true)
            ->whereDate('date', '>=', $start)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();

        $frozen = HabitStreakFreeze::where('habit_id', $habit->id)
            ->whereDate('date', '>=', $start)->pluck('date')->map(fn ($d) => $d->toDateString())->toArray();

        return collect(range(69, 0))->map(function ($i) use ($completed, $frozen) {
            $date = today()->subDays($i)->toDateString();
            return [
                'completed' => in_array($date, $completed),
                'frozen'    => in_array($date, $frozen),
            ];
        })->toArray();
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

    public function selectHabit(int $id): void
    {
        $this->selectedChallengeId = null;
        $this->selectedHabitId = $this->selectedHabitId === $id ? null : $id;
    }

    public function selectChallenge(int $id): void
    {
        $this->selectedHabitId = null;
        $this->selectedChallengeId = $this->selectedChallengeId === $id ? null : $id;
    }

    public function closePanel(): void
    {
        $this->selectedHabitId = null;
        $this->selectedChallengeId = null;
    }
};

?>

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- SISI KIRI (2/3) --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

        {{-- Header — samain pola judul kayak Habit/Challenge/Shop --}}
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

        {{-- Points Trend --}}
        <div class="bg-zinc-900 border border-zinc-800/80 rounded-xl p-5 mb-6 lg:mb-8">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Points — Last 14 Days</p>
                <p class="text-sm font-heading font-bold text-emerald-400">{{ $this->pointsChart['total'] }} pts</p>
            </div>
            @if ($this->pointsChart['has_data'])
                <svg viewBox="0 0 280 60" class="w-full h-14">
                    <polyline points="{{ $this->pointsChart['polyline'] }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="{{ $this->pointsChart['last']['x'] }}" cy="{{ $this->pointsChart['last']['y'] }}" r="3.5" fill="#10b981" />
                </svg>
            @else
                <p class="text-sm text-zinc-600 text-center py-4">No challenge points yet in the last 14 days.</p>
            @endif
        </div>

        {{-- Today's Focus --}}
        <div class="mb-6 lg:mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-heading text-lg font-bold text-zinc-100">Today's Focus</h2>
                <p class="text-xs text-zinc-500 font-medium">{{ today()->format('l, d M') }}</p>
            </div>

            <div class="space-y-2">
                @forelse ($this->todayHabits() as $habit)
                    @php $done = $this->isCompletedToday($habit); @endphp
                    <div wire:key="focus-{{ $habit->id }}"
                         class="flex items-center bg-zinc-900 rounded-xl py-3 px-3 transition group
                                {{ $selectedHabitId === $habit->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">

                        <button wire:click="toggleToday({{ $habit->id }})"
                                class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center transition-all mr-3
                                       {{ $done ? 'bg-emerald-500 text-white shadow-[0_0_10px_rgba(16,185,129,0.3)]' : 'bg-zinc-700/60 hover:bg-zinc-600' }}">
                            @if ($done)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </button>

                        <button wire:click="selectHabit({{ $habit->id }})" class="flex-1 text-left min-w-0">
                            <p class="font-heading font-medium text-[15px] truncate {{ $done ? 'text-zinc-500' : 'text-zinc-100 group-hover:text-emerald-400 transition' }}">
                                {{ $habit->name }}
                            </p>
                            <p class="text-[11px] text-zinc-500 mt-0.5 truncate">{{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }} {{ $habit->category ? '• ' . $habit->category : '' }}</p>
                        </button>
                    </div>
                @empty
                    <div class="border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm">No habits yet — head to Habits to add one.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Challenges --}}
        <div class="pb-32 lg:pb-20">
            <h2 class="font-heading text-lg font-bold text-zinc-100 mb-4">Active Challenges</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($this->activeChallenges() as $member)
                    @php $challenge = $member->challenge; @endphp
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
                        <p class="text-xs text-zinc-500">
                            {{ $challenge->points_per_completion }} pts/check-in &middot; Ends {{ $challenge->end_date->format('d M') }}
                        </p>
                    </button>
                @empty
                    <div class="col-span-full border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm">You're not in any challenges right now.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- SISI KANAN (1/3) --}}
    <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
        <div class="w-full">
            @include('pages.dashboard.partials.panel-content')
        </div>
    </div>

    @if ($selectedHabitId || $selectedChallengeId)
        <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
            @include('pages.dashboard.partials.panel-content')
        </div>
    @endif

</div>
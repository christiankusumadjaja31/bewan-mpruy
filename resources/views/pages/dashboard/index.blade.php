<?php

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\ChallengeMember;
use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\HabitStreakFreeze;
use Carbon\Carbon;
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
        // logs sudah difilter hanya hari ini di todayHabits()
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
            $coinsToAward = 2;
            $char = $this->equippedCharacter();
            if ($char && $char->ability_type === 'checkin_coin_bonus') {
                $coinsToAward += $char->ability_value;
            }
            Auth::user()->increment('coins', $coinsToAward);
            $log->coin_awarded_at = now();
            $log->save();

            Flux::toast(text: "+{$coinsToAward} Coins earned!", variant: 'success');
        }

        $this->syncChallengePoints($habit, $log);
    }

    #[Computed]
    public function stats(): array
    {
        $habitIds = $this->selectedHabitId
            ? [$this->selectedHabitId]
            : Auth::user()->habits()->pluck('id')->all();

        $totalCompletions = HabitLog::whereIn('habit_id', $habitIds)
            ->where('completed', true)
            ->count();

        // SESUAIKAN: salin logika freezes_remaining dari habits/⚡index.blade.php
        // (nama kolom user_id / habit_id di HabitStreakFreeze belum dipastikan)
        $freezeLimit = 2;
        $freezesUsed = HabitStreakFreeze::where('user_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total_completions' => $totalCompletions,
            'freezes_remaining' => max(0, $freezeLimit - $freezesUsed),
        ];
    }

    private function syncChallengePoints(Habit $habit, HabitLog $log): void
    {
        $logDate = Carbon::parse($log->date)->toDateString();

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

{{-- Root tunggal. Jangan bungkus dengan <x-layouts::app>, Livewire sudah memasang layout. --}}
<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- SISI KIRI (2/3 LAYAR) - MAIN DASHBOARD --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

        {{-- 1. HERO SECTION --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-zinc-900 border border-zinc-800/80 rounded-2xl p-6 lg:p-8 mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>

            <div class="relative z-10">
                <p class="text-sm font-medium text-emerald-500 mb-1 tracking-wide">{{ $this->greeting() }},</p>
                <h1 class="font-heading text-3xl lg:text-4xl font-bold text-zinc-100 mb-4">
                    {{ explode(' ', Auth::user()->name)[0] }}
                </h1>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/10 border border-amber-500/20 rounded-full">
                        <span class="text-lg">🪙</span>
                        <span class="font-bold font-heading text-amber-500 tracking-wide">{{ Auth::user()->coins ?? 0 }}</span>
                    </div>
                </div>
            </div>

            {{-- Showcase Karakter --}}
            @php $char = $this->equippedCharacter(); @endphp
            @if ($char)
                @php $isEpic = $char->tier === 'epic'; @endphp
                <div class="relative z-10 flex items-center gap-4 bg-zinc-950/50 p-3 pr-5 rounded-xl border border-zinc-800/50">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center overflow-hidden shrink-0 {{ $isEpic ? 'ring-2 ring-purple-500/60 shadow-[0_0_15px_rgba(168,85,247,0.2)]' : 'ring-1 ring-zinc-700 bg-zinc-800' }}">
                        <img src="{{ $char->image }}" alt="{{ $char->name }}" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium mb-0.5">Equipped</p>
                        <p class="font-heading font-semibold text-zinc-200 text-sm">{{ $char->name }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- 2. TODAY'S FOCUS --}}
        <div class="mb-8 lg:mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-heading text-lg font-bold text-zinc-100">Today's Focus</h2>
                <p class="text-xs text-zinc-500 font-medium">{{ today()->format('l, d M') }}</p>
            </div>

            <div class="space-y-3">
                @forelse ($this->todayHabits() as $habit)
                    @php $done = $this->isCompletedToday($habit); @endphp
                    <div wire:key="focus-{{ $habit->id }}"
                         class="flex items-center bg-zinc-900 rounded-xl p-3 lg:p-4 transition group border {{ $selectedHabitId === $habit->id ? 'border-emerald-500/50 bg-zinc-800/80' : 'border-zinc-800/50 hover:bg-zinc-800/40' }}">

                        <button wire:click="toggleToday({{ $habit->id }})"
                                class="w-8 h-8 lg:w-9 lg:h-9 shrink-0 rounded-full border-2 flex items-center justify-center transition-all mr-4
                                       {{ $done ? 'bg-emerald-500 border-emerald-500 text-zinc-950 shadow-[0_0_12px_rgba(16,185,129,0.4)]' : 'border-zinc-700 hover:border-emerald-500/50' }}">
                            @if ($done)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </button>

                        <button wire:click="selectHabit({{ $habit->id }})" class="flex-1 text-left min-w-0">
                            <p class="font-heading font-medium text-base truncate {{ $done ? 'text-zinc-500 line-through' : 'text-zinc-100 group-hover:text-emerald-400 transition' }}">
                                {{ $habit->name }}
                            </p>
                            <p class="text-[11px] text-zinc-500 mt-0.5 truncate">{{ $habit->target }} {{ $habit->unit }} {{ $habit->category ? '• ' . $habit->category : '' }}</p>
                        </button>
                    </div>
                @empty
                    <div class="border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm">No habits active for today. You're all caught up!</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 3. ACTIVE CHALLENGES --}}
        <div class="pb-32 lg:pb-20">
            <h2 class="font-heading text-lg font-bold text-zinc-100 mb-4">Active Challenges</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse ($this->activeChallenges() as $member)
                    <button wire:key="challenge-{{ $member->challenge->id }}"
                            wire:click="selectChallenge({{ $member->challenge->id }})"
                            class="text-left bg-zinc-900 border border-zinc-800/80 rounded-xl p-4 transition hover:bg-zinc-800 hover:border-zinc-700
                                   {{ $selectedChallengeId === $member->challenge->id ? 'ring-1 ring-emerald-500/50' : '' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-zinc-800 flex items-center justify-center shrink-0">
                                <span class="text-lg">⚔️</span>
                            </div>
                            <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold rounded uppercase tracking-wider border border-emerald-500/20">Active</span>
                        </div>
                        <p class="font-heading font-semibold text-zinc-100 truncate mb-1">{{ $member->challenge->name }}</p>
                        <div class="flex items-center gap-3 text-xs text-zinc-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                {{ $member->challenge->points_per_completion }} pt/day
                            </span>
                            <span>•</span>
                            <span>Ends {{ $member->challenge->end_date->format('d M') }}</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm">You are not participating in any challenges right now.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- SISI KANAN (1/3 LAYAR) - PANEL DINAMIS --}}
    <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
        <div class="w-full">
            @include('pages.dashboard.partials.panel-content')
        </div>
    </div>

    {{-- PANEL MOBILE --}}
    @if ($selectedHabitId || $selectedChallengeId)
        <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
            @include('pages.dashboard.partials.panel-content')
        </div>
    @endif

</div>
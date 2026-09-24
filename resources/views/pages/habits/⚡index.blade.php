<?php

use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Models\ChallengeMember;
use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\HabitStreakFreeze;
use Flux\Flux;

new class extends Component {
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $selectedHabitId = null;
    public ?int $confirmingDeleteId = null;
    public string $calendarMonth;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:50')]
    public string $category = '';

    #[Validate('required|in:daily,weekly')]
    public string $frequency = 'daily';

    #[Validate('required|numeric|min:0.1')]
    public $target = 1;

    #[Validate('nullable|string|max:20')]
    public string $unit = '';

    #[Validate('required|date')]
    public string $start_date = '';

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->calendarMonth = today()->format('Y-m');
    }

    #[Computed]
    public function days(): array
    {
        $startOfWeek = today()->startOfWeek(Carbon::MONDAY);

        return collect(range(0, 6))->map(function ($i) use ($startOfWeek) {
            $date = $startOfWeek->copy()->addDays($i);
            return [
                'date'     => $date->toDateString(),
                'label'    => $date->format('D'),
                'num'      => $date->format('j'),
                'isToday'  => $date->isToday(),
                'isFuture' => $date->isFuture(),
            ];
        })->toArray();
    }

    #[Computed]
    public function habits()
    {
        $start = today()->startOfWeek(Carbon::MONDAY);
        $end   = today()->endOfWeek(Carbon::SUNDAY);

        return Auth::user()->habits()
            ->with(['logs' => fn ($q) => $q->whereBetween('date', [$start, $end])])
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedHabit(): ?Habit
    {
        if (! $this->selectedHabitId) {
            return null;
        }

        return Auth::user()->habits()->find($this->selectedHabitId);
    }

    public function isCompleted($habit, string $date): bool
    {
        return $habit->logs->firstWhere(
            fn ($log) => $log->date->toDateString() === $date
        )?->completed ?? false;
    }

    public function getDailyProgress(string $date): int
    {
        if ($this->habits->isEmpty()) {
            return 0;
        }
        $completed = 0;
        foreach ($this->habits as $habit) {
            if ($this->isCompleted($habit, $date)) {
                $completed++;
            }
        }
        return (int) round(($completed / $this->habits->count()) * 100);
    }

   public function toggleDay(int $habitId, string $date): void
    {
        if ($date > today()->toDateString()) {
            return;
        }

        $habit = Auth::user()->habits()->findOrFail($habitId);

        $log = HabitLog::where('habit_id', $habit->id)
            ->whereDate('date', $date)
            ->first();

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
            Auth::user()->increment('coins', 2);
            $log->coin_awarded_at = now();
            $log->save();
        }

        $this->syncChallengePoints($habit, $log);

        unset($this->habits, $this->selectedHabit);
    }

        private function syncChallengePoints(Habit $habit, HabitLog $log): void
    {
        $memberships = ChallengeMember::where('user_id', Auth::id())
            ->where('habit_id', $habit->id)
            ->get();

        $character = Auth::user()->equippedCharacter;
        $multiplier = ($character && $character->ability_type === 'points_bonus')
            ? 1 + $character->ability_value
            : 1;

        foreach ($memberships as $membership) {
            $challenge = Challenge::find($membership->challenge_id);

            if (! $challenge || $challenge->status !== 'active') {
                continue;
            }

            if ($log->completed) {
                ChallengeLog::updateOrCreate(
                    [
                        'challenge_id' => $challenge->id,
                        'user_id'      => Auth::id(),
                        'date'         => $log->date->toDateString(),
                    ],
                    [
                        'habit_log_id' => $log->id,
                        'points'       => (int) round($challenge->points_per_completion * $multiplier),
                    ]
                );
            } else {
                ChallengeLog::where('challenge_id', $challenge->id)
                    ->where('user_id', Auth::id())
                    ->where('date', $log->date->toDateString())
                    ->delete();
            }
        }
    }

    public function challengeOwnerType(Habit $habit): ?string
    {
        $membership = ChallengeMember::where('user_id', Auth::id())
            ->where('habit_id', $habit->id)
            ->whereHas('challenge', fn ($q) => $q->where('status', 'active')->whereDate('end_date', '>=', today()))
            ->with('challenge')
            ->first();

        if (! $membership || ! $membership->challenge) {
            return null;
        }

        return $membership->challenge->creator_id === Auth::id() ? 'own' : 'joined';
    }

    public function currentStreak(Habit $habit): int
    {
        $completedDates = $habit->logs()
            ->where('completed', true)
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->toArray();

        $frozenDates = HabitStreakFreeze::where('habit_id', $habit->id)
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->toArray();

        $countedDates = array_unique(array_merge($completedDates, $frozenDates));

        $cursor = today();
        if (! in_array($cursor->toDateString(), $countedDates)) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while (in_array($cursor->toDateString(), $countedDates)) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    public function isFrozen(Habit $habit, string $date): bool
    {
        return HabitStreakFreeze::where('habit_id', $habit->id)->whereDate('date', $date)->exists();
    }

    public function freezesUsedThisMonth(Habit $habit): int
    {
        return HabitStreakFreeze::where('habit_id', $habit->id)
            ->whereYear('date', today()->year)
            ->whereMonth('date', today()->month)
            ->count();
    }

    public function freezesRemaining(Habit $habit): int
    {
        $base = 3;

        $character = Auth::user()->equippedCharacter;
        if ($character && $character->ability_type === 'freeze_bonus') {
            $base += (int) $character->ability_value;
        }

        return max(0, $base - $this->freezesUsedThisMonth($habit));
    }

    public function canUseFreeze(Habit $habit): bool
    {
        $yesterday = today()->subDay()->toDateString();
        $dayBefore = today()->subDays(2)->toDateString();

        $yesterdayCounted = $habit->logs()->whereDate('date', $yesterday)->where('completed', true)->exists()
            || $this->isFrozen($habit, $yesterday);

        if ($yesterdayCounted) {
            return false;
        }

        $dayBeforeCounted = $habit->logs()->whereDate('date', $dayBefore)->where('completed', true)->exists()
            || $this->isFrozen($habit, $dayBefore);

        if (! $dayBeforeCounted) {
            return false;
        }

        return $this->freezesRemaining($habit) > 0;
    }

    public function useFreeze(int $habitId): void
    {
        $habit = Auth::user()->habits()->findOrFail($habitId);

        if (! $this->canUseFreeze($habit)) {
            return;
        }

        HabitStreakFreeze::create([
            'habit_id' => $habit->id,
            'date'     => today()->subDay()->toDateString(),
        ]);

        Flux::toast(text: 'Streak saved! Freeze used.', variant: 'success');

        unset($this->habits, $this->selectedHabit);
    }

    public function totalCompletions(Habit $habit): int
    {
        return $habit->logs()->where('completed', true)->count();
    }

    public function selectHabit(int $id): void
    {
        $this->showForm = false;
        $this->selectedHabitId = $this->selectedHabitId === $id ? null : $id;
        $this->calendarMonth = today()->format('Y-m');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->selectedHabitId = null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $habit = Auth::user()->habits()->findOrFail($id);

        if ($this->challengeOwnerType($habit) === 'joined') {
            Flux::toast(text: "Locked — this habit follows the challenge creator's rules. Leave the challenge to unlock it.", variant: 'danger');
            return;
        }

        $this->editingId  = $habit->id;
        $this->name       = $habit->name;
        $this->category   = $habit->category ?? '';
        $this->frequency  = $habit->frequency;
        $this->target     = $habit->target;
        $this->unit       = $habit->unit ?? '';
        $this->start_date = $habit->start_date->toDateString();

        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Auth::user()->habits()->findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Habit successfully updated.', variant: 'success');
        } else {
            Auth::user()->habits()->create($data);
            Flux::toast(text: 'Habit successfully created.', variant: 'success');
        }

        $this->resetForm();
        $this->showForm = false;
        unset($this->habits);
    }

    public function delete(int $id): void
    {
        Auth::user()->habits()->findOrFail($id)->delete();
        $this->selectedHabitId = null;
        $this->confirmingDeleteId = null;
        $this->showForm = false;
        unset($this->habits);
        $this->modal('confirm-delete')->close();
        Flux::toast(text: 'Habit successfully deleted.', variant: 'danger');
    }

    public function confirmDelete(int $id): void
    {
        $habit = Auth::user()->habits()->findOrFail($id);

        if ($this->challengeOwnerType($habit) === 'joined') {
            Flux::toast(text: "Can't delete a habit linked to someone else's challenge. Leave the challenge instead.", variant: 'danger');
            return;
        }

        $this->confirmingDeleteId = $id;
        $this->modal('confirm-delete')->show();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = Carbon::createFromFormat('Y-m', $this->calendarMonth)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = Carbon::createFromFormat('Y-m', $this->calendarMonth)->addMonth()->format('Y-m');
    }

    #[Computed]
    public function calendarMonthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->calendarMonth)->translatedFormat('F Y');
    }

    #[Computed]
    public function calendarWeeks(): array
    {
        if (! $this->selectedHabit) {
            return [];
        }

        $monthStart = Carbon::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $gridStart  = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd    = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $completedDates = HabitLog::where('habit_id', $this->selectedHabit->id)
            ->where('completed', true)
            ->whereBetween('date', [$gridStart, $gridEnd])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->toArray();

        $frozenDates = HabitStreakFreeze::where('habit_id', $this->selectedHabit->id)
            ->whereBetween('date', [$gridStart, $gridEnd])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->toArray();

        $weeks  = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date'      => $cursor->toDateString(),
                    'day'       => $cursor->day,
                    'inMonth'   => $cursor->month === $monthStart->month,
                    'isToday'   => $cursor->isToday(),
                    'completed' => in_array($cursor->toDateString(), $completedDates),
                    'frozen'    => in_array($cursor->toDateString(), $frozenDates),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'frequency', 'target', 'unit']);
        $this->resetValidation();
        $this->start_date = now()->toDateString();
    }
};

?>

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- ============================== --}}
    {{-- SISI KIRI (2/3 LAYAR) - WORKSPACE --}}
    {{-- ============================== --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

        {{-- Bagian Atas: Judul & Tombol Add --}}
        <div class="flex items-center justify-between mb-6 lg:mb-8">
            <h1 class="font-heading text-2xl lg:text-2xl font-bold text-zinc-100">
                Habit
            </h1>

            <button wire:click="create" title="Add New Habit"
                    class="flex items-center gap-1.5 px-4 py-2 lg:px-3 lg:py-1.5 rounded-lg bg-emerald-500 text-zinc-950 hover:bg-emerald-400 transition shadow-[0_0_12px_rgba(16,185,129,0.3)] font-semibold text-sm">
                <span>Add</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>

        {{-- Kalender Tanggal (minggu berjalan) --}}
        <div class="flex items-end border-b border-zinc-800/80 pb-4 mb-4 px-1 lg:px-3 {{ count($this->habits) ? '' : 'opacity-30' }}">
            <div class="flex-1 grid grid-cols-7 justify-items-center">
                @foreach ($this->days as $day)
                    <div class="flex flex-col items-center {{ $day['isFuture'] ? 'opacity-30' : '' }}">
                        <p class="text-[11px] font-medium {{ $day['isToday'] ? 'text-emerald-500' : 'text-zinc-400' }}">{{ $day['label'] }}</p>
                        <p class="text-[13px] font-bold mt-0.5 {{ $day['isToday'] ? 'text-emerald-500' : 'text-zinc-100' }}">{{ $day['num'] }}</p>

                        <div class="relative w-7 h-7 lg:w-[26px] lg:h-[26px] mt-2">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="18" cy="18" r="14" fill="none" class="stroke-zinc-800" stroke-width="4"></circle>
                                @php $progress = $this->getDailyProgress($day['date']); @endphp
                                <circle cx="18" cy="18" r="14" fill="none" class="stroke-emerald-500 transition-all duration-500 ease-out" stroke-width="4"
                                        stroke-dasharray="88" stroke-dashoffset="{{ 88 - (88 * $progress / 100) }}"></circle>
                            </svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if (session('message'))
            <div class="p-3 mb-6 bg-emerald-500/10 text-emerald-400 rounded-lg text-sm border border-emerald-500/20">
                {{ session('message') }}
            </div>
        @endif

        {{-- Daftar Habit --}}
        <div class="space-y-3 lg:space-y-2 pb-32 lg:pb-20">
            @forelse ($this->habits as $habit)
                @php $challengeType = $this->challengeOwnerType($habit); $isLocked = $challengeType === 'joined'; @endphp
                <div wire:key="habit-{{ $habit->id }}"
                     class="flex items-center bg-zinc-900 rounded-xl py-3.5 px-3 lg:py-3 lg:px-3 transition group
                          {{ $selectedHabitId === $habit->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">

                    {{-- Sisi Kiri: Info Habit --}}
                    <button wire:click="selectHabit({{ $habit->id }})" class="flex-1 min-w-0 lg:w-[280px] lg:flex-none text-left flex items-center gap-3 lg:gap-3.5 pl-1 pr-3 lg:pr-4">
                        <div class="w-10 h-10 lg:w-9 lg:h-9 rounded-full flex items-center justify-center shrink-0 font-heading font-bold text-sm
                             {{ ['bg-emerald-300 text-emerald-900', 'bg-blue-300 text-blue-900', 'bg-purple-300 text-purple-900', 'bg-rose-300 text-rose-900'][$habit->id % 4] }}">
                            {{ strtoupper(mb_substr($habit->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-heading text-base lg:text-[15px] font-medium text-zinc-100 truncate group-hover:text-emerald-400 transition flex items-center gap-1.5">
                                <span class="truncate">{{ $habit->name }}</span>
                                @if ($challengeType === 'own')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:w-3.5 lg:h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="currentColor" title="From a challenge you created">
                                        <path d="M5 4h14a1 1 0 011 1v2a4 4 0 01-4 4h-.1A6.002 6.002 0 0113 15.917V18h2a1 1 0 011 1v1H8v-1a1 1 0 011-1h2v-2.083A6.002 6.002 0 018.1 11H8a4 4 0 01-4-4V5a1 1 0 011-1zm0 2v1a2 2 0 002 2 6.02 6.02 0 010-3H5zm14 0h-2a6.02 6.02 0 010 3 2 2 0 002-2V6z"/>
                                    </svg>
                                @elseif ($challengeType === 'joined')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:w-3.5 lg:h-3.5 text-sky-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" title="Locked — follows the challenge creator's rules">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                @endif
                            </p>

                            {{-- Menggunakan div & truncate agar teks panjang terpotong rapi dengan "..." jika layar sangat sempit --}}
                            <div class="text-[11px] text-zinc-500 mt-0.5 flex items-center gap-2 lg:gap-3 font-medium truncate">
                                <span class="flex items-center gap-1 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"></path>
                                    </svg>
                                    {{ $this->totalCompletions($habit) }} Days
                                </span>
                                <span class="flex items-center gap-1 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-orange-500" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"></path>
                                    </svg>
                                    {{ $this->currentStreak($habit) }} Days
                                </span>
                            </div>
                        </div>
                    </button>

                    {{-- Sisi Kanan: Check-in & Actions --}}
                    <div class="flex items-center justify-end shrink-0 lg:flex-1">
                        {{-- Lingkaran Check-in --}}
                        <div class="flex items-center gap-1.5 lg:w-full lg:grid lg:grid-cols-7 lg:justify-items-center">
                            @foreach ($this->days as $day)
                                @php
                                    $done = $this->isCompleted($habit, $day['date']);
                                    $frozen = ! $done && $this->isFrozen($habit, $day['date']);
                                @endphp
                                <button wire:click="toggleDay({{ $habit->id }}, '{{ $day['date'] }}')"
                                        wire:key="day-{{ $habit->id }}-{{ $day['date'] }}"
                                        @disabled(! $day['isToday'])
                                        class="w-[32px] h-[32px] lg:w-[26px] lg:h-[26px] rounded-full items-center justify-center transition-all duration-200 shrink-0 text-[10px]
                                               {{ $day['isToday'] ? 'flex' : 'hidden lg:flex' }}
                                               {{ ! $day['isToday'] ? 'opacity-60 cursor-not-allowed' : '' }}
                                               {{ $done
                                                   ? 'bg-emerald-500 text-white shadow-[0_0_10px_rgba(16,185,129,0.3)]'
                                                   : ($frozen ? 'bg-sky-500/70 text-white' : 'bg-zinc-700/60 hover:bg-zinc-600') }}">
                                    @if($done)
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 lg:w-3.5 lg:h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @elseif($frozen)
                                        ❄️
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        {{-- Tombol Edit & Delete HANYA muncul di Desktop --}}
                        <div class="hidden lg:flex items-center gap-2 ml-4 shrink-0">
                            <button wire:click="edit({{ $habit->id }})" title="{{ $isLocked ? 'Locked by challenge' : 'Edit Habit' }}"
                                    class="p-1.5 rounded-lg transition bg-zinc-800 {{ $isLocked ? 'text-zinc-600' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-700' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>

                            <button wire:click="confirmDelete({{ $habit->id }})" title="{{ $isLocked ? 'Locked by challenge' : 'Delete Habit' }}"
                                    class="p-1.5 rounded-lg transition bg-zinc-800 {{ $isLocked ? 'text-zinc-600' : 'text-red-400 hover:text-red-500 hover:bg-zinc-700' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="border border-dashed border-zinc-800 rounded-xl p-12 text-center text-zinc-500 mt-6">
                    <p class="font-heading text-zinc-400 mb-1">No habits yet</p>
                    <p class="text-sm">Click the "Add" button above to start building your habits.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============================== --}}
    {{-- SISI KANAN (1/3 LAYAR) - PANEL DETAIL --}}
    {{-- ============================== --}}
    {{-- Desktop: panel statis di kanan --}}
    <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
        <div class="w-full">
            @include('pages.habits.partials.panel-content')
        </div>
    </div>

    {{-- Mobile: full-screen overlay, cuma render kalau ada yang dibuka --}}
    @if ($showForm || $selectedHabitId)
        <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
            @include('pages.habits.partials.panel-content')
        </div>
    @endif

    <flux:modal name="confirm-delete" class="w-full max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete this habit?</flux:heading>
                <flux:text class="mt-2 text-zinc-400">
                    This action cannot be undone. All check-in history for this habit will be permanently removed.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('confirm-delete').close()">
                    Cancel
                </flux:button>
                <flux:button variant="danger" wire:click="delete({{ $confirmingDeleteId }})">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
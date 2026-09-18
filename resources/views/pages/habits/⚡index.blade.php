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

        $this->syncChallengePoints($habit, $log);

        unset($this->habits, $this->selectedHabit);
    }

    private function syncChallengePoints(Habit $habit, HabitLog $log): void
    {
        $memberships = ChallengeMember::where('user_id', Auth::id())
            ->where('habit_id', $habit->id)
            ->get();

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
                        'points'       => $challenge->points_per_completion,
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

    public function isChallengeLinked(Habit $habit): bool
    {
        return ChallengeMember::where('user_id', Auth::id())
            ->where('habit_id', $habit->id)
            ->whereHas('challenge', fn ($q) => $q->where('status', 'active')->whereDate('end_date', '>=', today()))
            ->exists();
    }

    public function currentStreak(Habit $habit): int
    {
        $completedDates = $habit->logs()
            ->where('completed', true)
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->toArray();

        $cursor = today();
        if (! in_array($cursor->toDateString(), $completedDates)) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while (in_array($cursor->toDateString(), $completedDates)) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
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
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-8 py-8">

        {{-- Bagian Atas: Judul & Tombol Add --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-heading text-2xl font-bold text-zinc-100">
                Habit
            </h1>

            <button wire:click="create" title="Add New Habit"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500 text-zinc-950 hover:bg-emerald-400 transition shadow-[0_0_12px_rgba(16,185,129,0.3)] font-semibold text-sm">
                <span>Add</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>

        {{-- Kalender Tanggal (minggu berjalan) --}}
        <div class="flex items-end border-b border-zinc-800/80 pb-4 mb-4 px-3 {{ count($this->habits) ? '' : 'opacity-30' }}">
            <div class="flex-1 grid grid-cols-7 justify-items-center">
                @foreach ($this->days as $day)
                    <div class="flex flex-col items-center {{ $day['isFuture'] ? 'opacity-30' : '' }}">
                        <p class="text-[11px] font-medium {{ $day['isToday'] ? 'text-emerald-500' : 'text-zinc-400' }}">{{ $day['label'] }}</p>
                        <p class="text-[13px] font-bold mt-0.5 {{ $day['isToday'] ? 'text-emerald-500' : 'text-zinc-100' }}">{{ $day['num'] }}</p>

                        <div class="relative w-[26px] h-[26px] mt-2">
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
        <div class="space-y-2 pb-20">
            @forelse ($this->habits as $habit)
                <div wire:key="habit-{{ $habit->id }}"
                     class="flex items-center bg-zinc-900 rounded-xl py-3 px-3 transition group
                          {{ $selectedHabitId === $habit->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">

                    <button wire:click="selectHabit({{ $habit->id }})" class="w-[280px] shrink-0 text-left flex items-center gap-3.5 pl-1 pr-4 min-w-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 font-heading font-bold text-sm
                             {{ ['bg-emerald-300 text-emerald-900', 'bg-blue-300 text-blue-900', 'bg-purple-300 text-purple-900', 'bg-rose-300 text-rose-900'][$habit->id % 4] }}">
                            {{ strtoupper(mb_substr($habit->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-heading text-[15px] font-medium text-zinc-100 truncate group-hover:text-emerald-400 transition flex items-center gap-1.5">
                                <span class="truncate">{{ $habit->name }}</span>
                                @if ($this->isChallengeLinked($habit))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="currentColor" title="Linked to a challenge">
                                        <path d="M5 4h14a1 1 0 011 1v2a4 4 0 01-4 4h-.1A6.002 6.002 0 0113 15.917V18h2a1 1 0 011 1v1H8v-1a1 1 0 011-1h2v-2.083A6.002 6.002 0 018.1 11H8a4 4 0 01-4-4V5a1 1 0 011-1zm0 2v1a2 2 0 002 2 6.02 6.02 0 010-3H5zm14 0h-2a6.02 6.02 0 010 3 2 2 0 002-2V6z"/>
                                    </svg>
                                @endif
                            </p>
                            <p class="text-[11px] text-zinc-500 mt-0.5 flex items-center gap-3 font-medium">
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
                            </p>
                        </div>
                    </button>

                    <div class="flex-1 grid grid-cols-7 justify-items-center">
                        @foreach ($this->days as $day)
                            @php $done = $this->isCompleted($habit, $day['date']); @endphp
                            <button wire:click="toggleDay({{ $habit->id }}, '{{ $day['date'] }}')"
                                    wire:key="day-{{ $habit->id }}-{{ $day['date'] }}"
                                    @disabled($day['isFuture'])
                                    class="w-[26px] h-[26px] rounded-full flex items-center justify-center transition-all duration-200 shrink-0
                                           {{ $day['isFuture'] ? 'opacity-30 cursor-not-allowed' : '' }}
                                           {{ $done
                                                ? 'bg-emerald-500 text-white shadow-[0_0_10px_rgba(16,185,129,0.3)]'
                                                : 'bg-zinc-700/60 hover:bg-zinc-600' }}">
                                @if($done)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
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
    <div class="{{ ($showForm || $selectedHabitId) ? 'fixed inset-0 z-50 bg-zinc-900' : 'hidden' }} lg:static lg:block lg:w-1/3 lg:z-auto h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 relative shadow-xl">

        @if ($showForm)
            <div class="p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <h2 class="font-heading font-semibold text-zinc-100 text-lg">
                        {{ $editingId ? 'Edit Habit' : 'New Habit' }}
                    </h2>
                    <button wire:click="cancel" title="Close" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Habit Name</label>
                        <input type="text" wire:model="name" placeholder="e.g., Read a book"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                        @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Category</label>
                        <input type="text" wire:model="category" placeholder="Study / Health"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                        @error('category') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Frequency</label>
                        <select wire:model="frequency"
                                class="w-full border-b border-zinc-800 bg-zinc-900 text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                        @error('frequency') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Target</label>
                            <input type="number" step="0.1" wire:model="target"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                            @error('target') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Unit</label>
                            <input type="text" wire:model="unit" placeholder="hours / pages"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                            @error('unit') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Start Date</label>
                        <input type="date" wire:model="start_date"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                        @error('start_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4">
                        <button wire:click="save"
                                class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-sm">
                            Save Habit
                        </button>
                    </div>
                </div>
            </div>

        @elseif ($this->selectedHabit)
            @php $habit = $this->selectedHabit; @endphp
            <div class="p-8 space-y-6">
                <div class="flex items-start justify-between border-b border-zinc-800 pb-4">
                    <div>
                        <p class="font-heading text-xl font-semibold text-zinc-100">{{ $habit->name }}</p>
                        <p class="text-xs text-emerald-500 mt-1 tracking-wide uppercase font-medium">{{ $habit->category ?: 'Uncategorized' }}</p>
                    </div>
                    <button wire:click="$set('selectedHabitId', null)" title="Close" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"></path>
                        </svg>
                        <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->currentStreak($habit) }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">Current Streak</p>
                    </div>
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"></path>
                        </svg>
                        <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->totalCompletions($habit) }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">Total Completions</p>
                    </div>
                </div>

                {{-- Kalender bulanan --}}
                <div class="border-t border-zinc-800/50 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <button wire:click="previousMonth" class="p-1 rounded-md text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <p class="font-heading text-sm font-semibold text-zinc-200">{{ $this->calendarMonthLabel }}</p>
                        <button wire:click="nextMonth" class="p-1 rounded-md text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-y-1.5">
                        @foreach (['S','M','T','W','T','F','S'] as $d)
                            <p class="text-[10px] text-zinc-500 font-medium text-center">{{ $d }}</p>
                        @endforeach

                        @foreach ($this->calendarWeeks as $week)
                            @foreach ($week as $cell)
                                <div wire:key="cal-{{ $cell['date'] }}" class="flex justify-center">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[11px]
                                                {{ ! $cell['inMonth'] ? 'text-zinc-700' : 'text-zinc-300' }}
                                                {{ $cell['completed'] ? 'bg-emerald-500 text-white font-semibold' : '' }}
                                                {{ $cell['isToday'] && ! $cell['completed'] ? 'ring-1 ring-emerald-500' : '' }}">
                                        {{ $cell['day'] }}
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="text-sm text-zinc-300 space-y-4 pt-2">
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Frequency
                        </span>
                        <span class="font-medium text-zinc-200">{{ $habit->frequency === 'daily' ? 'Daily' : 'Weekly' }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.563-.524-.985a6.953 6.953 0 00.553-2.733v-.784z" /></svg>
                            Target
                        </span>
                        <span class="font-medium text-zinc-200">{{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                            Started On
                        </span>
                        <span class="font-medium text-zinc-200">{{ $habit->start_date->translatedFormat('d M Y') }}</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button wire:click="edit({{ $habit->id }})"
                            class="flex-1 py-2 text-sm font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg transition border border-zinc-700">
                        Edit
                    </button>
                    <button wire:click="confirmDelete({{ $habit->id }})"
                            class="py-2 px-4 text-sm font-medium bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition border border-red-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <div class="hidden lg:flex h-full items-center justify-center p-8 text-center text-zinc-600 text-sm">
                Select a habit to view details, or click "Add" to create a new one.
            </div>
        @endif
    </div>

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
<?php

use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $selectedHabitId = null;

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
    }

    #[Computed]
    public function days(): array
    {
        return collect(range(6, 0))->map(function ($i) {
            $date = today()->subDays($i);
            return [
                'date'     => $date->toDateString(),
                'label'    => $date->translatedFormat('D'),
                'num'      => $date->format('j'),
                'isToday'  => $date->isToday(),
                'isFuture' => false,
            ];
        })->toArray();
    }

    #[Computed]
    public function habits()
    {
        $start = today()->subDays(6);

        return Auth::user()->habits()
            ->with(['logs' => fn ($q) => $q->whereBetween('date', [$start, today()])])
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

    public function toggleDay(int $habitId, string $date): void
    {
        if ($date > today()->toDateString()) {
            return; 
        }

        $habit = Auth::user()->habits()->findOrFail($habitId);

        $log = HabitLog::firstOrNew([
            'habit_id' => $habit->id,
            'date'     => $date,
        ]);

        $log->completed = ! $log->completed;
        $log->value = $log->completed ? $habit->target : 0;
        $log->save();

        unset($this->habits, $this->selectedHabit);
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
            session()->flash('message', 'Habit berhasil diperbarui.');
        } else {
            Auth::user()->habits()->create($data);
            session()->flash('message', 'Habit berhasil dibuat.');
        }

        $this->resetForm();
        $this->showForm = false;
        unset($this->habits);
    }

    public function delete(int $id): void
    {
        Auth::user()->habits()->findOrFail($id)->delete();
        $this->selectedHabitId = null;
        $this->showForm = false;
        unset($this->habits);
        session()->flash('message', 'Habit berhasil dihapus.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'frequency', 'target', 'unit']);
        $this->resetValidation();
        $this->start_date = now()->toDateString();
    }
};

?>

{{-- Memaksa komponen menempel penuh di layar dari sisi kanan sidebar --}}
<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">
    
    {{-- ============================== --}}
    {{-- SISI KIRI (2/3 LAYAR) --}}
    {{-- ============================== --}}
    {{-- Menggunakan bg-zinc-950 (Sangat Gelap) agar kontras dengan Sidebar --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-8 py-8">
        
        {{-- Header Kiri: Judul di Kiri, Tombol Tambah Habit di Kanan --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-heading text-2xl font-bold text-zinc-100 flex items-center gap-2">
                Habit
                <i class="ti ti-chevron-down text-lg text-zinc-600"></i>
            </h1>
            
            <button wire:click="create"
                    class="flex items-center gap-2 px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium rounded-lg border border-zinc-700 transition shadow-sm">
                <i class="ti ti-plus text-base" aria-hidden="true"></i> Tambah Habit
            </button>
        </div>

        @if (session('message'))
            <div class="p-3 mb-6 bg-emerald-500/10 text-emerald-400 rounded-lg text-sm border border-emerald-500/20">
                {{ session('message') }}
            </div>
        @endif

        {{-- Tanggal 7 Hari Terakhir --}}
        @if (count($this->habits))
            <div class="flex justify-end gap-3 pr-4 border-b border-zinc-800 pb-3 mb-4">
                @foreach ($this->days as $day)
                    <div class="w-8 text-center flex flex-col items-center">
                        <p class="text-[11px] {{ $day['isToday'] ? 'text-amber-500 font-semibold' : 'text-zinc-500' }}">{{ $day['label'] }}</p>
                        <p class="text-[12px] mt-0.5 {{ $day['isToday'] ? 'text-amber-500 font-semibold' : 'text-zinc-400' }}">{{ $day['num'] }}</p>
                        @if($day['isToday'])
                            <div class="w-1 h-1 rounded-full bg-amber-500 mt-1"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Daftar Habit --}}
        <div class="space-y-2 pb-20">
            @forelse ($this->habits as $habit)
                <div wire:key="habit-{{ $habit->id }}"
                     class="bg-zinc-900/60 rounded-xl p-4 flex items-center justify-between transition group
                          {{ $selectedHabitId === $habit->id ? 'bg-zinc-900 ring-1 ring-amber-500/50' : 'hover:bg-zinc-900/90 border border-zinc-800/60' }}">

                    <button wire:click="selectHabit({{ $habit->id }})" class="text-left flex-1 min-w-0 pr-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 shadow-sm
                             {{ ['bg-blue-500/20 text-blue-400', 'bg-emerald-500/20 text-emerald-400', 'bg-purple-500/20 text-purple-400', 'bg-rose-500/20 text-rose-400'][$habit->id % 4] }}">
                            <i class="ti {{ ['ti-glass-full', 'ti-mood-smile', 'ti-book', 'ti-barbell'][$habit->id % 4] }} text-lg"></i>
                        </div>

                        <div>
                            <p class="font-heading text-sm font-medium text-zinc-100 group-hover:text-amber-400 transition">{{ $habit->name }}</p>
                            <p class="text-[11px] text-zinc-500 mt-0.5 flex items-center gap-3 font-medium">
                                <span><i class="ti ti-bolt text-blue-500" aria-hidden="true"></i> {{ $this->totalCompletions($habit) }} Days</span>
                                <span><i class="ti ti-flame text-orange-500" aria-hidden="true"></i> {{ $this->currentStreak($habit) }} Days</span>
                            </p>
                        </div>
                    </button>

                    <div class="flex gap-1.5 shrink-0 pr-1">
                        @foreach ($this->days as $day)
                            @php $done = $this->isCompleted($habit, $day['date']); @endphp
                            <button wire:click="toggleDay({{ $habit->id }}, '{{ $day['date'] }}')"
                                    wire:key="day-{{ $habit->id }}-{{ $day['date'] }}"
                                    class="w-8 h-8 rounded-full border flex items-center justify-center transition
                                           {{ $done
                                                ? 'bg-blue-500 border-blue-500 text-white shadow-[0_0_8px_rgba(59,130,246,0.5)]'
                                                : 'border-zinc-700 bg-zinc-800/40 text-transparent hover:border-zinc-500' }}">
                                <i class="ti ti-check" style="font-size:14px" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="border border-dashed border-zinc-800 rounded-xl p-12 text-center text-zinc-500">
                    <p class="font-heading text-zinc-400 mb-1">Belum ada habit</p>
                    <p class="text-sm">Klik tombol "Tambah Habit" di atas untuk mulai membangun kebiasaan.</p>
                </div>
            @endforelse
        </div>
    </div>


    {{-- ============================== --}}
    {{-- SISI KANAN (1/3 LAYAR) --}}
    {{-- ============================== --}}
    {{-- Menggunakan bg-zinc-900 agar menjadi panel yang jelas memisahkan diri dari sisi kiri --}}
    <div class="hidden lg:block lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/50 relative">
        
        @if ($showForm)
            <div class="p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <h2 class="font-heading font-semibold text-zinc-100 text-lg">
                        {{ $editingId ? 'Edit Habit' : 'Habit Baru' }}
                    </h2>
                    <button wire:click="cancel" class="text-zinc-500 hover:text-zinc-300 transition">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Nama Habit</label>
                        <input type="text" wire:model="name" placeholder="Contoh: Belajar 1 jam"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition placeholder:text-zinc-700">
                        @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Kategori</label>
                        <input type="text" wire:model="category" placeholder="Study / Health"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition placeholder:text-zinc-700">
                        @error('category') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Frekuensi</label>
                        <select wire:model="frequency"
                                class="w-full border-b border-zinc-800 bg-zinc-900 text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition">
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                        </select>
                        @error('frequency') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Target</label>
                            <input type="number" step="0.1" wire:model="target"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition">
                            @error('target') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Satuan</label>
                            <input type="text" wire:model="unit" placeholder="jam / halaman"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition placeholder:text-zinc-700">
                            @error('unit') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Mulai</label>
                        <input type="date" wire:model="start_date"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-amber-500 outline-none transition">
                        @error('start_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4">
                        <button wire:click="save"
                                class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold rounded-lg transition text-sm">
                            Simpan Habit
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
                        <p class="text-xs text-amber-500 mt-1 tracking-wide uppercase font-medium">{{ $habit->category ?: 'Tanpa kategori' }}</p>
                    </div>
                    <button wire:click="$set('selectedHabitId', null)" class="text-zinc-500 hover:text-zinc-300 transition">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <i class="ti ti-flame text-orange-500 text-2xl mb-1 block"></i>
                        <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->currentStreak($habit) }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">Streak Hari Ini</p>
                    </div>
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <i class="ti ti-bolt text-blue-500 text-2xl mb-1 block"></i>
                        <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->totalCompletions($habit) }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">Total Selesai</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 space-y-4 pt-2">
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500"><i class="ti ti-calendar-repeat mr-2"></i>Frekuensi</span>
                        <span class="font-medium text-zinc-200">{{ $habit->frequency === 'daily' ? 'Harian' : 'Mingguan' }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500"><i class="ti ti-target mr-2"></i>Target</span>
                        <span class="font-medium text-zinc-200">{{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2">
                        <span class="text-zinc-500"><i class="ti ti-flag mr-2"></i>Mulai Sejak</span>
                        <span class="font-medium text-zinc-200">{{ $habit->start_date->translatedFormat('d M Y') }}</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button wire:click="edit({{ $habit->id }})"
                            class="flex-1 py-2 text-sm font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg transition border border-zinc-700">
                        Edit
                    </button>
                    <button wire:click="delete({{ $habit->id }})"
                            wire:confirm="Yakin mau hapus habit ini?"
                            class="py-2 px-4 text-sm font-medium bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition border border-red-500/20">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
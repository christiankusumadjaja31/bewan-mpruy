<?php

use App\Models\Habit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public bool $showForm = false;
    public ?int $editingId = null;

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
    public function habits()
    {
        return Auth::user()->habits()->latest()->get();
    }

    public function create(): void
    {
        $this->resetForm();
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

<div class="max-w-4xl mx-auto p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-heading text-2xl font-semibold text-zinc-900 dark:text-zinc-100">My Habits</h1>
        @unless($showForm)
            <button wire:click="create"
                    class="px-4 py-2 bg-amber-500 text-zinc-900 font-medium rounded-lg hover:bg-amber-400 transition">
                + Tambah Habit
            </button>
        @endunless
    </div>

    @if (session('message'))
        <div class="p-3 bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif

    @if ($showForm)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 space-y-4 bg-zinc-50 dark:bg-zinc-900">
            <h2 class="font-heading font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $editingId ? 'Edit Habit' : 'Habit Baru' }}
            </h2>

            <div>
                <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Nama Habit</label>
                <input type="text" wire:model="name" placeholder="Contoh: Belajar 1 jam"
                       class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Kategori</label>
                    <input type="text" wire:model="category" placeholder="Study / Health"
                           class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('category') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Frekuensi</label>
                    <select wire:model="frequency"
                            class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <option value="daily">Harian</option>
                        <option value="weekly">Mingguan</option>
                    </select>
                    @error('frequency') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Target</label>
                    <input type="number" step="0.1" wire:model="target"
                           class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('target') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Satuan</label>
                    <input type="text" wire:model="unit" placeholder="jam / halaman"
                           class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('unit') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1 text-zinc-600 dark:text-zinc-400">Mulai</label>
                    <input type="date" wire:model="start_date"
                           class="w-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('start_date') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button wire:click="save"
                        class="px-4 py-2 bg-amber-500 text-zinc-900 font-medium rounded-lg hover:bg-amber-400 transition">
                    Simpan
                </button>
                <button wire:click="cancel"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                    Batal
                </button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->habits as $habit)
            <div wire:key="habit-{{ $habit->id }}"
                 class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center justify-between hover:border-amber-500/50 transition">
                <div>
                    <p class="font-heading font-medium text-zinc-900 dark:text-zinc-100">{{ $habit->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $habit->category ?: 'Tanpa kategori' }} &middot;
                        {{ $habit->frequency === 'daily' ? 'Harian' : 'Mingguan' }} &middot;
                        Target {{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        0 hari streak
                    </span>
                    <button wire:click="edit({{ $habit->id }})"
                            class="px-3 py-1 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                        Edit
                    </button>
                    <button wire:click="delete({{ $habit->id }})"
                            wire:confirm="Yakin mau hapus habit ini?"
                            class="px-3 py-1 text-sm border border-red-300 dark:border-red-900 text-red-500 rounded-lg hover:bg-red-500/10 transition">
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="border border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-8 text-center text-zinc-500 dark:text-zinc-400">
                <p class="font-heading text-zinc-700 dark:text-zinc-300 mb-1">Belum ada habit</p>
                <p class="text-sm">Klik "Tambah Habit" untuk mulai membangun kebiasaan pertamamu.</p>
            </div>
        @endforelse
    </div>

</div>
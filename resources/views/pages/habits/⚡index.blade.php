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
        <h1 class="text-2xl font-bold">My Habits</h1>
        @unless($showForm)
            <button wire:click="create"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                + Tambah Habit
            </button>
        @endunless
    </div>

    @if (session('message'))
        <div class="p-3 bg-green-100 text-green-800 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if ($showForm)
        <div class="border rounded-lg p-5 space-y-4">
            <h2 class="font-semibold">
                {{ $editingId ? 'Edit Habit' : 'Habit Baru' }}
            </h2>

            <div>
                <label class="block text-sm mb-1">Nama Habit</label>
                <input type="text" wire:model="name" placeholder="Contoh: Belajar 1 jam"
                       class="w-full border rounded-lg px-3 py-2">
                @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Kategori</label>
                    <input type="text" wire:model="category" placeholder="Study / Health"
                           class="w-full border rounded-lg px-3 py-2">
                    @error('category') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Frekuensi</label>
                    <select wire:model="frequency" class="w-full border rounded-lg px-3 py-2">
                        <option value="daily">Harian</option>
                        <option value="weekly">Mingguan</option>
                    </select>
                    @error('frequency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm mb-1">Target</label>
                    <input type="number" step="0.1" wire:model="target"
                           class="w-full border rounded-lg px-3 py-2">
                    @error('target') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Satuan</label>
                    <input type="text" wire:model="unit" placeholder="jam / halaman"
                           class="w-full border rounded-lg px-3 py-2">
                    @error('unit') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Mulai</label>
                    <input type="date" wire:model="start_date"
                           class="w-full border rounded-lg px-3 py-2">
                    @error('start_date') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex gap-2">
                <button wire:click="save"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Simpan
                </button>
                <button wire:click="cancel"
                        class="px-4 py-2 border rounded-lg">
                    Batal
                </button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->habits as $habit)
            <div wire:key="habit-{{ $habit->id }}"
                 class="border rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $habit->name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $habit->category ?: 'Tanpa kategori' }} &middot;
                        {{ $habit->frequency === 'daily' ? 'Harian' : 'Mingguan' }} &middot;
                        Target {{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button wire:click="edit({{ $habit->id }})"
                            class="px-3 py-1 text-sm border rounded-lg">
                        Edit
                    </button>
                    <button wire:click="delete({{ $habit->id }})"
                            wire:confirm="Yakin mau hapus habit ini?"
                            class="px-3 py-1 text-sm border border-red-300 text-red-600 rounded-lg">
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="border border-dashed rounded-lg p-8 text-center text-gray-500">
                Belum ada habit. Klik "Tambah Habit" untuk mulai.
            </div>
        @endforelse
    </div>

</div>
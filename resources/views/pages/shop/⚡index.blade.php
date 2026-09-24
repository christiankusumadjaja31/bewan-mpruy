<?php

use App\Models\Character;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $activeSection = 'characters'; // 'characters' | 'currency'

    public ?int $selectedCharacterId = null;
    public ?string $selectedPackageKey = null;

    public function packages(): array
    {
        return [
            ['key' => 'small',  'coins' => 100,  'price_label' => 'Rp15.000'],
            ['key' => 'medium', 'coins' => 500,  'price_label' => 'Rp65.000'],
            ['key' => 'large',  'coins' => 1200, 'price_label' => 'Rp120.000', 'best_value' => true],
        ];
    }

    public function characters()
    {
        // Ambil semua ID karakter yang sudah dimiliki user
        $ownedIds = Auth::user()->characters()->pluck('characters.id')->toArray();

        // Ambil semua karakter, tandai mana yang owned, lalu sort (false di atas, true di bawah)
        return Character::all()->map(function ($c) use ($ownedIds) {
            $c->is_owned = in_array($c->id, $ownedIds);
            return $c;
        })->sortBy('is_owned')->values();
    }

    public function selectedCharacter(): ?Character
    {
        if (! $this->selectedCharacterId) {
            return null;
        }
        return Character::find($this->selectedCharacterId);
    }

    public function selectedPackage(): ?array
    {
        if (! $this->selectedPackageKey) {
            return null;
        }
        return collect($this->packages())->firstWhere('key', $this->selectedPackageKey);
    }

    public function selectSection(string $section): void
    {
        $this->activeSection = $section;
        $this->selectedCharacterId = null;
        $this->selectedPackageKey = null;
    }

    public function selectCharacter(int $id): void
    {
        $this->selectedPackageKey = null;
        $this->selectedCharacterId = $this->selectedCharacterId === $id ? null : $id;
    }

    public function selectPackage(string $key): void
    {
        $this->selectedCharacterId = null;
        $this->selectedPackageKey = $this->selectedPackageKey === $key ? null : $key;
    }

    public function buy(int $characterId): void
    {
        $character = Character::findOrFail($characterId);

        if (Auth::user()->ownsCharacter($character->id)) {
            return;
        }

        if (Auth::user()->coins < $character->price_coins) {
            Flux::toast(text: 'Not enough coins.', variant: 'danger');
            return;
        }

        Auth::user()->decrement('coins', $character->price_coins);
        Auth::user()->characters()->attach($character->id, ['unlocked_at' => now()]);

        Flux::toast(text: "{$character->name} unlocked!", variant: 'success');
    }

    public function equip(int $characterId): void
    {
        if (! Auth::user()->ownsCharacter($characterId)) {
            return;
        }

        Auth::user()->update(['equipped_character_id' => $characterId]);
        Flux::toast(text: 'Character equipped.', variant: 'success');
    }

    public function unequip(): void
    {
        Auth::user()->update(['equipped_character_id' => null]);
        Flux::toast(text: 'Character unequipped.', variant: 'success');
    }

    public function simulateTopup(string $packageKey): void
    {
        $package = collect($this->packages())->firstWhere('key', $packageKey);

        if (! $package) {
            return;
        }

        Auth::user()->increment('coins', $package['coins']);
        Flux::toast(text: "{$package['coins']} coins added (simulated purchase).", variant: 'success');
    }
};

?>

{{-- BUNGKUS ROOT MURNI UNTUK MENCEGAH ERROR MULTIPLE ROOT ELEMENT --}}
<div>
    <div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

        {{-- ============================== --}}
        {{-- SISI KIRI (2/3) --}}
        {{-- ============================== --}}
        <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

        {{-- Header & Coin Balance --}}
        <div class="flex items-center justify-between mb-6 lg:mb-8">
            <h1 class="font-heading text-2xl lg:text-2xl font-bold text-zinc-100">Shop</h1>
            
            {{-- Tinggi dipaksa fix (h-[36px] dan h-[32px]) agar border dan ukuran emoji tidak memelarkan elemen --}}
            <div class="flex items-center justify-center gap-1.5 px-4 lg:px-3 rounded-lg bg-amber-500/10 border border-amber-500/20 font-semibold text-sm h-[36px] lg:h-[32px]">
                <span class="leading-none text-[15px] lg:text-sm">🪙</span>
                <span class="font-heading font-bold text-amber-400 leading-none">{{ Auth::user()->coins }}</span>
            </div>
        </div>

        {{-- Horizontal Tabs --}}
        <div class="flex gap-1 mb-6 border-b border-zinc-800">
            <button wire:click="selectSection('characters')"
                    class="px-4 py-2.5 lg:py-2 text-sm font-medium border-b-2 transition
                           {{ $activeSection === 'characters' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                Characters
            </button>
            <button wire:click="selectSection('currency')"
                    class="px-4 py-2.5 lg:py-2 text-sm font-medium border-b-2 transition
                           {{ $activeSection === 'currency' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                Currency
            </button>
        </div>

            {{-- Konten Utama --}}
            @if ($activeSection === 'characters')
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pb-32 lg:pb-20">
                    @forelse ($this->characters() as $c)
                        <button wire:click="selectCharacter({{ $c->id }})" wire:key="char-{{ $c->id }}"
                                class="relative bg-zinc-900 border rounded-xl p-4 text-center transition
                                       {{ $selectedCharacterId === $c->id ? 'border-emerald-500 ring-1 ring-emerald-500/50' : ($c->tier === 'epic' ? 'border-purple-500/40 hover:border-purple-400/60' : 'border-zinc-800 hover:border-zinc-700') }}
                                       {{ $c->tier === 'epic' ? 'bg-gradient-to-b from-purple-950/40 to-zinc-900' : '' }}
                                       {{ $c->is_owned ? 'bg-zinc-900/50' : '' }}">

                            {{-- Avatar Karakter (Efek Grayscale jika belum punya) --}}
                            <div class="relative w-14 h-14 mx-auto rounded-full bg-zinc-800 flex items-center justify-center mb-2 overflow-hidden
                                        {{ $c->is_owned ? 'ring-2 ring-emerald-500/30' : ($c->tier === 'epic' ? 'ring-2 ring-purple-500/40' : '') }}">
                                <img src="{{ $c->image }}" alt="{{ $c->name }}" 
                                     class="block w-full h-full object-cover object-center transition {{ ! $c->is_owned ? 'grayscale opacity-60' : '' }}">
                            </div>

                            <p class="text-xs font-medium text-zinc-100 mb-0.5">{{ $c->name }}</p>
                            <p class="text-[10px] {{ $c->tier === 'epic' ? 'text-purple-400' : 'text-zinc-600' }} mb-1.5">
                                {{ str_repeat('★', $c->stars()) }}
                            </p>
                            
                            {{-- Penanda Status --}}
                            <div>
                                @if ($c->is_owned)
                                    @if (Auth::user()->equipped_character_id === $c->id)
                                        <span class="text-[9px] font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">Equipped</span>
                                    @else
                                        <span class="text-[9px] font-medium text-zinc-400 bg-zinc-800 px-2.5 py-0.5 rounded-full">Owned</span>
                                    @endif
                                @else
                                    <span class="text-[10px] font-semibold text-amber-400 flex items-center justify-center gap-1">
                                        <span>🪙</span> {{ $c->price_coins }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                            No characters available yet.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pb-32 lg:pb-20">
                    @foreach ($this->packages() as $pkg)
                        <button wire:click="selectPackage('{{ $pkg['key'] }}')" wire:key="pkg-{{ $pkg['key'] }}"
                                class="relative bg-zinc-900 border rounded-xl p-4 text-center transition
                                       {{ $selectedPackageKey === $pkg['key'] ? 'border-amber-500 ring-1 ring-amber-500/50' : 'border-zinc-800 hover:border-zinc-700' }}">
                            @if (! empty($pkg['best_value']))
                                <span class="absolute -top-2 left-1/2 -translate-x-1/2 text-[9px] bg-amber-500 text-zinc-950 px-2.5 py-0.5 rounded-full font-bold shadow-sm">Best Value</span>
                            @endif
                            <p class="text-3xl lg:text-2xl mt-1">🪙</p>
                            <p class="text-base lg:text-sm font-heading font-semibold text-zinc-100 mt-2">{{ $pkg['coins'] }}</p>
                            <p class="text-[11px] lg:text-[10px] text-zinc-500 mt-0.5">{{ $pkg['price_label'] }}</p>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ============================== --}}
        {{-- SISI KANAN (1/3) --}}
        {{-- ============================== --}}
        <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
            <div class="w-full">
                @include('pages.shop.partials.panel-content')
            </div>
        </div>

        @if ($selectedCharacterId || $selectedPackageKey)
            <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
                @include('pages.shop.partials.panel-content')
            </div>
        @endif
    </div>
</div>
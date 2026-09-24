<?php

use App\Models\Character;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $activeSection = 'characters'; // 'characters' | 'currency'
    public string $characterTab = 'owned'; // 'owned' | 'locked'

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

    public function ownedCharacters()
    {
        return Auth::user()->characters()->get();
    }

    public function lockedCharacters()
    {
        $ownedIds = Auth::user()->characters()->pluck('characters.id')->toArray();
        return Character::whereNotIn('id', $ownedIds)->get();
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

    public function setCharacterTab(string $tab): void
    {
        $this->characterTab = $tab;
        $this->selectedCharacterId = null;
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
        $this->characterTab = 'owned';
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

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- ============================== --}}
    {{-- SISI KIRI (2/3) --}}
    {{-- ============================== --}}
    <div class="w-full lg:w-2/3 h-full flex bg-zinc-950">

        {{-- Rail kategori vertikal ala Genshin --}}
        <div class="w-16 shrink-0 border-r border-zinc-800/80 flex flex-col items-center pt-6 gap-1">
            <button wire:click="selectSection('characters')"
                    class="flex flex-col items-center gap-1 py-2 w-full transition {{ $activeSection === 'characters' ? 'text-zinc-100' : 'text-zinc-500 hover:text-zinc-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-[9px]">Characters</span>
            </button>
            <button wire:click="selectSection('currency')"
                    class="flex flex-col items-center gap-1 py-2 w-full transition {{ $activeSection === 'currency' ? 'text-zinc-100' : 'text-zinc-500 hover:text-zinc-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-[9px]">Currency</span>
            </button>
        </div>

        {{-- Konten utama --}}
        <div class="flex-1 min-w-0 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-heading text-2xl font-bold text-zinc-100">Shop</h1>
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
                    <span>🪙</span>
                    <span class="font-heading font-bold text-amber-400">{{ Auth::user()->coins }}</span>
                </div>
            </div>

            @if ($activeSection === 'characters')
                <div class="flex gap-1 mb-6 border-b border-zinc-800">
                    <button wire:click="setCharacterTab('owned')"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition
                                   {{ $characterTab === 'owned' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                        Owned
                    </button>
                    <button wire:click="setCharacterTab('locked')"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition
                                   {{ $characterTab === 'locked' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                        Locked
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pb-24 lg:pb-8">
                    @php $list = $characterTab === 'owned' ? $this->ownedCharacters() : $this->lockedCharacters(); @endphp
                    @forelse ($list as $c)
                        <button wire:click="selectCharacter({{ $c->id }})" wire:key="char-{{ $c->id }}"
                                class="bg-zinc-900 border rounded-xl p-4 text-center transition
                                       {{ $selectedCharacterId === $c->id ? 'border-emerald-500 ring-1 ring-emerald-500/50' : 'border-zinc-800 hover:border-zinc-700' }}
                                       {{ $characterTab === 'locked' ? 'opacity-70' : '' }}">
                            
                            <div class="relative w-14 h-14 mx-auto rounded-full bg-zinc-800 flex items-center justify-center mb-2 overflow-hidden">
                                <img src="{{ $c->image }}" alt="{{ $c->name }}" class="block w-full h-full object-cover object-center">
                                @if ($characterTab === 'locked')
                                    <div class="absolute -bottom-1 -right-1 bg-zinc-900 rounded-full w-5 h-5 flex items-center justify-center border border-zinc-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-2.5 h-2.5 text-zinc-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <p class="text-xs font-medium text-zinc-100">{{ $c->name }}</p>
                            @if ($characterTab === 'owned' && Auth::user()->equipped_character_id === $c->id)
                                <span class="text-[9px] text-emerald-400">Equipped</span>
                            @elseif ($characterTab === 'locked')
                                <span class="text-[9px] text-amber-400">🪙 {{ $c->price_coins }}</span>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full border border-dashed border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                            {{ $characterTab === 'owned' ? "You don't own any characters yet." : "You've unlocked everything!" }}
                        </div>
                    @endforelse
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pb-24 lg:pb-8">
                    @foreach ($this->packages() as $pkg)
                        <button wire:click="selectPackage('{{ $pkg['key'] }}')" wire:key="pkg-{{ $pkg['key'] }}"
                                class="relative bg-zinc-900 border rounded-xl p-4 text-center transition
                                       {{ $selectedPackageKey === $pkg['key'] ? 'border-amber-500 ring-1 ring-amber-500/50' : 'border-zinc-800 hover:border-zinc-700' }}">
                            @if (! empty($pkg['best_value']))
                                <span class="absolute -top-2 left-1/2 -translate-x-1/2 text-[9px] bg-amber-500 text-zinc-950 px-2 py-0.5 rounded-full font-semibold">Best Value</span>
                            @endif
                            <p class="text-2xl">🪙</p>
                            <p class="text-base font-heading font-semibold text-zinc-100 mt-1">{{ $pkg['coins'] }}</p>
                            <p class="text-[11px] text-zinc-500">{{ $pkg['price_label'] }}</p>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
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
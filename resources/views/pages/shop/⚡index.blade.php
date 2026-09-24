<?php

use App\Models\Character;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    #[Computed]
    public function characters()
    {
        $ownedIds = Auth::user()->characters()->pluck('characters.id')->toArray();

        return Character::all()->map(fn ($c) => [
            'model' => $c,
            'owned' => in_array($c->id, $ownedIds),
        ]);
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
        unset($this->characters);
    }

    public function simulateTopup(): void
    {
        Auth::user()->increment('coins', 500);
        Flux::toast(text: '500 coins added (simulated purchase).', variant: 'success');
        unset($this->characters);
    }

    public function equip(int $characterId): void
    {
        if (! Auth::user()->ownsCharacter($characterId)) {
            return;
        }

        Auth::user()->update(['equipped_character_id' => $characterId]);
        Flux::toast(text: 'Character equipped.', variant: 'success');
        unset($this->characters);
    }

    public function unequip(): void
    {
        Auth::user()->update(['equipped_character_id' => null]);
        unset($this->characters);
    }
};

?>

<div class="min-h-screen bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8 lg:pl-24">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-heading text-2xl font-bold text-zinc-100">Shop</h1>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
                <span class="text-amber-400">🪙</span>
                <span class="font-heading font-bold text-amber-400">{{ Auth::user()->coins }}</span>
            </div>

            <button wire:click="$dispatch('simulate-topup')" x-data x-on:simulate-topup.window="if(confirm('Simulate buying 500 coins for demo?')) $wire.simulateTopup()"
                    class="text-xs text-zinc-500 hover:text-zinc-300 underline">
                (Demo) Top up
            </button>
        </div>

        <div class="space-y-4">
            @foreach ($this->characters as $entry)
                @php $c = $entry['model']; $isEquipped = Auth::user()->equipped_character_id === $c->id; @endphp
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 flex items-start gap-4">
                    <div class="w-14 h-14 rounded-full bg-zinc-800 flex items-center justify-center text-3xl shrink-0">
                        {{ $c->emoji }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-heading font-semibold text-zinc-100">{{ $c->name }}</p>
                            @if ($isEquipped)
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold uppercase">Equipped</span>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-400 mt-1">{{ $c->description }}</p>

                        <div class="mt-3">
                            @if ($entry['owned'])
                                @if ($isEquipped)
                                    <button wire:click="unequip"
                                            class="px-3 py-1.5 text-xs font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg transition">
                                        Unequip
                                    </button>
                                @else
                                    <button wire:click="equip({{ $c->id }})"
                                            class="px-3 py-1.5 text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-zinc-950 rounded-lg transition">
                                        Equip
                                    </button>
                                @endif
                            @else
                                <button wire:click="buy({{ $c->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-400 text-zinc-950 rounded-lg transition">
                                    🪙 {{ $c->price_coins }} — Unlock
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@if ($this->selectedCharacter())
    @php
        $c = $this->selectedCharacter();
        $owned = Auth::user()->ownsCharacter($c->id);
        $equipped = Auth::user()->equipped_character_id === $c->id;
        $isEpic = $c->tier === 'epic';
    @endphp
    <div class="p-5 lg:p-8 h-full flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Character</h2>
            <button wire:click="$set('selectedCharacterId', null)" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="w-20 h-20 mx-auto rounded-full bg-zinc-800 flex items-center justify-center mb-3 overflow-hidden {{ $isEpic ? 'ring-2 ring-purple-500/50' : '' }}">
                <img src="{{ $c->image }}" alt="{{ $c->name }}" class="block w-full h-full object-cover object-center">
            </div>

            <p class="text-center font-heading text-xl font-semibold text-zinc-100">{{ $c->name }}</p>
            <div class="flex items-center justify-center gap-1.5 mt-1 mb-5">
                <span class="text-xs {{ $isEpic ? 'text-purple-400' : 'text-zinc-500' }}">{{ str_repeat('★', $c->stars()) }}</span>
                <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $isEpic ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-zinc-800 text-zinc-400' }}">
                    {{ $isEpic ? 'Epic' : 'Basic' }}
                </span>
            </div>

            <div class="space-y-1.5 mb-5">
                <p class="text-[10px] uppercase tracking-wider text-zinc-500 font-medium">About</p>
                <p class="text-sm text-zinc-400 leading-relaxed">{{ $c->description }}</p>
            </div>

            <div class="rounded-xl p-4 border {{ $isEpic ? 'bg-purple-500/5 border-purple-500/20' : 'bg-zinc-800/40 border-zinc-700/50' }}">
                <p class="text-[10px] uppercase tracking-wider {{ $isEpic ? 'text-purple-400' : 'text-emerald-400' }} font-medium mb-1">Ability — {{ $c->ability_name }}</p>
                <p class="text-sm text-zinc-300 leading-relaxed">{{ $c->ability_description }}</p>
            </div>

            @if ($owned)
                <p class="text-xs text-zinc-600 mt-4 text-center">Unlocked</p>
            @else
                <p class="text-xs text-zinc-600 mt-4 text-center">🪙 {{ $c->price_coins }} to unlock</p>
            @endif
        </div>

        <div class="pt-4">
            @if ($owned)
                @if ($equipped)
                    <button wire:click="unequip"
                            class="w-full py-3 bg-transparent border-2 border-emerald-500 text-emerald-400 font-bold rounded-lg transition hover:bg-emerald-500/10 flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Equipped
                    </button>
                @else
                    <button wire:click="equip({{ $c->id }})" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition">
                        Equip
                    </button>
                @endif
            @else
                <button wire:click="buy({{ $c->id }})"
                        class="w-full py-3 font-bold rounded-lg transition {{ $isEpic ? 'bg-purple-500 hover:bg-purple-400 text-white' : 'bg-amber-500 hover:bg-amber-400 text-zinc-950' }}">
                    🪙 {{ $c->price_coins }} — Unlock
                </button>
            @endif
        </div>
    </div>

@elseif ($this->selectedPackage())
    @php $pkg = $this->selectedPackage(); @endphp
    <div class="p-5 lg:p-8 h-full flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Coin Package</h2>
            <button wire:click="$set('selectedPackageKey', null)" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 text-center">
            <p class="text-5xl">🪙</p>
            <p class="font-heading text-2xl font-bold text-zinc-100 mt-3">{{ $pkg['coins'] }} Coins</p>
            <p class="text-sm text-zinc-500 mt-1">{{ $pkg['price_label'] }}</p>
        </div>

        <div class="pt-4">
            <button wire:click="simulateTopup('{{ $pkg['key'] }}')" wire:confirm="Simulate this purchase for demo?"
                    class="w-full py-3 bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold rounded-lg transition">
                Buy (Demo)
            </button>
        </div>
    </div>
@else
    <div class="flex h-full items-center justify-center p-8 text-center text-zinc-600 text-sm">
        Select a character or coin package to see the details.
    </div>
@endif
@php $inline = $inline ?? false; $char = $this->equippedCharacter(); @endphp

<div class="{{ $inline ? '' : 'p-5 lg:p-8 flex flex-col h-full' }}">
    @if ($inline)
        <h2 class="font-heading text-lg font-bold text-zinc-100 mb-4">Overview</h2>
    @else
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Overview</h2>
        </div>
    @endif

    @if ($char)
        <div class="rounded-xl p-4 border {{ $char->tier === 'epic' ? 'bg-purple-500/5 border-purple-500/20' : 'bg-zinc-800/40 border-zinc-700/50' }}">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 {{ $char->tier === 'epic' ? 'ring-2 ring-purple-500/50' : '' }}">
                    <img src="{{ $char->image }}" alt="{{ $char->name }}" class="block w-full h-full object-cover object-center">
                </div>
                <div>
                    <p class="font-heading font-medium text-zinc-100 text-sm">{{ $char->name }}</p>
                    <p class="text-[10px] text-zinc-500 uppercase tracking-wider">Equipped</p>
                </div>
            </div>
            <p class="text-[10px] uppercase tracking-wider {{ $char->tier === 'epic' ? 'text-purple-400' : 'text-emerald-400' }} font-medium mb-1">{{ $char->ability_name }}</p>
            <p class="text-xs text-zinc-400 leading-relaxed">{{ $char->ability_description }}</p>
        </div>
    @endif

    <div class="bg-zinc-800/40 border border-zinc-700/50 rounded-xl p-4 flex items-center justify-between mt-3">
        <div>
            <p class="text-[11px] text-zinc-500 uppercase tracking-wider font-medium">Streak freezes left</p>
            <p class="font-heading text-2xl font-bold text-sky-400 mt-1">{{ $this->stats['freezes_remaining'] }}</p>
        </div>
        <div class="w-10 h-10 rounded-full bg-sky-500/10 flex items-center justify-center text-sky-400">
            <span class="text-lg">❄️</span>
        </div>
    </div>

    <div class="mt-5">
        <p class="text-xs font-medium text-zinc-500 mb-3 uppercase tracking-wider">Quick links</p>
        <div class="space-y-2">
            <a href="/shop" wire:navigate class="flex items-center justify-between p-3 rounded-lg bg-zinc-800/60 hover:bg-zinc-800 transition text-sm text-zinc-300">
                <span>🛒 Visit Shop</span>
                <svg class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </a>
            <a href="/challenges" wire:navigate class="flex items-center justify-between p-3 rounded-lg bg-zinc-800/60 hover:bg-zinc-800 transition text-sm text-zinc-300">
                <span>🏆 Find Challenges</span>
                <svg class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>
</div>
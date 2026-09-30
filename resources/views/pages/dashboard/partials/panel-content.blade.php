@if ($this->selectedHabitId)
    {{-- TAMPILAN DETAIL HABIT (Mirip dengan halaman Habit) --}}
    @php
        $habit = Auth::user()->habits()->find($this->selectedHabitId);
    @endphp
    @if($habit)
    <div class="p-5 lg:p-8 flex flex-col h-full">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Habit Detail</h2>
            <button wire:click="closePanel" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        
        <p class="font-heading text-2xl font-bold text-zinc-100">{{ $habit->name }}</p>
        <p class="text-xs text-emerald-500 mt-1 tracking-wide uppercase font-medium mb-6">{{ $habit->category ?: 'Uncategorized' }}</p>
        
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <p class="text-3xl font-heading font-bold text-zinc-100">{{ $habit->logs()->where('completed', true)->count() }}</p>
                <p class="text-[11px] text-zinc-500 mt-0.5">Total Done</p>
            </div>
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <p class="text-3xl font-heading font-bold text-zinc-100">{{ rtrim(rtrim($habit->target, '0'), '.') }}</p>
                <p class="text-[11px] text-zinc-500 mt-0.5">Target {{ $habit->unit }}</p>
            </div>
        </div>

        <div class="mt-auto">
            <a href="/habits" wire:navigate class="block w-full py-3 text-center bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-lg transition text-sm border border-zinc-700">
                Manage in Habits Page
            </a>
        </div>
    </div>
    @endif

@elseif ($this->selectedChallengeId)
    {{-- TAMPILAN DETAIL CHALLENGE --}}
    @php
        $challenge = App\Models\Challenge::find($this->selectedChallengeId);
    @endphp
    @if($challenge)
    <div class="p-5 lg:p-8 flex flex-col h-full">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Challenge Detail</h2>
            <button wire:click="closePanel" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <p class="font-heading text-xl font-bold text-zinc-100 mb-2">{{ $challenge->name }}</p>
        <p class="text-sm text-zinc-400 mb-6">{{ $challenge->description }}</p>

        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-6">
            <p class="text-xs text-zinc-400 mb-0.5">Linked habit</p>
            <p class="text-sm font-medium text-zinc-100">{{ $challenge->habit_name }}</p>
        </div>

        <div class="mt-auto">
            <a href="/challenges" wire:navigate class="block w-full py-3 text-center bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-lg transition text-sm border border-zinc-700">
                View Leaderboard
            </a>
        </div>
    </div>
    @endif

@else
    {{-- TAMPILAN DEFAULT (OVERVIEW & STATS) - Muncul jika tidak ada yang diklik --}}
    <div class="p-5 lg:p-8 flex flex-col h-full">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-8">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Your Overview</h2>
            {{-- Tombol Close HANYA untuk Mobile jika mereka entah bagaimana nyasar ke default state ini --}}
            <button wire:click="closePanel" class="lg:hidden text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 p-1.5 rounded-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="space-y-4">
            <div class="bg-zinc-800/40 border border-zinc-700/50 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] text-zinc-500 uppercase tracking-wider font-medium">Total Check-ins</p>
                    <p class="font-heading text-2xl font-bold text-zinc-100 mt-1">{{ $this->stats['total_completions'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </div>
            </div>

            <div class="bg-zinc-800/40 border border-zinc-700/50 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] text-zinc-500 uppercase tracking-wider font-medium">Streak Freezes</p>
                    <p class="font-heading text-2xl font-bold text-sky-400 mt-1">{{ $this->stats['freezes_remaining'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-sky-500/10 flex items-center justify-center text-sky-400">
                    <span class="text-lg">❄️</span>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <p class="text-xs font-medium text-zinc-500 mb-3 uppercase tracking-wider">Quick Links</p>
            <div class="space-y-2">
                <a href="/shop" wire:navigate class="flex items-center justify-between p-3 rounded-lg bg-zinc-800/60 hover:bg-zinc-800 transition text-sm text-zinc-300">
                    <span class="flex items-center gap-2">🛒 Visit Shop</span>
                    <svg class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
                <a href="/challenges" wire:navigate class="flex items-center justify-between p-3 rounded-lg bg-zinc-800/60 hover:bg-zinc-800 transition text-sm text-zinc-300">
                    <span class="flex items-center gap-2">⚔️ Find Challenges</span>
                    <svg class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
        </div>
    </div>
@endif
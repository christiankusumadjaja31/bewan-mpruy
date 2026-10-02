@if ($this->selectedHabitId)
    @php $habit = Auth::user()->habits()->find($this->selectedHabitId); @endphp
    @if ($habit)
        <div class="p-5 lg:p-8 h-full flex flex-col">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
                <h2 class="font-heading font-semibold text-zinc-100 text-lg">Habit</h2>
                <button wire:click="closePanel" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <p class="font-heading text-xl font-semibold text-zinc-100">{{ $habit->name }}</p>
            <p class="text-xs text-emerald-500 mt-1 tracking-wide uppercase font-medium mb-5">{{ $habit->category ?: 'Uncategorized' }}</p>

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"></path>
                    </svg>
                    <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->habitStreak($habit) }}</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Current Streak</p>
                </div>
                <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500 mx-auto mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"></path>
                    </svg>
                    <p class="text-2xl font-heading font-bold text-zinc-100">{{ $this->habitTotalCompletions($habit) }}</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Total Completions</p>
                </div>
            </div>

            <p class="text-xs text-zinc-500 mb-5">Target: {{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}</p>

            <div class="mb-6">
                <p class="text-[10px] uppercase tracking-wider text-zinc-500 font-medium mb-2">Last 70 Days</p>
                <div class="grid grid-cols-10 gap-1">
                    @foreach ($this->habitHeatmap($habit) as $day)
                        <div wire:key="hm-{{ $loop->index }}"
                             class="aspect-square rounded-sm {{ $day['completed'] ? 'bg-emerald-500' : ($day['frozen'] ? 'bg-sky-500/70' : 'bg-zinc-800') }}"></div>
                    @endforeach
                </div>
            </div>

            <div class="mt-auto">
                <a href="/habits" wire:navigate class="block w-full py-3 text-center bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-lg transition text-sm border border-zinc-700">
                    Manage in Habits
                </a>
            </div>
        </div>
    @endif

@elseif ($this->selectedChallengeId)
    @php
        $challenge = \App\Models\Challenge::find($this->selectedChallengeId);
        $cStats = $challenge ? $this->challengeStats($challenge) : null;
    @endphp
    @if ($challenge && $cStats)
        <div class="p-5 lg:p-8 h-full flex flex-col">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
                <h2 class="font-heading font-semibold text-zinc-100 text-lg">Challenge</h2>
                <button wire:click="closePanel" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <p class="font-heading text-xl font-semibold text-zinc-100">{{ $challenge->name }}</p>
            <p class="text-xs text-zinc-500 mt-1">{{ $challenge->start_date?->format('d M') }} &ndash; {{ $challenge->end_date?->format('d M Y') }}</p>

            @if ($challenge->description)
                <p class="text-sm text-zinc-400 mt-3">{{ $challenge->description }}</p>
            @endif

            <div class="grid grid-cols-2 gap-3 mt-5">
                <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                    <p class="text-2xl font-heading font-bold text-zinc-100">{{ $cStats['members_count'] }}</p>
                    <p class="text-xs text-zinc-500 mt-0.5">{{ Str::plural('Member', $cStats['members_count']) }}</p>
                </div>
                <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                    <p class="text-2xl font-heading font-bold text-emerald-400">{{ $challenge->points_per_completion }}</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Points / check-in</p>
                </div>
            </div>

            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mt-4">
                <p class="text-xs text-zinc-400 mb-0.5">Your position</p>
                <p class="text-sm font-medium text-zinc-100">
                    @if ($cStats['my_rank'])
                        Rank #{{ $cStats['my_rank'] }} &middot; {{ $cStats['my_points'] }} pts
                    @else
                        Not ranked yet — check in to get on the board.
                    @endif
                </p>
            </div>

            <div class="mt-auto pt-6">
                <a href="/challenges" wire:navigate class="block w-full py-3 text-center bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-lg transition text-sm border border-zinc-700">
                    View Leaderboard
                </a>
            </div>
        </div>
    @endif

@else
    <div class="p-5 lg:p-8 flex flex-col h-full">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
            <h2 class="font-heading font-semibold text-zinc-100 text-lg">Overview</h2>
        </div>

        <div class="space-y-3">
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
                    <p class="text-[11px] text-zinc-500 uppercase tracking-wider font-medium">Streak Freezes Left</p>
                    <p class="font-heading text-2xl font-bold text-sky-400 mt-1">{{ $this->stats['freezes_remaining'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-sky-500/10 flex items-center justify-center text-sky-400">
                    <span class="text-lg">❄️</span>
                </div>
            </div>
        </div>

        @php $char = $this->equippedCharacter(); @endphp
        @if ($char)
            <div class="mt-5 rounded-xl p-4 border {{ $char->tier === 'epic' ? 'bg-purple-500/5 border-purple-500/20' : 'bg-zinc-800/40 border-zinc-700/50' }}">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 {{ $char->tier === 'epic' ? 'ring-2 ring-purple-500/50' : '' }}">
                        <img src="{{ $char->image }}" alt="{{ $char->name }}" class="block w-full h-full object-cover object-center">
                    </div>
                    <p class="font-heading font-medium text-zinc-100 text-sm">{{ $char->name }}</p>
                </div>
                <p class="text-[10px] uppercase tracking-wider {{ $char->tier === 'epic' ? 'text-purple-400' : 'text-emerald-400' }} font-medium mb-1">{{ $char->ability_name }}</p>
                <p class="text-xs text-zinc-400 leading-relaxed">{{ $char->ability_description }}</p>
            </div>
        @endif

        <div class="mt-6">
            <p class="text-xs font-medium text-zinc-500 mb-3 uppercase tracking-wider">Quick Links</p>
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
@endif
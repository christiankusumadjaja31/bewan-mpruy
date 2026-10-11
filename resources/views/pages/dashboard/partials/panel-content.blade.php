@if ($this->selectedChallengeId)
    @php
        $challenge = \App\Models\Challenge::find($this->selectedChallengeId);
        $cStats = $challenge ? $this->challengeStats($challenge) : null;
    @endphp
    @if ($challenge && $cStats)
        <div class="p-5 lg:p-8 h-full flex flex-col pb-28 lg:pb-8">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
                <h2 class="font-heading font-semibold text-zinc-100 text-lg">Challenge</h2>
                <button wire:click="closePanel" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md">
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
    @include('pages.dashboard.partials.overview')
@endif
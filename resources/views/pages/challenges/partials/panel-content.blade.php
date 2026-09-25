@if ($showCreateForm)
    <div class="p-5 lg:p-8 space-y-5 lg:space-y-6 pb-28 lg:pb-8">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
            <h2 class="font-heading font-semibold text-zinc-100 text-xl lg:text-lg">New Challenge</h2>
            <button wire:click="cancelCreate" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="space-y-5">
            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Challenge Name</label>
                <input type="text" wire:model="name" placeholder="e.g., 30 Days Study Duel"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Description</label>
                <textarea wire:model="description" rows="3" placeholder="What's this challenge about?"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700"></textarea>
                @error('description') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Start Date</label>
                    <input type="date" wire:model="start_date"
                           class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                    @error('start_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">End Date</label>
                    <input type="date" wire:model="end_date"
                           class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                    @error('end_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Points per Check-in</label>
                <input type="number" wire:model="points_per_completion" min="1"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                @error('points_per_completion') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <label class="flex items-center gap-3 lg:gap-2 text-sm text-zinc-300 pt-1">
                <input type="checkbox" wire:model="is_private" class="rounded border-zinc-700 bg-zinc-800 text-emerald-500 w-5 h-5 lg:w-4 lg:h-4 focus:ring-emerald-500 focus:ring-offset-0">
                Make this challenge private (invite-only)
            </label>

            <div class="border-t border-zinc-800/50 pt-5">
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">What should participants do?</label>
                <input type="text" wire:model="habit_name" placeholder="e.g., Study 1 hour"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('habit_name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                <p class="text-xs text-zinc-500 mt-1.5">This becomes the habit every participant — including you — automatically gets added to their Habits.</p>
            </div>

            <div class="pt-4 lg:pt-2">
                <button wire:click="create"
                        class="w-full py-3.5 lg:py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-base lg:text-sm">
                    Create Challenge
                </button>
            </div>
        </div>
    </div>

@elseif ($this->selectedChallenge)
    @php $challenge = $this->selectedChallenge; $isMember = (bool) $this->membership; $leaderboard = $this->challengeLeaderboard; @endphp
    <div class="p-5 lg:p-8 space-y-5 lg:space-y-6 pb-28 lg:pb-8">

        <div class="flex items-start justify-between border-b border-zinc-800 pb-4 lg:pb-5">
            <div>
                <p class="font-heading text-2xl lg:text-xl font-semibold text-zinc-100">{{ $challenge->name }}</p>

                <div class="flex items-center gap-2 mt-1.5 lg:mt-2 flex-wrap">
                    <p class="text-[11px] lg:text-xs text-zinc-400 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-70">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        {{ $challenge->start_date?->format('d M') }} &ndash; {{ $challenge->end_date?->format('d M Y') }}
                    </p>

                    {{-- Indikator visibility — selalu tampil buat siapa aja, cuma label, nggak bisa diklik --}}
                    <span class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold tracking-wide uppercase border
                                {{ $challenge->visibility === 'private'
                                    ? 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                    : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' }}">
                        @if ($challenge->visibility === 'private')
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            Private
                        @else
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            Public
                        @endif
                    </span>
                </div>

                {{-- Aksi ubah visibility — cuma buat creator --}}
                @if ($this->isCreator)
                    <button wire:click="toggleVisibility"
                            class="text-[11px] text-zinc-500 hover:text-zinc-300 underline underline-offset-2 mt-2 transition">
                        Change to {{ $challenge->visibility === 'public' ? 'Private' : 'Public' }}
                    </button>
                @endif
            </div>

            <button wire:click="$set('selectedChallengeId', null)" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @if ($challenge->description)
            <p class="text-base lg:text-sm text-zinc-300">{{ $challenge->description }}</p>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:gap-4">
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <p class="text-3xl lg:text-2xl font-heading font-bold text-zinc-100">{{ $challenge->members_count }}</p>
                <p class="text-[11px] lg:text-xs text-zinc-500 mt-0.5">{{ Str::plural('Member', $challenge->members_count) }}</p>
            </div>
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <p class="text-3xl lg:text-2xl font-heading font-bold text-emerald-400">{{ $challenge->points_per_completion }}</p>
                <p class="text-[11px] lg:text-xs text-zinc-500 mt-0.5">Points / check-in</p>
            </div>
        </div>

        @if ($isMember)
            <div class="bg-zinc-800/40 rounded-xl p-4 lg:p-3 flex items-center justify-between border border-zinc-700/50">
                <div>
                    <p class="text-[11px] lg:text-[10px] text-zinc-500 uppercase tracking-wider">Invite code</p>
                    <p class="font-heading font-bold text-zinc-100 tracking-widest text-lg lg:text-base">{{ $challenge->join_code }}</p>
                </div>
                <p class="text-xs text-zinc-500">Share this to invite friends</p>
            </div>

            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 lg:p-3">
                <p class="text-xs text-zinc-400 mb-0.5">Linked habit</p>
                <p class="text-base lg:text-sm font-medium text-zinc-100">{{ $this->membership->habit?->name ?? '—' }}</p>
            </div>
        @endif

        {{-- Podium + Leaderboard --}}
        @if (count($leaderboard))
            <div class="border-t border-zinc-800/50 pt-6 lg:pt-5">
                <p class="font-heading text-base lg:text-sm font-semibold text-zinc-200 mb-6 lg:mb-5">Leaderboard</p>

                {{-- AREA PODIUM (Top 3) - Dikembalikan ke desain Minimalis Clean Pertama --}}
                <div class="flex items-end justify-center gap-4 lg:gap-6 mb-8 lg:mb-6">
                    
                    {{-- Rank 2 (Silver) --}}
                    @if (isset($leaderboard[1]))
                        <div class="flex flex-col items-center mb-1">
                            <div class="relative w-12 h-12 lg:w-11 lg:h-11 rounded-full bg-zinc-800 flex items-center justify-center overflow-hidden font-heading font-bold text-sm text-zinc-200 ring-2 ring-zinc-400/50 shadow-[0_0_10px_rgba(161,161,170,0.2)]">
                                @if ($leaderboard[1]['character_image'] ?? null)
                                    <img src="{{ $leaderboard[1]['character_image'] }}" alt="" class="block w-full h-full object-cover object-center">
                                @else
                                    {{ strtoupper(mb_substr($leaderboard[1]['name'], 0, 1)) }}
                                @endif
                            </div>
                            <p class="text-[11px] lg:text-[10px] text-zinc-300 mt-2 max-w-[64px] lg:max-w-[56px] truncate text-center font-medium">{{ $leaderboard[1]['name'] }}</p>
                            <span class="mt-1 px-2.5 py-0.5 bg-zinc-800 text-zinc-300 text-[10px] font-bold rounded-full border border-zinc-700">{{ $leaderboard[1]['points'] }} pt</span>
                        </div>
                    @endif

                    {{-- Rank 1 (Gold) --}}
                    @if (isset($leaderboard[0]))
                        <div class="flex flex-col items-center z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400 drop-shadow-[0_2px_4px_rgba(251,191,36,0.3)] mb-1.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 6l4 6l5 -4l-2 10h-14l-2 -10l5 4z"></path>
                            </svg>
                            <div class="relative w-16 h-16 lg:w-14 lg:h-14 rounded-full bg-amber-500/20 flex items-center justify-center overflow-hidden font-heading font-bold text-base lg:text-sm text-amber-500 ring-2 ring-amber-400 shadow-[0_0_15px_rgba(251,191,36,0.25)]">
                                @if ($leaderboard[0]['character_image'] ?? null)
                                    <img src="{{ $leaderboard[0]['character_image'] }}" alt="" class="block w-full h-full object-cover object-center">
                                @else
                                    {{ strtoupper(mb_substr($leaderboard[0]['name'], 0, 1)) }}
                                @endif
                            </div>
                            <p class="text-xs lg:text-[11px] text-amber-400 mt-2 max-w-[72px] lg:max-w-[64px] truncate text-center font-bold">{{ $leaderboard[0]['name'] }}</p>
                            <span class="mt-1 px-3 py-0.5 bg-amber-500/10 text-amber-500 text-[11px] font-bold rounded-full border border-amber-500/30">{{ $leaderboard[0]['points'] }} pt</span>
                        </div>
                    @endif

                    {{-- Rank 3 (Bronze) --}}
                    @if (isset($leaderboard[2]))
                        <div class="flex flex-col items-center mb-2">
                            <div class="relative w-10 h-10 lg:w-9 lg:h-9 rounded-full bg-zinc-800 flex items-center justify-center overflow-hidden font-heading font-bold text-sm text-zinc-200 ring-2 ring-orange-500/50 shadow-[0_0_10px_rgba(249,115,22,0.15)]">
                                @if ($leaderboard[2]['character_image'] ?? null)
                                    <img src="{{ $leaderboard[2]['character_image'] }}" alt="" class="block w-full h-full object-cover object-center">
                                @else
                                    {{ strtoupper(mb_substr($leaderboard[2]['name'], 0, 1)) }}
                                @endif
                            </div>
                            <p class="text-[11px] lg:text-[10px] text-zinc-400 mt-2 max-w-[64px] lg:max-w-[56px] truncate text-center font-medium">{{ $leaderboard[2]['name'] }}</p>
                            <span class="mt-1 px-2.5 py-0.5 bg-zinc-900 text-orange-400/80 text-[10px] font-bold rounded-full border border-orange-500/20">{{ $leaderboard[2]['points'] }} pt</span>
                        </div>
                    @endif
                </div>

                {{-- LIST RANKING KESELURUHAN (Desain Card Individual yang Baru) --}}
                <div class="space-y-2.5 lg:space-y-2 max-h-64 lg:max-h-56 overflow-y-auto pr-1">
                    @foreach ($leaderboard as $i => $entry)
                        @php
                            $rank = $i + 1;
                            $isMe = $entry['user_id'] === auth()->id();
                            $isEpic = ($entry['character_tier'] ?? null) === 'epic';
                        @endphp
                        
                        <div wire:key="lb-{{ $entry['user_id'] }}"
                             class="flex items-center justify-between px-3 py-3 lg:px-3 lg:py-2.5 rounded-xl border transition-all hover:scale-[1.01]
                                    {{ $isMe 
                                        ? 'bg-emerald-500/10 border-emerald-500/30 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]' 
                                        : 'bg-zinc-900/80 border-zinc-800/80 hover:bg-zinc-800/60 hover:border-zinc-700' }}">
                            
                            <div class="flex items-center gap-3 lg:gap-3.5 min-w-0">
                                {{-- Kotak Indikator Ranking --}}
                                <div class="w-7 h-7 lg:w-6 lg:h-6 shrink-0 flex items-center justify-center rounded-md 
                                            {{ $rank === 1 ? 'bg-amber-500/20 text-amber-400 font-bold' : 
                                              ($rank === 2 ? 'bg-zinc-300/20 text-zinc-300 font-bold' : 
                                              ($rank === 3 ? 'bg-orange-500/20 text-orange-400 font-bold' : 
                                              'bg-zinc-800 text-zinc-500 font-medium text-xs lg:text-[11px]')) }}">
                                    {{ $rank }}
                                </div>
                                
                                {{-- Avatar Mini --}}
                                <div class="relative w-9 h-9 lg:w-8 lg:h-8 rounded-full bg-zinc-800 flex items-center justify-center overflow-hidden text-[10px] font-bold shrink-0 
                                             {{ $isEpic ? 'ring-2 ring-purple-500/50 shadow-[0_0_8px_rgba(168,85,247,0.3)]' : 'ring-1 ring-zinc-700' }}">
                                    @if ($entry['character_image'])
                                        <img src="{{ $entry['character_image'] }}" alt="" class="block w-full h-full object-cover object-center">
                                    @else
                                        {{ strtoupper(mb_substr($entry['name'], 0, 1)) }}
                                    @endif
                                </div>
                                
                                <span class="truncate font-medium text-base lg:text-sm {{ $isMe ? 'text-emerald-400 font-semibold' : 'text-zinc-200' }}">
                                    {{ $entry['name'] }}
                                </span>
                            </div>
                            
                            {{-- Badge Poin di sebelah kanan --}}
                            <div class="shrink-0 flex items-center gap-1.5 px-2.5 py-1.5 lg:py-1 rounded-lg {{ $isMe ? 'bg-emerald-500/20' : 'bg-zinc-800/80' }}">
                                <span class="text-sm lg:text-xs font-bold font-heading tracking-wide {{ $isMe ? 'text-emerald-400' : 'text-zinc-200' }}">
                                    {{ $entry['points'] }}
                                </span>
                                <span class="text-[10px] uppercase tracking-wider {{ $isMe ? 'text-emerald-500/70' : 'text-zinc-500' }}">
                                    pts
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($isMember)
            <div class="pt-4 lg:pt-2">
                <button wire:click="confirmLeave"
                        class="w-full py-3.5 lg:py-2 text-base lg:text-sm font-medium bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition border border-red-500/20">
                    Leave Challenge
                </button>
            </div>
        @else
            <div class="border-t border-zinc-800/50 pt-5 lg:pt-4 space-y-4 lg:space-y-3">
                <p class="font-heading text-base lg:text-sm font-semibold text-zinc-200">Join this challenge</p>
                <p class="text-sm text-zinc-400 leading-relaxed">
                    Joining adds <span class="text-zinc-200 font-medium">"{{ $challenge->habit_name }}"</span> to your Habits — check in daily to earn points here.
                </p>
                <button wire:click="join"
                        class="w-full py-3.5 lg:py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-base lg:text-sm mt-2">
                    Join Challenge
                </button>
            </div>
        @endif
    </div>
@else
    <div class="flex h-full items-center justify-center p-8 text-center text-zinc-600 text-sm">
        Select a challenge to view details, or click "Create" to start a new one.
    </div>
@endif
<?php

use App\Models\Challenge;
use App\Models\ChallengeLog;
use App\Models\ChallengeMember;
use App\Models\Habit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $activeTab = 'my'; // 'my' | 'discover'

    public bool $showCreateForm = false;
    public ?int $selectedChallengeId = null;

    // Create form
    public string $name = '';
    public string $description = '';
    public string $start_date = '';
    public string $end_date = '';
    public $points_per_completion = 10;
    public bool $is_private = false;

    // Shared habit-linking (dipakai waktu create DAN join)
    public string $habit_name = '';

    // Join via kode
    public string $joinCodeInput = '';

    public function mount(): void
    {
        $this->start_date = today()->toDateString();
        $this->end_date   = today()->addDays(30)->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedChallengeId = null;
        $this->showCreateForm = false;
    }

    #[Computed]
    public function myChallenges()
    {
        return Auth::user()->challenges()
            ->withCount('members')
            ->orderByDesc('challenges.created_at')
            ->get();
    }

    #[Computed]
    public function discoverChallenges()
    {
        return Challenge::where('visibility', 'public')
            ->where('status', 'active')
            ->whereDate('end_date', '>=', today())
            ->whereDoesntHave('members', fn ($q) => $q->where('user_id', Auth::id()))
            ->withCount('members')
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedChallenge(): ?Challenge
    {
        if (! $this->selectedChallengeId) {
            return null;
        }

        return Challenge::withCount('members')->find($this->selectedChallengeId);
    }

    #[Computed]
    public function membership(): ?ChallengeMember
    {
        if (! $this->selectedChallengeId) {
            return null;
        }

        return ChallengeMember::where('challenge_id', $this->selectedChallengeId)
            ->where('user_id', Auth::id())
            ->first();
    }

    #[Computed]
    public function challengeLeaderboard(): array
    {
        if (! $this->selectedChallengeId) {
            return [];
        }

        $points = ChallengeLog::where('challenge_id', $this->selectedChallengeId)
            ->selectRaw('user_id, SUM(points) as total_points')
            ->groupBy('user_id')
            ->pluck('total_points', 'user_id');

        return ChallengeMember::where('challenge_id', $this->selectedChallengeId)
            ->with('user')
            ->get()
            ->map(fn ($member) => [
                'user_id' => $member->user_id,
                'name'    => $member->user->name ?? 'Unknown',
                'points'  => (int) ($points[$member->user_id] ?? 0),
            ])
            ->sortByDesc('points')
            ->values()
            ->toArray();
    }

    public function selectChallenge(int $id): void
    {
        $this->showCreateForm = false;
        $this->selectedChallengeId = $this->selectedChallengeId === $id ? null : $id;
    }

    public function openCreate(): void
    {
        $this->resetCreateForm();
        $this->selectedChallengeId = null;
        $this->showCreateForm = true;
    }

    public function cancelCreate(): void
    {
        $this->showCreateForm = false;
    }

    public function create(): void
    {
        $data = $this->validate([
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string|max:1000',
            'habit_name'            => 'required|string|max:255',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after_or_equal:start_date',
            'points_per_completion' => 'required|integer|min:1',
        ]);

        $challenge = Challenge::create([
            'creator_id'            => Auth::id(),
            'name'                  => $data['name'],
            'description'           => $data['description'],
            'habit_name'            => $data['habit_name'],
            'type'                  => 'point',
            'start_date'            => $data['start_date'],
            'end_date'              => $data['end_date'],
            'target'                => 1,
            'points_per_completion' => $data['points_per_completion'],
            'visibility'            => $this->is_private ? 'private' : 'public',
            'status'                => 'active',
        ]);

        $habit = $this->createHabitForChallenge($challenge);

        ChallengeMember::create([
            'challenge_id' => $challenge->id,
            'user_id'      => Auth::id(),
            'habit_id'     => $habit->id,
            'joined_at'    => now(),
        ]);

        Flux::toast(text: "Challenge created — you're in!", variant: 'success');

        $this->showCreateForm = false;
        $this->resetCreateForm();
        $this->activeTab = 'my';
        $this->selectedChallengeId = $challenge->id;
        unset($this->discoverChallenges, $this->myChallenges);
    }

    public function join(): void
    {
        $challenge = $this->selectedChallenge;

        if (!$challenge) {
            return;
        }

        $habit = $this->createHabitForChallenge($challenge);

        ChallengeMember::create([
            'challenge_id' => $challenge->id,
            'user_id'      => Auth::id(),
            'habit_id'     => $habit->id,
            'joined_at'    => now(),
        ]);

        Flux::toast(text: 'Successfully joined the challenge.', variant: 'success');

        unset($this->discoverChallenges, $this->myChallenges, $this->membership);
    }

    public function confirmLeave(): void
    {
        $this->modal('confirm-leave')->show();
    }

    public function leave(): void
    {
        ChallengeMember::where('challenge_id', $this->selectedChallengeId)
            ->where('user_id', Auth::id())
            ->delete();

        ChallengeLog::where('challenge_id', $this->selectedChallengeId)
            ->where('user_id', Auth::id())
            ->delete();

        $this->modal('confirm-leave')->close();
        Flux::toast(text: 'You left the challenge.', variant: 'danger');

        $this->selectedChallengeId = null;
        unset($this->discoverChallenges, $this->myChallenges, $this->membership);
    }

    public function findByCode(): void
    {
        $this->validate(['joinCodeInput' => 'required|string|max:20']);

        $challenge = Challenge::where('join_code', strtoupper(trim($this->joinCodeInput)))->first();

        if (! $challenge) {
            Flux::toast(text: 'No challenge found with that code.', variant: 'danger');
            return;
        }

        $this->showCreateForm = false;
        $this->selectedChallengeId = $challenge->id;
        $this->joinCodeInput = '';
    }

    private function createHabitForChallenge(Challenge $challenge): Habit
    {
        return Auth::user()->habits()->create([
            'name'       => $challenge->habit_name,
            'frequency'  => 'daily',
            'target'     => 1,
            'start_date' => today()->toDateString(),
        ]);
    }

    private function resetCreateForm(): void
    {
        $this->reset(['name', 'description', 'habit_name', 'points_per_completion', 'is_private']);
        $this->resetValidation();
        $this->start_date = today()->toDateString();
        $this->end_date   = today()->addDays(30)->toDateString();
        $this->points_per_completion = 10;
    }
};

?>

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

    {{-- ============================== --}}
    {{-- SISI KIRI (2/3) --}}
    {{-- ============================== --}}
    <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-8 py-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="font-heading text-2xl font-bold text-zinc-100">Challenges</h1>
            <button wire:click="openCreate"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500 text-zinc-950 hover:bg-emerald-400 transition shadow-[0_0_12px_rgba(16,185,129,0.3)] font-semibold text-sm">
                <span>Create</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>

        <div class="flex gap-1 mb-6 border-b border-zinc-800">
            <button wire:click="setTab('my')"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition
                           {{ $activeTab === 'my' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                My Challenges
            </button>
            <button wire:click="setTab('discover')"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition
                           {{ $activeTab === 'discover' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                Discover
            </button>
        </div>

        @if ($activeTab === 'discover')
            <div class="flex items-center gap-2 mb-6 bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                <input type="text" wire:model="joinCodeInput" placeholder="Have an invite code? Enter it here"
                       class="flex-1 bg-transparent text-sm text-zinc-100 outline-none placeholder:text-zinc-600">
                <button wire:click="findByCode"
                        class="px-3 py-1.5 text-xs font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg transition">
                    Find
                </button>
            </div>
            @error('joinCodeInput') <p class="text-xs text-red-400 -mt-4 mb-4">{{ $message }}</p> @enderror
        @endif

        <div class="space-y-2 pb-20">
            @if ($activeTab === 'my')
                @forelse ($this->myChallenges as $challenge)
                    <div wire:key="mychal-{{ $challenge->id }}" wire:click="selectChallenge({{ $challenge->id }})"
                         class="cursor-pointer bg-zinc-900 rounded-xl py-4 px-4 transition
                              {{ $selectedChallengeId === $challenge->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">
                        <div class="flex items-center justify-between">
                            <p class="font-heading font-medium text-zinc-100">{{ $challenge->name }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">Joined</span>
                        </div>
                        <p class="text-xs text-zinc-500 mt-1">
                            {{ $challenge->start_date?->format('d M') }} &ndash; {{ $challenge->end_date?->format('d M Y') }}
                            &middot; {{ $challenge->members_count }} {{ Str::plural('member', $challenge->members_count) }}
                            &middot; {{ $challenge->points_per_completion }} pts/check-in
                        </p>
                    </div>
                @empty
                    <div class="border border-dashed border-zinc-800 rounded-xl p-12 text-center text-zinc-500 mt-6">
                        <p class="font-heading text-zinc-400 mb-1">You haven't joined any challenges</p>
                        <p class="text-sm">Check the "Discover" tab to find one, or create your own.</p>
                    </div>
                @endforelse
            @else
                @forelse ($this->discoverChallenges as $challenge)
                    <div wire:key="dischal-{{ $challenge->id }}" wire:click="selectChallenge({{ $challenge->id }})"
                         class="cursor-pointer bg-zinc-900 rounded-xl py-4 px-4 transition
                              {{ $selectedChallengeId === $challenge->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">
                        <p class="font-heading font-medium text-zinc-100">{{ $challenge->name }}</p>
                        <p class="text-xs text-zinc-500 mt-1">
                            {{ $challenge->start_date?->format('d M') }} &ndash; {{ $challenge->end_date?->format('d M Y') }}
                            &middot; {{ $challenge->members_count }} {{ Str::plural('member', $challenge->members_count) }}
                            &middot; {{ $challenge->points_per_completion }} pts/check-in
                        </p>
                    </div>
                @empty
                    <div class="border border-dashed border-zinc-800 rounded-xl p-12 text-center text-zinc-500 mt-6">
                        <p class="font-heading text-zinc-400 mb-1">No public challenges right now</p>
                        <p class="text-sm">Be the first — click "Create" to start one, or use an invite code above.</p>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    {{-- ============================== --}}
    {{-- SISI KANAN (1/3) --}}
    {{-- ============================== --}}
    <div class="{{ ($showCreateForm || $selectedChallengeId) ? 'fixed inset-0 z-50 bg-zinc-900' : 'hidden' }} lg:static lg:block lg:w-1/3 lg:z-auto h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 relative shadow-xl">

        @if ($showCreateForm)
            <div class="p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <h2 class="font-heading font-semibold text-zinc-100 text-lg">New Challenge</h2>
                    <button wire:click="cancelCreate" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Challenge Name</label>
                        <input type="text" wire:model="name" placeholder="e.g., 30 Days Study Duel"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                        @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="What's this challenge about?"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700"></textarea>
                        @error('description') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Start Date</label>
                            <input type="date" wire:model="start_date"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                            @error('start_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">End Date</label>
                            <input type="date" wire:model="end_date"
                                   class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                            @error('end_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Points per Check-in</label>
                        <input type="number" wire:model="points_per_completion" min="1"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                        @error('points_per_completion') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-300 pt-1">
                        <input type="checkbox" wire:model="is_private" class="rounded border-zinc-700 bg-zinc-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0">
                        Make this challenge private (invite-only via code)
                    </label>

                    <div class="border-t border-zinc-800/50 pt-5">
                        <label class="block text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">What should participants do?</label>
                        <input type="text" wire:model="habit_name" placeholder="e.g., Study 1 hour"
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                        @error('habit_name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-zinc-500 mt-1">This becomes the habit every participant — including you — automatically gets added to their Habits.</p>
                    </div>

                    <div class="pt-2">
                        <button wire:click="create"
                                class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-sm">
                            Create Challenge
                        </button>
                    </div>
                </div>
            </div>

        @elseif ($this->selectedChallenge)
            @php $challenge = $this->selectedChallenge; $isMember = (bool) $this->membership; $leaderboard = $this->challengeLeaderboard; @endphp
            <div class="p-8 space-y-6">
                <div class="flex items-start justify-between border-b border-zinc-800 pb-4">
                    <div>
                        <p class="font-heading text-xl font-semibold text-zinc-100">{{ $challenge->name }}</p>
                        <p class="text-xs text-zinc-500 mt-1">
                            {{ $challenge->start_date?->format('d M') }} &ndash; {{ $challenge->end_date?->format('d M Y') }}
                            @if ($challenge->visibility === 'private')
                                &middot; <span class="text-amber-400">Private</span>
                            @endif
                        </p>
                    </div>
                    <button wire:click="$set('selectedChallengeId', null)" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-1.5 rounded-md mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($challenge->description)
                    <p class="text-sm text-zinc-300">{{ $challenge->description }}</p>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <p class="text-2xl font-heading font-bold text-zinc-100">{{ $challenge->members_count }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">{{ Str::plural('Member', $challenge->members_count) }}</p>
                    </div>
                    <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                        <p class="text-2xl font-heading font-bold text-emerald-400">{{ $challenge->points_per_completion }}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">Points / check-in</p>
                    </div>
                </div>

                @if ($isMember)
                    <div class="bg-zinc-800/40 rounded-xl p-3 flex items-center justify-between border border-zinc-700/50">
                        <div>
                            <p class="text-[10px] text-zinc-500 uppercase tracking-wider">Invite code</p>
                            <p class="font-heading font-bold text-zinc-100 tracking-widest">{{ $challenge->join_code }}</p>
                        </div>
                        <p class="text-xs text-zinc-500">Share this to invite friends</p>
                    </div>

                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3">
                        <p class="text-xs text-zinc-400">Linked habit</p>
                        <p class="text-sm font-medium text-zinc-100">{{ $this->membership->habit?->name ?? '—' }}</p>
                    </div>
                @endif

                {{-- Podium + Leaderboard --}}
                @if (count($leaderboard))
                    <div class="border-t border-zinc-800/50 pt-4">
                        <p class="font-heading text-sm font-semibold text-zinc-200 mb-3">Leaderboard</p>

                        <div class="flex items-end justify-center gap-2 mb-4">
                            @if (isset($leaderboard[1]))
                                <div class="flex flex-col items-center">
                                    <div class="w-9 h-9 rounded-full bg-zinc-700 flex items-center justify-center font-heading font-bold text-sm text-zinc-200">{{ strtoupper(mb_substr($leaderboard[1]['name'], 0, 1)) }}</div>
                                    <p class="text-[10px] text-zinc-400 mt-1 max-w-[56px] truncate text-center">{{ $leaderboard[1]['name'] }}</p>
                                    <div class="w-14 h-10 bg-zinc-800 rounded-t-md flex items-center justify-center text-xs font-bold text-zinc-300 mt-1">{{ $leaderboard[1]['points'] }}</div>
                                </div>
                            @endif

                            @if (isset($leaderboard[0]))
                                <div class="flex flex-col items-center">
                                    <span class="text-lg">🏆</span>
                                    <div class="w-10 h-10 rounded-full bg-amber-400 flex items-center justify-center font-heading font-bold text-sm text-zinc-900">{{ strtoupper(mb_substr($leaderboard[0]['name'], 0, 1)) }}</div>
                                    <p class="text-[10px] text-zinc-200 mt-1 max-w-[56px] truncate text-center font-medium">{{ $leaderboard[0]['name'] }}</p>
                                    <div class="w-14 h-14 bg-amber-500/20 rounded-t-md flex items-center justify-center text-sm font-bold text-amber-400 mt-1">{{ $leaderboard[0]['points'] }}</div>
                                </div>
                            @endif

                            @if (isset($leaderboard[2]))
                                <div class="flex flex-col items-center">
                                    <div class="w-9 h-9 rounded-full bg-zinc-700 flex items-center justify-center font-heading font-bold text-sm text-zinc-200">{{ strtoupper(mb_substr($leaderboard[2]['name'], 0, 1)) }}</div>
                                    <p class="text-[10px] text-zinc-400 mt-1 max-w-[56px] truncate text-center">{{ $leaderboard[2]['name'] }}</p>
                                    <div class="w-14 h-8 bg-zinc-800 rounded-t-md flex items-center justify-center text-xs font-bold text-zinc-300 mt-1">{{ $leaderboard[2]['points'] }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            @foreach ($leaderboard as $i => $entry)
                                <div wire:key="lb-{{ $entry['user_id'] }}"
                                     class="flex items-center justify-between px-2 py-1.5 rounded-lg text-sm
                                            {{ $entry['user_id'] === auth()->id() ? 'bg-emerald-500/10 text-emerald-400' : 'text-zinc-300' }}">
                                    <span class="flex items-center gap-2">
                                        <span class="w-4 text-xs text-zinc-500">{{ $i + 1 }}</span>
                                        {{ $entry['name'] }}
                                    </span>
                                    <span class="font-medium">{{ $entry['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($isMember)
                    <button wire:click="confirmLeave"
                            class="w-full py-2 text-sm font-medium bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition border border-red-500/20">
                        Leave Challenge
                    </button>
                @else
                    <div class="border-t border-zinc-800/50 pt-4 space-y-3">
                        <p class="font-heading text-sm font-semibold text-zinc-200">Join this challenge</p>
                        <p class="text-sm text-zinc-400">
                            Joining adds <span class="text-zinc-200 font-medium">"{{ $challenge->habit_name }}"</span> to your Habits — check in daily to earn points here.
                        </p>
                        <button wire:click="join"
                                class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-sm">
                            Join Challenge
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="hidden lg:flex h-full items-center justify-center p-8 text-center text-zinc-600 text-sm">
                Select a challenge to view details, or click "Create" to start a new one.
            </div>
        @endif
    </div>
    
    <flux:modal name="confirm-leave" class="w-full max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Leave this challenge?</flux:heading>
                <flux:text class="mt-2 text-zinc-400">
                    Your points in this challenge will be reset and can't be recovered.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('confirm-leave').close()">
                    Cancel
                </flux:button>
                <flux:button variant="danger" wire:click="leave">
                    Leave
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
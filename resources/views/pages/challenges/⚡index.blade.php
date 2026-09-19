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

    // Shared habit-linking
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
    public function isCreator(): bool
    {
        return $this->selectedChallenge && $this->selectedChallenge->creator_id === Auth::id();
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

    public function toggleVisibility(): void
    {
        $challenge = $this->selectedChallenge;

        if (! $challenge || $challenge->creator_id !== Auth::id()) {
            return;
        }

        $challenge->update([
            'visibility' => $challenge->visibility === 'public' ? 'private' : 'public',
        ]);

        Flux::toast(
            text: 'Challenge is now ' . $challenge->visibility . '.',
            variant: 'success'
        );

        unset($this->discoverChallenges, $this->myChallenges);
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

{{-- BUNGKUS ROOT MURNI UNTUK MENCEGAH ERROR MULTIPLE ROOT ELEMENT --}}
<div>
    <div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 flex overflow-hidden">

        {{-- ============================== --}}
        {{-- SISI KIRI (2/3) --}}
        {{-- ============================== --}}
        <div class="w-full lg:w-2/3 h-full overflow-y-auto bg-zinc-950 px-4 py-6 lg:px-8 lg:py-8">

            <div class="flex items-center justify-between mb-6 lg:mb-8">
                <h1 class="font-heading text-2xl lg:text-2xl font-bold text-zinc-100">Challenges</h1>
                <button wire:click="openCreate"
                        class="flex items-center gap-1.5 px-4 py-2 lg:px-3 lg:py-1.5 rounded-lg bg-emerald-500 text-zinc-950 hover:bg-emerald-400 transition shadow-[0_0_12px_rgba(16,185,129,0.3)] font-semibold text-sm">
                    <span>Create</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            </div>

            <div class="flex gap-1 mb-6 border-b border-zinc-800">
                <button wire:click="setTab('my')"
                        class="px-4 py-2.5 lg:py-2 text-sm font-medium border-b-2 transition
                               {{ $activeTab === 'my' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                    My Challenges
                </button>
                <button wire:click="setTab('discover')"
                        class="px-4 py-2.5 lg:py-2 text-sm font-medium border-b-2 transition
                               {{ $activeTab === 'discover' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                    Discover
                </button>
            </div>

            @if ($activeTab === 'discover')
                <div class="flex items-center gap-2 mb-6 bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                    <input type="text" wire:model="joinCodeInput" placeholder="Have an invite code? Enter it here"
                           class="flex-1 bg-transparent text-base lg:text-sm text-zinc-100 outline-none focus:ring-0 placeholder:text-zinc-600">
                    <button wire:click="findByCode"
                            class="px-4 py-2 lg:px-3 lg:py-1.5 text-sm lg:text-xs font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg transition">
                        Find
                    </button>
                </div>
                @error('joinCodeInput') <p class="text-xs text-red-400 -mt-4 mb-4">{{ $message }}</p> @enderror
            @endif

            {{-- Padding bottom ekstra untuk mobile agar tidak tertutup bottom nav --}}
            <div class="space-y-3 lg:space-y-2 pb-32 lg:pb-20">
                @if ($activeTab === 'my')
                    @forelse ($this->myChallenges as $challenge)
                        <div wire:key="mychal-{{ $challenge->id }}" wire:click="selectChallenge({{ $challenge->id }})"
                             class="cursor-pointer bg-zinc-900 rounded-xl py-4 px-4 transition
                                  {{ $selectedChallengeId === $challenge->id ? 'bg-zinc-800/80 ring-1 ring-emerald-500/50' : 'hover:bg-zinc-800/60' }}">
                            <div class="flex items-center justify-between">
                                <p class="font-heading font-medium text-zinc-100 text-base lg:text-[15px]">{{ $challenge->name }}</p>
                                <span class="text-[11px] lg:text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">Joined</span>
                            </div>
                            <p class="text-xs text-zinc-500 mt-1.5 lg:mt-1">
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
                            <p class="font-heading font-medium text-zinc-100 text-base lg:text-[15px]">{{ $challenge->name }}</p>
                            <p class="text-xs text-zinc-500 mt-1.5 lg:mt-1">
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
        {{-- SISI KANAN (1/3) - PANEL DETAIL --}}
        {{-- ============================== --}}
        
        {{-- Desktop: panel statis di kanan --}}
        <div class="hidden lg:flex lg:w-1/3 h-full overflow-y-auto bg-zinc-900 border-l border-zinc-800/80 shadow-xl">
            <div class="w-full">
                @include('pages.challenges.partials.panel-content') 
            </div>
        </div>

        {{-- Mobile: full-screen overlay --}}
        @if ($showCreateForm || $selectedChallengeId)
            <div class="lg:hidden fixed inset-0 z-50 bg-zinc-900 overflow-y-auto">
                @include('pages.challenges.partials.panel-content')
            </div>
        @endif

    </div>

    {{-- Modal dipindah ke dalam Root Div --}}
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
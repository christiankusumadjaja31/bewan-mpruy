<?php

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }
}; ?>

<section class="w-full space-y-6">
    <x-pages::settings.layout active="profile" heading="Your details" subheading="Update your name and email address">

        @php $char = Auth::user()->equippedCharacter; @endphp

        {{-- Identitas Singkat Karakter --}}
        <div class="flex items-center gap-3 bg-zinc-900 border border-zinc-800/80 rounded-xl p-4">
            <div class="w-12 h-12 rounded-full overflow-hidden shrink-0 bg-zinc-800 flex items-center justify-center font-heading font-bold text-zinc-200
                        {{ $char?->tier === 'epic' ? 'ring-2 ring-purple-500/60' : '' }}">
                @if ($char?->image)
                    <img src="{{ $char->image }}" alt="{{ $char->name }}" class="block w-full h-full object-cover object-center">
                @else
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-heading font-medium text-zinc-100 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-zinc-500 truncate">{{ Auth::user()->email }}</p>
            </div>
            @if ($char)
                <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded-full
                             {{ $char->tier === 'epic' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-zinc-800 text-zinc-400' }}">
                    {{ $char->name }}
                </span>
            @endif
        </div>

        {{-- Form Edit Profil (Gaya Garis Bawah Asli) --}}
        <form wire:submit="updateProfileInformation" class="space-y-5 pt-2">
            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Name</label>
                <input type="text" wire:model="name" required autocomplete="name"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Email</label>
                <input type="email" wire:model="email" required autocomplete="email"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('email') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" data-test="update-profile-button"
                        class="w-full py-3.5 lg:py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-base lg:text-sm">
                    Save
                </button>
            </div>
        </form>

        {{-- Sesi Logout --}}
        <div class="mt-8 pt-6 border-t border-zinc-800">
            <h3 class="font-heading text-sm font-semibold text-zinc-200">Session</h3>
            <p class="text-sm text-zinc-500 mt-1 mb-4">Sign out of Versus on this device.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-test="settings-logout-button"
                        class="w-full py-3 lg:py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-lg border border-zinc-700 transition text-base lg:text-sm">
                    Log out
                </button>
            </form>
        </div>

        {{-- Hapus Akun --}}
        <div class="mt-8 pt-6 border-t border-zinc-800">
            <livewire:pages::settings.delete-user-form />
        </div>

    </x-pages::settings.layout>
</section>
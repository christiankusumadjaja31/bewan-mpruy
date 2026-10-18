<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }
}; ?>

<section class="w-full">
    <x-pages::settings.layout active="security" heading="Password" subheading="Use a long, random password to keep your account safe">
        <form wire:submit="updatePassword" class="space-y-5 pt-2">
            @foreach ([
                ['current_password', 'Current password', 'current-password'],
                ['password', 'New password', 'new-password'],
                ['password_confirmation', 'Confirm password', 'new-password'],
            ] as [$field, $label, $autocomplete])
                <div x-data="{ show: false }" wire:key="pw-{{ $field }}">
                    <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">{{ $label }}</label>
                    <div class="relative">
                        <input type="password" :type="show ? 'text' : 'password'" wire:model="{{ $field }}" required
                               autocomplete="{{ $autocomplete }}"
                               @if ($field !== 'current_password') passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" @endif
                               class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 pl-0 pr-12 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                        <button type="button" x-on:click="show = ! show"
                                class="absolute right-0 top-1/2 -translate-y-1/2 text-[11px] uppercase tracking-wider text-zinc-500 hover:text-zinc-300 transition">
                            <span x-text="show ? 'Hide' : 'Show'">Show</span>
                        </button>
                    </div>
                    @error($field) <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>
            @endforeach

            <div class="pt-2">
                <button type="submit" data-test="update-password-button"
                        class="w-full py-3.5 lg:py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-base lg:text-sm">
                    Save
                </button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
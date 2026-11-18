<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Reset password')"
            :description="__('Choose a new password for your account.')"
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf

            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <x-auth-input
                name="email"
                :label="__('Email')"
                type="email"
                :value="request('email')"
                autocomplete="email"
                required
            />

            <x-auth-input
                name="password"
                :label="__('New password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Your new password')"
                :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
                :hint="__('Use at least 8 characters.')"
                required
            />

            <x-auth-input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Repeat your new password')"
                :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
                required
            />

            <button
                type="submit"
                data-test="reset-password-button"
                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-500 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
            >
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-layouts::auth>

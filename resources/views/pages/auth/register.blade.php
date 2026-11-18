<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Create your account')"
            :description="__('Start with one habit — and someone to keep you honest.')"
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-auth-input
                name="name"
                :label="__('Name')"
                type="text"
                :value="old('name')"
                autocomplete="name"
                :placeholder="__('Your name')"
                required
                autofocus
            />

            <x-auth-input
                name="email"
                :label="__('Email')"
                type="email"
                :value="old('email')"
                autocomplete="email"
                placeholder="you@example.com"
                required
            />

            <x-auth-input
                name="password"
                :label="__('Password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Your password')"
                :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
                :hint="__('Use at least 8 characters.')"
                required
            />

            <x-auth-input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Repeat your password')"
                :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
                required
            />

            <button
                type="submit"
                data-test="register-user-button"
                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-500 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
            >
                {{ __('Create account') }}
            </button>
        </form>

        <div class="border-t border-zinc-800/80 pt-6">
            <p class="text-center text-sm text-zinc-500">
                {{ __('Already have an account?') }}
                <a
                    href="{{ route('login') }}"
                    class="font-medium text-emerald-400 transition hover:text-emerald-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                >
                    {{ __('Log in') }}
                </a>
            </p>
        </div>
    </div>
</x-layouts::auth>
